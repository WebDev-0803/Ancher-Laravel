<?php

namespace App\Http\Controllers;


use App\Campaign;
use App\CampaignLink;
use App\OhiBatchState;
use App\Setting;
use App\TempTier1;
use App\TempTier1Link;
use App\OhiStatus;
use Carbon\Carbon;

use App\Http\Controllers\DropboxController as Dropbox;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Hamidjavadi\guid;

class AnchorController extends Controller
{

    private $dropboxClient;

    /**
     * Controller constructor to init some variables
     *
     *
     * @return Void
     */
    public function __construct()
    {

        // TODO: Add check settings error reporting
        $setting = Setting::first();
        $this->dropboxClient = new Dropbox($setting->dropbox_access_token);
    }


    /**
     * Show search anchors page
     *
     * @return View
     */
    public function showSearchForm()
    {

        return view('pages.clientarea.searchAnchors', [
            'user'          => Auth::user(),
            'breadcrumb'    => [
                'Home'              => route('dashboard.index'),
                'Process Anchors'    => ''
            ]
        ]);
    }


    /**
     * Search URL into webpages and return details
     *
     * @param Request $request
     * @return JSON
     */
    public function search(Request $request)
    {

        $result = array(
            'success'   => null,
            'messages'  => [],
            'data'      => [],
        );


        // Begin check inputs
        $data = $request->input();
        $validationResult = Validator::make($data, [
            'target_Anchor'  => ['required', 'url'],
            'link'          => ['required', 'url']
        ]);

        if ($validationResult->fails() == true) {

            $result['success']  = false;

            $messages = $validationResult->errors();
            $messages = $messages->messages();

            foreach ($messages as $key => $value) {
                $result['messages'][] = $value;
            }

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }
        // End check inputs


        // Begin fetch webpage
        $links              = array($request->input('link'));
        $targetAnchorUrl    = $request->input('target_Anchor');
        $analyzeResult      = $this->analyzeUploadedFileLinks($targetAnchorUrl, $links, false);
        // End fetch webpage


        // Return response
        $result['success']      = true;
        $result['messages'][]   = 'Your link has been processed.';
        $result['data']         = $analyzeResult[0];

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }


