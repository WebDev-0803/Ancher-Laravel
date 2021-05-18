<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Mruser;
use App\Mrstatus;
use KubAT\PhpSimple\HtmlDomParser;
use App\Http\Controllers\AnchorController;

class RoboticProcessController extends Controller
{
    //
    /**
     * Display Robotic view
     *
     * @return View
     */
    public function index()
    {        
        return view('pages.clientarea.roboticProcess', [
            'user'          => Auth::user(),
            'breadcrumb'    => [
                'Home'          => route('dashboard.index'),
                'Robotic'       => ''
            ]
        ]);
    }

    //scraping money robot status 
    public function getMrStatus(Request $request)
    {
        $hasId = true;
        if ($request->input('id') == 'NULL') {
            $hasId = false;
            $count = Mruser::where([
                ['email', $request->input('email')],
                ['password', $request->input('password')]
            ])->get()->count();

            if ($count < 1) {

                $result['success']      = false;
                $result['messages']         = 'Wrrong email or password';
                $response = response(
                    json_encode($result),
                    200
                )
                    ->header('Content-Type', 'application/json');

                return $response;
            }
        }

        $user = $hasId ? 
            Mruser::where([
                    ['id', $request->input('id')]
                ])->first()
            :Mruser::where([
                ['email', $request->input('email')],
                ['password', $request->input('password')]
                ])->first();

        $data = Mrstatus::where('mr_userId', $user->id)->orderBy('mr_id')->get();

        // Return response
        $result['success']      = true;
        $result['mrCampaigns']  = $data;
        $result['mr_userId'] = $user->id;

        $response = response(
            json_encode($result),
            200 
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    public function convertMrToOhi(Request $request)
    {
        $mr_id = $request->input('id');
        $mr_userId = $request->input('mr_userId');

        $mrStatus = Mrstatus::where('mr_id', $mr_id)->first();
        $anchor_url = $mrStatus->anchor_url;
        $mrUrls = $this->scrapeUrl($mr_id, $mr_userId);

        if (!$mrUrls || strpos($mrUrls, 'No campaigns') > 0 || !$anchor_url) {
            $result['success']      = false;
            $result['message']      = "Processing Failed. No Data!";
            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }

        $anchor = new AnchorController();
        $tempTier1 = $anchor->func_addTempTier1($anchor_url, $mrUrls);
        if (!$tempTier1['success']) {
            // Return response
            $result['success']      = false;
            $result['messages']     = "Temp db Error!";
            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }

        $results = $anchor->func_processPlainText($tempTier1['id']);
        if (!$results['success']) {
            $result['success']      = false;
            $result['messages']     = "Processing Error!";
            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }

        Mrstatus::where('mr_id', $mr_id)->update([
            'status'    => 'Sent to OHI'
        ]);
        // Return response
        $result['success']      = true;
        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    private function scrapeUrl($id, $mr_userId)
    {
        try {
            $handle = curl_init();

            $url = "https://www.moneyrobot.com/campaign-manager/export_successfully_links.php";

            $header = array(
                Mruser::where('id', $mr_userId)->first()->security
            );

            $curl_data = 'selected_IDs=' . $id . '&export_successfully_links=Export+successfully+links';
            curl_setopt_array(
                $handle,
                array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER     => true,
                    CURLOPT_HTTPHEADER => $header,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS     => $curl_data
                )
            );

            $data = curl_exec($handle);
            $data = str_replace('</h2>', '</h2><br>', $data);
            $data = str_replace('</h3>', '</h3><br>', $data);
            curl_close($handle);
            $text =  HTMLDomParser::str_get_html($data)->plaintext;
            return $text;
        } catch (\Exception $e) {
            return null;
        }
    }

    //Delete campaign
    public function mrDeleteCampaign(Request $request)
    {        
        $rowIndexes = $request->input('rows');
        $deletedRows = [];
        if(!$rowIndexes) {
            $result['success']      = false;
            $result['message']      = "Please select rows!";
            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }

        $rows = $rowIndexes;//Mrstatus::select('mr_id')->whereIn('id', $rowIndexes)->get();

        foreach ($rows as $id) {
            $mr_userId = $request->input('mr_userId');
            $handle = curl_init();

            $url = "https://www.moneyrobot.com/campaign-manager/";

            $header = array(
                Mruser::where('id', $mr_userId)->first()->security
            );

            $curl_data = 'delete=Delete&id=' . $id;
            curl_setopt_array(
                $handle,
                array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER     => true,
                    CURLOPT_HTTPHEADER => $header,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS     => $curl_data
                )
            );

            $data = curl_exec($handle);
            curl_close($handle);

            if ($data) {
                Mrstatus::where('mr_id', $id)->delete();
                $deletedRows[] = $id;
            }
        }
        
        // Return response
        $result['success']      = true;
        $result['rows'] = $deletedRows;
        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    public function processRetry(Request $request) 
    {
        $mr_userId = $request->input('id');

        Mrstatus::where('progress', 3)->update(array('progress' => 2));
        $count = $this->convertToPorcesss();

        Mrstatus::where('progress', '>=', 2)->update(array('progress' => 0));

        $result['success']      = true;
        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;   
    }

    private function convertToPorcesss() 
    {
        $rows = Mrstatus::where('progress', 2)->orderBy('mr_id')->get();
        $anchor = new AnchorController();
        $unprocessed = [];
        $processed = [];

        foreach ($rows as $row) {

            $currentRow = Mrstatus::where('mr_id', $row->mr_id)->first();

            if(!($currentRow)) {
                $unprocessed[] = $row->mr_id;
                continue;
            } else if ($currentRow->progress != 2) {
                $unprocessed[] = $row->mr_id;
                continue;
            }

            $currentRow->progress = 3;
            $currentRow->save();

            $mrUrls = $this->scrapeUrl($currentRow->mr_id, $currentRow->mr_userId);

            if (!$mrUrls || strpos($mrUrls, 'No campaigns') > 0) {
                $unprocessed[] = $currentRow->mr_id;
                continue;
            }            
            
            try {
                $tempTier1 = $anchor->func_addTempTier1($currentRow->anchor_url, $mrUrls);
                if (!$tempTier1['success']) {
                    $unprocessed[] = $currentRow->mr_id;
                    continue;
                }
                
                $results = $anchor->func_processPlainText($tempTier1['id']);
                if (!$results['success']) {
                    $unprocessed[] = $currentRow->mr_id;
                    continue;
                }
            } catch(\Exception $e) {
                //do nothing
            }

            $processed[] = $currentRow->mr_id;
            $currentRow->status = "Processed";
            $currentRow->progress = 1;
            $currentRow->save();

        }

        Mrstatus::where('progress', 3)->update(array('progress' => 2));
        $rows = Mrstatus::where('progress', 2)->orderBy('mr_id')->get();
        foreach ($rows as $row) {

            $currentRow = Mrstatus::where('mr_id', $row->mr_id)->first();

            if(!($currentRow)) {
                $unprocessed[] = $row->mr_id;
                continue;
            } else if ($currentRow->progress != 2) {
                $unprocessed[] = $row->mr_id;
                continue;
            }

            $currentRow->progress = 3;
            $currentRow->save();

            $mrUrls = $this->scrapeUrl($currentRow->mr_id, $currentRow->mr_userId);

            if (!$mrUrls || strpos($mrUrls, 'No campaigns') > 0) {
                $unprocessed[] = $currentRow->mr_id;
                continue;
            }            
            
            try {
                $tempTier1 = $anchor->func_addTempTier1($currentRow->anchor_url, $mrUrls);
                if (!$tempTier1['success']) {
                    $unprocessed[] = $currentRow->mr_id;
                    continue;
                }
                
                $results = $anchor->func_processPlainText($tempTier1['id']);
                if (!$results['success']) {
                    $unprocessed[] = $currentRow->mr_id;
                    continue;
                }
            } catch(\Exception $e) {
                //do nothing
            }

            $processed[] = $currentRow->mr_id;
            $currentRow->status = "Processed";
            $currentRow->progress = 1;
            $currentRow->save();

        }

        unset($anchor);
        //Mrstatus::whereIn('mr_id', $unprocessed)->update(array('progress' => 0));
        Mrstatus::where('progress', 3)->update(array('progress' => 0));

        return count($processed);
    }

    public function processAll(Request $request) 
    {
        $mr_userId = $request->input('id');
        $rowIndexes = $request->input('rows');

        Mrstatus::whereIn('mr_id', $rowIndexes)->update(array('progress' => 2));
        $count = $this->convertToPorcesss();

        // Return response
        $result['success']      = true;
        $result['count']      = $count;
        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;   
    }

    public function getProgressStatus() {
        $processed = Mrstatus::select('mr_id')->where('progress', 1)->orderBy('mr_id')->get();
        $processing = Mrstatus::select('mr_id')->where('progress', '>=', 2)->orderBy('mr_id')->get();
        $now = Mrstatus::select('mr_id')->where('progress', 3)->orderBy('mr_id')->get();

        if (count($processing) == 0 && count($now) == 0)
            Mrstatus::where('progress', 1)->update(array('progress' => 0));        
        
        // Return response
        $result['success']              = true;
        $result['processingIds']        = $processing;
        $result['processedIds']         = $processed;
        $result['now']                  = $now;
        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;   
    }
}