    /**
     * Process the plain text that user sent
     *
     * @param Request $request
     * @return JSON
     */
    public function processPlainText(Request $request)
    {
        $data = $request->input();
        // Begin check inputs

        $validationResult = Validator::make($data, [
            'temptier1_id'    => ['required', 'string'],
        ]);


        if ($validationResult->fails() == true) {

            $result['success']  = false;

            $messages = $validationResult->errors();
            $messages = $messages->messages();

            foreach ($messages as $key => $value) {
                $result['messages'][] = $value;
            }

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }
        // End check inputs

        $tempTier1_id  =      $request->input('temptier1_id');

        $response = response(
            json_encode($this->func_processPlainText($tempTier1_id)),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    public function func_processPlainText($tempTier1_id)
    {
        $result = array(
            'success'   => null,
            'messages'  => [],
            'data'      => [],
        );
        $fileContent    = '';
        $campaignName   = '';
        $targetAnchor   = '';


        //Clear temp_tier_links table with temptier1_id
        TempTier1Link::where('temptier1_id', $tempTier1_id)->delete();
        TempTier1::where('id', $tempTier1_id)->update([
            'completed_percent' => 0
        ]);

        $fileContent   =      TempTier1::where('id', $tempTier1_id)->first()->text;
        $targetAnchor  =      TempTier1::where('id', $tempTier1_id)->first()->anchor_url;


        // Begin validate file format
        $isTier2 = $tempTier1_id == 'Tier2';
        if ($this->validateSourceFormat($fileContent, $isTier2) == false) {

            $result['success']      = false;
            $result['messages'][]   = 'Please send a valid source !';

            return $result;
        }
        // End validate file format


        // Begin get and check campaign name for the Tier word
        $campaignName           = $this->getCampaignName($fileContent);
        $campaignNameLowercase  = strtolower($campaignName);

        // End get and check campaign name for the Tier word        

        // Begin get all campaign's links and store on the Dropbox
        // $this->createCampaignDirectory($campaignName);
        $campaign               = $this->insertCampaignToDatabase($campaignName);
        $campaignAllLinks       = $this->extractCampaignAllLinks($fileContent);
        $insertedCampaignLinks  = $this->insertCampaignLinksToDatabase($campaign->id, $campaignAllLinks);
        $fileDetails            = $this->prepareCampaignLinksTxtFile($campaignName, $campaignAllLinks);
        //pck
        //$this->dropboxClient->uploadFile($fileDetails['path'], $fileDetails['fileContent']);
        // End get all campaign's links and store on the Dropbox

        if ((strpos($campaignNameLowercase, 'tier2') > -1)
            || (strpos($campaignNameLowercase, 'tier 2') > -1)
            || (strpos($campaignNameLowercase, 'tier  2') > -1)
        ) {

            $result['success']      = false;
            $result['messages'][]   = 'Tier2 Links are sent to OHI in success!';

            return $result;
        }


        // Begin analyzing campaign links        
        $linksForAnalyze    = $this->extractSourceFileLinksForAnalyze($fileContent);
        if (!$linksForAnalyze) {
            TempTier1::where('id', $tempTier1_id)->update([
                'completed_percent' =>  100
            ]);

            $result['success']      = true;
            $result['messages'][]   = 'Your file has been processed';
            $result['data']         = [
                'campaignId'        => $campaign->uuid,
                'campaignName'  => $campaignName,
                'analyzeResult' => []
            ];

            return $result;
        }

        $analyzeResults     = $this->analyzeUploadedFileLinks($targetAnchor, $linksForAnalyze, false);
        // End analyzing campaign links

        // Begin store the "Tier 1.txt" it on the Dropbox
        //pck
        //$fileName       = 'Tier 1';        
        //$fileDetails    = $this->prepareCampaignAnalyzedLinksTxtFile($campaignName, $fileName, $linksForAnalyze);
        //pck
        //$this->dropboxClient->uploadFile($fileDetails['path'], $fileDetails['fileContent']);
        // End store the "Tier 1.txt" on the Dropbox


        // Begin store the "Tier 1.csv" on the Dropbox
        //pck
        //$fileDetails = $this->prepareCampaignLinksCsvFile($campaignName, $fileName, $analyzeResults);
        //pck
        //$this->dropboxClient->uploadFile($fileDetails['path'], $fileDetails['fileContent']);
        // End store the "Tier 1.csv" on the Dropbox        

        //Store temp data to db
        $this->storeTempTier1($tempTier1_id, $analyzeResults);
        //

        // Return response
        $result['success']      = true;
        $result['messages'][]   = 'Your file has been processed';
        $result['data']         = [
            'campaignId'        => $campaign->uuid,
            'campaignName'  => $campaignName,
            'analyzeResult' => $analyzeResults
        ];

        return $result;
    }

    /**
     * Upload source file and process it
     *
     * @param Request $request
     * @return JSON
     */
    public function processUploadedFile(Request $request)
    {

        $result = array(
            'success'   => null,
            'messages'  => [],
            'data'      => [],
        );
        $fileContent    = '';
        $campaignName   = '';

        // Begin check file
        if ($request->hasFile('source_file') == false) {

            $result['success']      = false;
            $result['messages'][]   = 'Please send a file !';

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }
        // End check file


        // Begin check file
        $data = $request->file();
        $data['target_anchor'] = $request->input('target_anchor');
        $validationResult = Validator::make($data, [
            'target_anchor' => ['required', 'url'],
            'source_file'   => ['required', 'file', 'mimes:txt,rtf', 'max:10240'],
        ]);

        if ($validationResult->fails() == true) {

            $result['success']  = false;

            $messages = $validationResult->errors();
            $messages = $messages->messages();

            foreach ($messages as $key => $value) {
                $result['messages'][] = $value;
            }

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }
        // End check file


        // Begin get file content
        $sourceFile = $request->file('source_file');
        $extension = $sourceFile->extension();

        if ($extension == 'txt') {

            $fileContent = file_get_contents($sourceFile->getRealPath());
        } else {

            $doc            = \RTFLexer::file($sourceFile->getRealPath());
            $fileContent    = $doc->extractText();
        }
        // End get file content


        // Begin validate file format
        if ($this->validateSourceFormat($fileContent) == false) {

            $result['success']      = false;
            $result['messages'][]   = 'Please send a valid file !';

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }
        // End validate file format


        // Begin get and check campaign name for the Tier word
        $campaignName           = $this->getCampaignName($fileContent);
        $campaignNameLowercase  = strtolower($campaignName);

        if (strpos($campaignNameLowercase, 'tier') > -1) {

            $result['success']      = false;
            $result['messages'][]   = 'This file processed before !';

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }
        // End get and check campaign name for the Tier word


        // Begin get all campaign's links and store on the Dropbox
        // $this->createCampaignDirectory($campaignName);
        $campaignId             = $this->insertCampaignToDatabase($campaignName);
        $campaignAllLinks       = $this->extractCampaignAllLinks($fileContent);
        $insertedCampaignLinks  = $this->insertCampaignLinksToDatabase($campaignId, $campaignAllLinks);
        $fileDetails            = $this->prepareCampaignLinksTxtFile($campaignName, $campaignAllLinks);

        $this->dropboxClient->uploadFile($fileDetails['path'], $fileDetails['fileContent']);
        // End get all campaign's links and store on the Dropbox


        // Begin analyzing campaign links
        $targetAnchor       = $request->input('target_anchor');
        $linksForAnalyze    = $this->extractSourceFileLinksForAnalyze($fileContent);
        $analyzeResults     = $this->analyzeUploadedFileLinks($targetAnchor, $linksForAnalyze, false);
        // End analyzing campaign links


        // Begin store the "Tier 1.txt" and upload it on the Dropbox
        $fileName       = 'Tier 1';
        $fileDetails    = $this->prepareCampaignAnalyzedLinksTxtFile($campaignName, $fileName, $linksForAnalyze);

        $this->dropboxClient->uploadFile($fileDetails['path'], $fileDetails['fileContent']);
        // End store the "Tier 1.txt" and upload it on the Dropbox


        // Begin store the "Tier 1.csv" and upload it on the Dropbox
        $fileDetails = $this->prepareCampaignLinksCsvFile($campaignName, $fileName, $analyzeResults);
        $this->dropboxClient->uploadFile($fileDetails['path'], $fileDetails['fileContent']);
        // End store the "Tier 1.csv" and upload it on the Dropbox


        // Return response
        $result['success']      = true;
        $result['messages'][]   = 'Your file has been processed';
        $result['data']         = [
            'campaignName'  => $campaignName,
            'analyzeResult' => $analyzeResults
        ];

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }


    /**
     * Analyze uploaded file links
     *
     * @param String $anchor
     * @param Array $links
     * @return Array
     */
    public function analyzeUploadedFileLinks($tagetAnchor, $urlLinks, $timeout)
    {

        $DIV_COUNT = 5;
        $TIME_OUT = $timeout ? 0 : 5; //3 sec

        $nowTime        = Carbon::now()->toDateTimeString();
        $searchResults  = [];
        $counter        = 0;
        $group_count = ceil(count($urlLinks) / $DIV_COUNT);

        for ($i = 0; $i < $group_count; $i++) {

            $links = array_slice($urlLinks, $DIV_COUNT * $i, min($DIV_COUNT, count($urlLinks) - $DIV_COUNT * $i));

            $client         = new GuzzleClient([
                'timeout' => $TIME_OUT,
            ]);

            $promises       = [];

            // Begin fetch page contents
            foreach ($links as $key => $link) {

                $promises[$link] = $client->getAsync($link);
            }

            $promiseResults = Promise\settle($promises)->wait();
            // $promiseResults = Promise\unwrap($promises);
            // End fetch page contents


            // Begin analyze page contents
            foreach ($promiseResults as $link => $promiseResult) {

                $counter++;
                $searchResult = [];

                if ($promiseResult['state'] == 'fulfilled') {

                    $response   = $promiseResult['value'];
                    $body       = $response->getBody();


                    $searchResult = array(
                        'anchors'       => [],
                        'checked_at'    => $nowTime,
                        'file_size'     => round(($body->getSize() / 1024), 2) . ' KB',
                        'number'        => $counter,
                        'page_content'  => $body->getContents(),
                        'status'        => $this->curlStatusTitle($response->getStatusCode()),
                        'url'           => $link,
                    );

                    // Begin search target anchor in the content
                    $regexSearchResult  = $this->regexSearch($tagetAnchor, $searchResult['page_content']);

                    foreach ($regexSearchResult as $key => $anchor) {

                        $searchResult['anchors'][] = [
                            'anchor'    => $anchor[0],
                            'position'  => $anchor[1],
                            'text'      => $this->getAnchorText($anchor[0]),
                            'url'       => $this->getAnchorHref($anchor[0]),
                        ];
                    }
                    // End search target anchor in the content

                    //pck reduce response data.
                    $searchResult['page_content'] = '';
                } else { // rejected

                    $curlStatusTitle    = '';
                    $exception          = $promiseResult['reason'];
                    $handlerContext     = $exception->getHandlerContext();

                    if (count($handlerContext) != 0) {

                        $curlStatusTitle = $this->curlStatusTitle($handlerContext['errno']);
                    } else {

                        $curlStatusTitle = $this->curlStatusTitle($exception->getCode());
                    }


                    $searchResult = array(
                        'anchors'       => [],
                        'checked_at'    => $nowTime,
                        'file_size'     => '0' . ' KB',
                        'number'        => $counter,
                        'page_content'  => '',
                        'status'        => $curlStatusTitle,
                        'url'           => $link,
                    );
                }

                $searchResults[] = $searchResult;
            }
        }


        // End analyze page contents
        return $searchResults;
    }


    /**
     * Get CURL status code title
     *
     * @param Int $code
     * @return String
     */
    private function curlStatusTitle($code)
    {

        $title = '';

        switch ($code) {
            case 0:
                $title = 'Timeout';
                break;
            case 28:
                $title = 'Timeout';
                break;
            case 200:
                $title = 'Success';
                break;
            case 400:
                $title = 'Bad Request';
                break;
            case 401:
                $title = 'Unauthorized';
                break;
            case 403:
                $title = 'Forbidden';
                break;
            case 404:
                $title = 'Not Found';
                break;
            case 405:
                $title = 'Method Not Allowed';
                break;
            case 429:
                $title = 'Too Many Requests';
                break;
            case 451:
                $title = 'Unavailable For Legal Reasons';
                break;
            case 500:
                $title = 'Internal Server Error';
                break;
            case 502:
                $title = 'Bad Gateway';
                break;
            case 503:
                $title = 'Internal Server Error';
                break;
            case 504:
                $title = 'Timeout';
                break;
            default:
                $title = 'Unknown Error';
                break;
        }

        return $title;
    }


    /**
     * Fetch page
     *
     * @param String $url
     * @return Array
     */
    private function fetchPageContent($url)
    {

        $httpClient = new GuzzleClient();
        $result     = [
            'status'        => null,
            'file_size'     => null,
            'page_content'  => null
        ];

        try {

            $response = $httpClient->request('GET', $url, [
                'allow_redirects' => true,
                'max'   => 5
            ]);

            $result['status'] = $response->getStatusCode();

            if ($result['status'] == 200) {

                $body                   = $response->getBody();
                $result['page_content'] = $body->getContents();
                $result['file_size']    = $body->getSize();
            }
        } catch (RequestException $exception) {

            $result['status'] = $exception->getCode();
            $result['page_content'] = '';
        }

        return $result;
    }


    /**
     * Regex search
     *
     * @param String $subject
     * @param String $string
     * @return Array
     */
    private function regexSearch($subject, $string)
    {

        $length = strlen($string);
        $matchAnchors = [];

        // Begin scape $subject
        $regex = addslashes($subject);
        $regex = addcslashes($regex, '!@#$%^&?*().,\'\"\/-+');
        $regex = '/<a.*?href[\s]?=.*?[\s\'\"]?' . $regex . '[\'\"]?.*?>.*?<\/a>/';
        // End scape $subject

        preg_match_all($regex, $string, $matchAnchors, PREG_OFFSET_CAPTURE);
        return $matchAnchors[0];
    }


    /**
     * Get anchor tag text
     *
     * @param String $anchor
     * @return String
     */
    private function getAnchorText($anchor)
    {

        $regex      = '/<a.*>(.*?)<\/a>/';
        $superRegex = '/<a[\s]+([^>]+)>((?:.(?!\<\/a\>))*.)<\/a>/';

        $matches    = [];

        preg_match($regex, $anchor, $matches);

        if ($matches[1] != '')
            return $matches[1];
        else {
            $matches    = [];
            preg_match($superRegex, $anchor, $matches);
            return $matches[2];
        }
    }


    /**
     * Get anchor tag href
     *
     * @param String $anchor
     * @return String
     */
    private function getAnchorHref($anchor)
    {

        $regex      = '/<a.*href=\"(.*?)\".*<\/a>/';
        $matches    = [];

        preg_match($regex, $anchor, $matches);
        if (count($matches) > 1)
            return $matches[1];
        else
            return null;
    }


    /**
     * Validate Source File format
     *
     * @param String $source
     * @return Boolean
     */
    private function validateSourceFormat($source, $isTier2 = false)
    {

        $findHeadingsRegex  = '';
        if ($isTier2) {
            $findHeadingsRegex = '/(Web.*Blogs\\r\\n)|(Web.*Blogs\\n)|(Web.*Blogs (.*)\\n)|(Social Likes and Shares\\r\\n)|(Social Likes and Shares\\n)|(Social Bookmarking\\r\\n)|(Social Bookmarking\\n)/';
        } else {
            $findHeadingsRegex = '/(Web.*Blogs\\r\\n)|(Web.*Blogs\\n)|(Web.*Blogs (.*)\\n)|(Social Likes and Shares\\r\\n)|(Social Likes and Shares\\n)|(Social Bookmarking\\r\\n)|(Social Bookmarking\\n)/';
        }

        $matches            = [];


        if ($source == '') {
            return false;
        }


        // Begin check headings
        preg_match_all($findHeadingsRegex, $source, $matches, PREG_OFFSET_CAPTURE);
        $matches = $matches[0];


        if (count($matches) == 0) {
            return false;
        }
        // End check headings

        return true;
    }


    /**
     * Extract all links from campaign file
     *
     * @param String $source
     * @return Array
     */
    public function extractCampaignAllLinks($source)
    {

        $allURLsRegex   = '/(http.*$)|(http.*\\r\\n)|(http.*\\n)/m';
        $matchedLines   = [];
        $links          = [];

        preg_match_all($allURLsRegex, $source, $matchedLines, PREG_OFFSET_CAPTURE);

        $matchedLines = $matchedLines[0];

        foreach ($matchedLines as $key => $line) {

            $isDuplicate    = false;
            $link           = $line[0];
            $link           = str_replace("\r", '', $link);
            $link           = str_replace("\n", '', $link);
            $link           = trim($link);


            // Begin check duplicate link
            foreach ($links as $key => $value) {

                if ($link == $value) {

                    $isDuplicate = true;
                    break;
                }
            }
            // End check duplicate link


            if ($isDuplicate == false) {
                $links[] = $link;
            }
        }
        // End extranct URLs

        return $links;
    }


    /**
     * Extract URLs from source file, fetch URLs from first section
     *
     * @param String $source
     * @return Array
     */
    private function extractSourceFileLinksForAnalyze($source)
    {

        $firstSectionRegex              = '/(Web.*Blogs\\r\\n)|(Web.*Blogs\\n)|(Web.*Blogs \(.*\)\\r\\n)|(Web.*Blogs \(.*\)\\n)/';
        $allURLsRegex                   = '/(http.*$)|(http.*\\r\\n)|(http.*\\n)/m';
        $fisrtSectionMatches            = [];
        $fisrtOfOtherSectionsMatches    = [];
        $searchableSource               = '';
        $matchURLs                      = '';
        $URLs                           = [];


        preg_match_all($firstSectionRegex, $source, $fisrtSectionMatches, PREG_OFFSET_CAPTURE);

        $fisrtSectionMatches            = $fisrtSectionMatches[0];
        $fisrtOfOtherSectionsMatches    = $fisrtSectionMatches[1];


        // Begin get searchable source
        $start  = 0;
        $length = 0;

        if (count($fisrtSectionMatches)>0) {

            $start  = $fisrtSectionMatches[0][1];            

            if (count($fisrtSectionMatches)>1) {
                $length = $fisrtSectionMatches[1][1] - $fisrtSectionMatches[0][1];
                $searchableSource = substr($source, $start, $length);
            } else {
                $searchableSource = $source;
            }
        } else {
            $fisrtSectionMatches            = [];
            $fisrtOfOtherSectionsMatches    = [];

            $firstSectionRegex              = '/(Social Bookmarking\\r\\n)|(Social Bookmarking\\n)/';
            $firstOfOtherSectionsRegex      = '/(Social Bookmarking \(.*\)\\r\\n)|(Social Bookmarking \(.*\)\\n)/m';

            preg_match_all($firstSectionRegex, $source, $fisrtSectionMatches, PREG_OFFSET_CAPTURE);
            preg_match_all($firstOfOtherSectionsRegex, $source, $fisrtOfOtherSectionsMatches, PREG_OFFSET_CAPTURE);

            $fisrtSectionMatches            = $fisrtSectionMatches[0];
            $fisrtOfOtherSectionsMatches    = $fisrtOfOtherSectionsMatches[0];

            if (!$fisrtSectionMatches) {
                return null;
            }


            if (count($fisrtOfOtherSectionsMatches) != 0) {

                $start  = $fisrtSectionMatches[0][1];
    
                $index = -1;
                for ($i=0; $i<count($fisrtOfOtherSectionsMatches);$i++)
                    if ($fisrtOfOtherSectionsMatches[$i][1] > $fisrtSectionMatches[0][1]) {
                        $index = $i;
                        break;
                    }
                
                if ($index > 0)
                    $length = $fisrtOfOtherSectionsMatches[0][1] - $fisrtSectionMatches[0][1];
                else
                    $length = strlen($source) - $start;
    
                $searchableSource = substr($source, $start, $length);
            } else {
                $searchableSource = $source-1;
            }
        }
        // End get searchable source
        

        // Begin extranct URLs
        preg_match_all($allURLsRegex, $searchableSource, $matchURLs, PREG_OFFSET_CAPTURE);
        $matchURLs = $matchURLs[0];


        foreach ($matchURLs as $key => $matchURL) {

            $isDuplicate    = false;
            $URL            = $matchURL[0];
            $URL            = str_replace("\r\n", '', $URL);
            $URL            = trim($URL);


            // Begin check duplicate URL
            foreach ($URLs as $key => $value) {

                if ($URL == $value) {

                    $isDuplicate = true;
                    break;
                }
            }
            // End check duplicate URL


            if ($isDuplicate == false) {
                $URLs[] = $URL;
            }
        }
        // End extranct URLs


        return $URLs;
    }


    /**
     * Fetch campaign name
     *
     * @param String $fileContent
     * @return String
     */
    public function getCampaignName($fileContent)
    {

        $lines          = [];
        $campaignName   = '';

        if (strpos($fileContent, "\r") > -1) {

            $lines = explode("\r\n", $fileContent);
        } else {

            $lines = explode("\n", $fileContent);
        }


        foreach ($lines as $key => $line) {

            if ($line != "") {

                $lineString = trim($line);

                if ((strpos(strtolower($lineString), 'web 2.0 blogs') > -1) || (strpos(strtolower($lineString), 'social bookmarking') > -1) || (strpos(strtolower($lineString), 'social likes and shares') > -1)) {

                    if ($key == 0)
                        break;

                    $campaignName = preg_replace('/ \(.*\)$/', '', ($lines[$key - 1] == '' && $key > 1 ? $lines[$key - 2] : $lines[$key - 1]));
                    $campaignName = trim($campaignName);
                    break;
                }
            }
        }

        return $campaignName;
    }


    /**
     * Create campaign directory
     *
     * @param String $campaignName
     * @return Void
     */
    private function createCampaignDirectory($campaignName)
    {

        // Begin check and create campaigns dirctory
        $storagePath = Storage::disk('public')->path('campaigns/');

        if (File::isDirectory($storagePath) == false) {
            Storage::disk('public')->makeDirectory('campaigns');
        }
        // End check and create campaigns dirctory


        // Begin check and create campaign dirctory
        $campaignDirectory  = 'campaigns/' . $campaignName;
        $campaignPath       = Storage::disk('public')->path($campaignDirectory);

        if (File::isDirectory($campaignPath) == false) {
            Storage::disk('public')->makeDirectory($campaignDirectory);
        }
        // End check and create campaign dirctory

    }


    /**
     * Store all campaign's links into a .txt file
     *
     * @param String $campaignName
     * @param Array  $links
     * @return Array
     */
    private function prepareCampaignLinksTxtFile($campaignName, $links)
    {

        $fileStoreResult = [
            'path'          => '',
            'fileContent'   => '',
        ];

        // Begin prepare file content
        $fileContent = "";
        foreach ($links as $key => $link) {

            if (($key + 1) == count($links)) { // The last link

                $fileContent .= $link;
            } else {

                $fileContent .= $link . "\r\n";
            }
        }
        // End prepare file content


        $fileStoreResult['path']        = '/campaigns/' . $campaignName . '.txt';;
        $fileStoreResult['fileContent'] = $fileContent;

        return $fileStoreResult;
    }


    /**
     * Store links in a txt file with the campaign name as the file name
     *
     * @param String $campaignName
     * @param String $fileName
     * @param Array  $links
     * @return Array
     */
    private function prepareCampaignAnalyzedLinksTxtFile($campaignName, $fileName, $links)
    {

        $fileStoreResult = [
            'path'          => '',
            'fileContent'   => '',
        ];

        // Begin prepare file content
        $fileContent = "";
        foreach ($links as $key => $link) {

            if (($key + 1) == count($links)) { // The last link

                $fileContent .= $link;
            } else {

                $fileContent .= $link . "\r\n";
            }
        }
        // End prepare file content

        $fileStoreResult['path']        = '/campaigns/' . $fileName . ' Indexing/' . $campaignName . ' ' . $fileName . '.txt';
        $fileStoreResult['fileContent'] = $fileContent;

        return $fileStoreResult;
    }


    /**
     * Store links in a txt file with the campaign name as the file name
     *
     * @param String $campaignName
     * @param String $fileName
     * @param Array  $analyzeResults
     * @return Boolean
     */
    private function prepareCampaignLinksCsvFile($campaignName, $fileName, $analyzeResults)
    {

        $fileStoreResult = [
            'path'          => '',
            'fileContent'   => '',
        ];
        $csvHeader = [
            '#',
            'URL',
            'Status',
            'Found',
            'Anchor Text',
            'Anchor URL',
            'File Size',
            'Date Checked',
        ];
        $csvRows = [];


        // Begin prepare file content
        foreach ($analyzeResults as $key => $analyzeResult) {

            if (count($analyzeResult['anchors']) == 0) {

                $csvRows[] = [
                    $analyzeResult['number'],
                    $analyzeResult['url'],
                    $analyzeResult['status'],
                    'Not Found',
                    '',
                    '',
                    $analyzeResult['file_size'],
                    $analyzeResult['checked_at']
                ];
            } else {

                foreach ($analyzeResult['anchors'] as $key => $anchor) {

                    if ($key == 0) {

                        $csvRows[] = [
                            $analyzeResult['number'],
                            $analyzeResult['url'],
                            $analyzeResult['status'],
                            'Found',
                            $anchor['text'],
                            $anchor['url'],
                            $analyzeResult['file_size'],
                            $analyzeResult['checked_at']
                        ];
                    } else {

                        $csvRows[] = [
                            '',
                            '',
                            '',
                            '',
                            $anchor['text'],
                            $anchor['url'],
                            '',
                            ''
                        ];
                    }
                }
            }
        }


        $fileContent = implode(',', $csvHeader) . "\r\n";
        foreach ($csvRows as $key => $csvRow) {
            $fileContent .= implode(',', $csvRow) . "\r\n";
        }
        // End prepare file content


        $fileStoreResult['path']        = '/campaigns/' . $fileName . ' CSV/' . $campaignName . ' ' . $fileName . '.csv';
        $fileStoreResult['fileContent'] = $fileContent;

        return $fileStoreResult;
    }


    /**
     * Insert campaign into database.
     * The campaign ID will be returned if campaign exist.
     *
     * @param String $campaignName
     * @return Int
     */
    public function insertCampaignToDatabase($campaignName)
    {

        $campaign   = Campaign::where('title', $campaignName)->first();
        $user       = Auth::user();


        if ($campaign == null) {

            $campaign           = new Campaign;
            $ohiBranchState     = OhiBatchState::where('title', 'Not Processed')->first();

            $campaign->title            = $campaignName;
            $campaign->user_id          = $user->id;
            $campaign->ohi_batch_state  = $ohiBranchState->id;
            $campaign->uuid             = guid::generate();
            $campaign->save();
        }

        return $campaign;
    }


    /**
     * Insert campaign links into database.
     *
     * @param Int $campaignId
     * @param Array $campaignNewLinks
     * @return boolean
     */
    public function insertCampaignLinksToDatabase($campaignId, $campaignNewLinks)
    {

        $newLinks = [];
        $campaignLinks = [];

        // Get all campaign links form database
        $campaignLinks = CampaignLink::where('campaign_id', $campaignId)->get();

        $i = 0;
        $dbCounts = [];
        $dbTotal = count($campaignLinks);

        for ($j = 0; $j < $dbTotal; $j++)
            $dbCounts[$j] = strlen($campaignLinks[$j]->url);

        foreach ($campaignNewLinks as $key => $campaignNewLink) {

            $isDuplicate = false;

            $count = strlen($campaignNewLink);
            for ($j = 0; $j < $dbTotal; $j++) {
                $i++;
                if ($count == $dbCounts[$j])
                    if ($campaignNewLink == $campaignLinks[$j]->url) {

                        $isDuplicate = true;
                        break;
                    }
            }

            if ($isDuplicate == false) {

                $newLinks[] = [
                    'campaign_id'   => $campaignId,
                    'url'           => $campaignNewLink
                ];
            }
        }
        // End search links and specify new links that should be added to database


        // Insert new links into the database
        CampaignLink::insert($newLinks);

        OhiStatus::where('completed_at', Carbon::now()->toDateString())->update([
            'for_process' =>  CampaignLink::count()
        ]);
        return true;
    }


    /**
     * Store CSV file from
     *
     * @param Request $request
     * @return JSON
     */
    public function storSCVFile(Request $request)
    {

        $result = [
            'success'   => '',
            'messages'  => [],
            'data'      => []
        ];
        $csvHeader = [
            '#',
            'URL',
            'Status',
            'Found',
            'Anchor Text',
            'Anchor URL',
            'File Size',
            'Date Checked',
        ];
        $fileContent = '';

        // Begin validate inputs
        $inputs = $request->input();
        $validationResult = Validator::make($inputs, [
            'campaign' => ['required', 'string'],
            'csv'       => ['required', 'array']
        ]);

        if ($validationResult->fails() == true) {

            $result['success']  = false;

            $messages = $validationResult->errors();
            $messages = $messages->messages();

            foreach ($messages as $key => $value) {
                $result['messages'][] = $value;
            }

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }
        // End validate inputs


        // Begin preparing file content
        $fileContent = implode(',', $csvHeader) . "\r\n";

        foreach ($inputs['csv'] as $key => $csvRow) {
            $fileContent .= implode(',', $csvRow) . "\r\n";
        }
        // End preparing file content


        // Begin upload file into dropbox
        $campaignName = $inputs['campaign'];
        $path = '/campaigns/Tier 1 CSV/' . $campaignName . ' Tier 1.csv';
        $this->dropboxClient->uploadFile($path, $fileContent);
        // End upload file into dropbox


        // Return response
        $result['success']      = true;
        $result['messages'][]   = 'The csv file has been stored on the Dropbox';
        $result['data']         = [];

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }


    /**
     * Upload source file and process it
     *
     * @param campaignName analyzeResults
     * @return 
     */
    private function storeTempTier1($newTempTier1_id, $analyzeResults)
    {

        foreach ($analyzeResults as $key => $analyzeResult) {

            if (count($analyzeResult['anchors']) == 0) {
                TempTier1Link::create([
                    'url'           =>  $analyzeResult['url'],
                    'status'        =>  $analyzeResult['status'],
                    'anchor_text'   =>  '',
                    'anchor_url'    =>  '',
                    'file_size'     =>  $analyzeResult['file_size'],
                    'processed_at'  =>  $analyzeResult['checked_at'],
                    'temptier1_id'  =>  $newTempTier1_id
                ]);
            } else {
                foreach ($analyzeResult['anchors'] as $key => $anchor) {

                    TempTier1Link::create([
                        'url'           =>  $analyzeResult['url'],
                        'status'        =>  $analyzeResult['status'],
                        'anchor_text'   =>  $anchor['text'],
                        'anchor_url'    =>  $anchor['url'],
                        'file_size'     =>  $analyzeResult['file_size'],
                        'processed_at'  =>  $analyzeResult['checked_at'],
                        'temptier1_id'  =>  $newTempTier1_id
                    ]);
                }
            }
        }

        TempTier1::where('id', $newTempTier1_id)->update([
            'completed_percent' =>  100
        ]);
    }

    /**
     * return temp campaign from database
     *
     * @param Request $request
     * @return JSON
     */
    public function getTempTier1(Request $request)
    {


        $tier1s = TempTier1::select('id', 'title', 'completed_percent', 'created_at')->where('user_id', Auth::user()->uuid)->get();

        // Return response
        $result['success']      = true;
        $result['data']         = $tier1s;

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }


    /**
     * delete temp campain data
     *
     * @param Request $request
     * @return JSON
     */
    public function deleteTempTier1(Request $request)
    {

        TempTier1::where('id', $request->input('id'))->delete();
        TempTier1Link::where('temptier1_id', $request->input('id'))->delete();
        // Return response
        $result['success']      = true;

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    /**
     * add temptier1
     *
     * @param 
     * @return 
     */
    public function addTempTier1(Request $request)
    {
        $response = response(
            json_encode($this->func_addTempTier1($request->input('anchor_url'), $request->input('plain_text'))),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    public function func_addTempTier1($anchor_url, $fileContent)
    {
        $user           = Auth::user();
        $campaignName = $this->getCampaignName($fileContent);

        $campaignNameLowercase  = strtolower($campaignName);

        if ((strpos($campaignNameLowercase, 'tier2') > -1)
            || (strpos($campaignNameLowercase, 'tier 2') > -1)
            || (strpos($campaignNameLowercase, 'tier  2') > -1)
        ) {

            $result['success']      = false;
            $result['validate']     = true;
            $result['messages'][]   = 'Sent Links to OHI. Input other source to process!';

            return $result;
        }

        if ($this->validateSourceFormat($fileContent) == false || ($campaignNameLowercase == '')) {

            $result['success']      = false;
            $result['validate']     = false;
            $result['messages'][]   = 'Please send a valid Source!';

            return $result;
        }

        $newTempTier1   = TempTier1::create([
            'user_id'           =>  $user->uuid,
            'title'             =>  $campaignName,
            'anchor_url'        =>  $anchor_url,
            'text'              =>  $fileContent,
            'completed_percent' =>  0
        ]);

        $result['success'] = true;
        $result['validate'] = true;
        $result['id'] = $newTempTier1->id;
        $result['campaign_name'] = $newTempTier1->title;

        return $result;
    }

    /**
     * get temp-tier1-links temptier1
     *
     * @param 
     * @return 
     */
    public function getTempTier1Link(Request $request)
    {

        $tier1s = TempTier1Link::where('temptier1_id', $request->input('id'))->get();

        $data = [];
        foreach ($tier1s as $key => $tier1) {
            $data[] = [
                'id'            =>  $tier1['id'],
                'url'           =>  $tier1['url'],
                'status'        =>  $tier1['status'],
                'anchor_text'   =>  $tier1['anchor_text'],
                'anchor_url'    =>  $tier1['anchor_url'],
                'file_size'     =>  $tier1['file_size'],
                'processed_at'  =>  $tier1['processed_at'],
                'temptier1_id'  =>  $tier1['temptier1_id']
            ];
        }

        // Return response
        $result['success']      = true;
        $result['data']         = $data;

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }


    /**
     * delete temp-tier1-links temptier1
     *
     * @param 
     * @return 
     */
    public function deleteTempTier1Link(Request $request)
    {

        TempTier1Link::whereIn('id', $request->input('ids'))->delete();

        // Return response
        $result['success']      = true;
        $result['message']      = 'success';

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }
}
