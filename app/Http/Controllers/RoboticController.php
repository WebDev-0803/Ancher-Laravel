<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Mruser;
use App\Client;
use App\Mrdropdown;
use App\Mrcampaign;
use KubAT\PhpSimple\HtmlDomParser;

class RoboticController extends Controller
{
    //
    /**
     * Display Robotic view
     *
     * @return View
     */
    public function index()
    {

        return view('pages.clientarea.robotic', [
            'user'          => Auth::user(),
            'breadcrumb'    => [
                'Home'          => route('dashboard.index'),
                'Robotic'       => ''
            ]
        ]);
    }

    //
    /**
     * Scraping Robotic view
     *
     * @return View
     */
    public function test()
    {
        $handle = curl_init();

        $url = "https://www.moneyrobot.com/campaign-manager/?modifica=new";

        $header = array(
            'Cookie:__cfduid=d0cba3513af9384d6aeb7ebd8fa8bdb3f1573629163; Le=carlstarus@gmail.com; Le_cod=3ed744b716e398451fde452532a98df0; security=2159774509dd57901f79581cfc184b3b; security_pass=2a9aa6b29446e98246a09d73a5d31429'

        );

        $curl_data = array(
            'field_MONEY_SITE_URLS' => 'e',
            'field_PROFILE_NAME' => ' BlogDude',
            'field_DIAGRAM_NAME' => '(4 tiers)  2 > 2x6 > 2x7 >1 social',
            'field_ACCOUNTS' => 'Create new Accounts',
            'field_LINKS_PER_ARTICLE' => ' 3 links per article',
            'field_ACCOUNTS_CATEGORY' => 'Uncategorized',
            'field_KEYWORDS' => 'f',
            'field_GENERIC_KEYWORDS' => 'a',
            'field_ARTICLE_TITLE' => 'g',
            'field_ARTICLE_BODY' => 'h',
            'field_BLOG_TITLE' => 'b',
            'field_BLOG_ADDRESS' => 'c',
            'field_VIDEO_URLS' => 'd',
            'field_HTML_CONTENT' => '',
            'field_CAMPAIGN_NAME' => 'Campaign 11111',
            'field_RUN_TIMES' => 0,
            'btn_save' => 'Add'
        );
        curl_setopt_array(
            $handle,
            array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER     => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS     => http_build_query($curl_data)
            )
        );

        $data = curl_exec($handle);
        curl_close($handle);
        echo  $data;
    }

    public function register(Request $request)
    {        
        $isDuplicate = Mruser::where('email', $request->input('email'))->get()->count();
        if ($isDuplicate > 0) {

            $result['success']      = false;
            $result['messages']   = "Register Failed! Client already exists!";

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }

        //Scraping dropdown data
        if ($this->scrapeDropdownData($request)) {

            $result['success']      = true;

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        } else {

            $result['success']      = false;
            $result['messages']     = 'Access MoneyRobot Failed! Input valid MoneyRobot account.';

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }
    }

    private function scrapeDropdownData($request)
    {
        $user = $request->input('email');
        $pwd = $request->input('password');


        $handle = curl_init();

        $url = "https://www.moneyrobot.com/campaign-manager/";

        $post_data = array(
                'email_login' => $user,
                'parola_login' => $pwd,
                'tinemaminte' => 'X',
                'btn_login' => 'Login'
                );

        curl_setopt_array($handle,
        array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER     => true,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_SSL_VERIFYPEER => 0,
            )
        );
        
        $data = curl_exec($handle);
        curl_close($handle);

        //To get le_code
        $codeTemp = explode('createCookie("Le_cod","',$data);

        $le_code_ary = explode('","',$codeTemp[1]);
        $le_code = $le_code_ary[0];

        //To get security
        $codeTemp = explode('createCookie("security","',$data);

        $security_ary = explode('","',$codeTemp[1]);
        $security = $security_ary[0];

        //To get security_pass
        $codeTemp = explode('createCookie("security_pass","',$data);

        $security_pass_ary = explode('","',$codeTemp[1]);
        $security_pass = $security_pass_ary[0];

        $handle = curl_init();
        $security = 'Cookie:security='.$security.'; security_pass='.$security_pass.'; Le='.$user.'; Le_cod='.$le_code;

        $url = "https://www.moneyrobot.com/campaign-manager/?modifica=new";

        $header = array(
            $security
        );

        curl_setopt_array(
            $handle,
            array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER     => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_SSL_VERIFYPEER => 0,
            )
        );

        $data = curl_exec($handle);

        curl_close($handle);

        $dropdowns = [];
        foreach (HTMLDomParser::str_get_html($data)->find('select') as $dropdown) {
            $item = [];
            foreach ($dropdown->find('option') as $option) {
                $item[] = $option->plaintext;
            }
            $dropdowns[] = $item;
        }

        if (count($dropdowns) != 5)
            return false;

        $newClient = Mruser::create([
            'email'     => $request->input('email'),
            'password'     => $request->input('password'),
            'security'      => $security
        ]);

        $id = $newClient['id'];
        for ($i = 0; $i < count($dropdowns); $i++) {
            for ($j = 0; $j < count($dropdowns[$i]); $j++) {
                Mrdropdown::create([
                    'mruser_id' => $id,
                    'category'  => $i,
                    'option'    => $dropdowns[$i][$j]
                ]);
            }
        }

        return true;
    }


    // get dropdown and return
    public function loginMr(Request $request)
    {
        $hasId = true;
        if ($request->input('id') == 'NULL') {
            $hasId = false;
            $user = Mruser::where([
                ['email', $request->input('email')],
                ['password', $request->input('password')]
            ])->get()->count();

            if ($user < 1) {

                $result['success']      = false;
                $result['messages']         = 'Wrong email or password';
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
        
        // 5 : category count
        $dropdowns = [];

        for ($i = 0; $i < 5; $i++) {

            $rows = Mrdropdown::where([
                ['mruser_id', $user->id],
                ['category', $i]
            ])->get();

            $options = [];
            foreach ($rows as $key => $row) {
                $options[] = $row->option;
            }
            $dropdowns[$i] = $options;
        }

        $clients = Client::all();
        foreach($clients as $client) {
            $count = Mrcampaign::where('client', $client->uuid)->count();
            $client->title = $client->title.'   -   '.$count;
        }

        $result['success']      = true;
        $result['dropdowns']    = $dropdowns;
        $result['id']           = $user->id;
        $result['clients']      = $clients;

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    // save moneyrobot campagin
    public function saveMrCampaign(Request $request)
    {
        $data = [];
        $isMr = $request->input('ismr');
        $id = $request->input('mrid');
        $data = $request->input('data');
        $status = $request->input('status');
        
        if ($status=='new') {//Add new
            if (Mrcampaign::where('name', $data[14])->get()->count() > 0) {
                $result['success']      = false;
                $result['messages']     = 'Failed: Campaign name already exist!';

                $response = response(
                    json_encode($result),
                    200
                )
                    ->header('Content-Type', 'application/json');
                return $response;
            }
        } else {//update 
            $exist = Mrcampaign::where('name', $data[14])->first();
            if ($exist && ($exist->id != $status)) {
            
                $result['success']      = false;
                $result['messages']     = 'Failed: Campaign name already exist!';

                $response = response(
                    json_encode($result),
                    200
                )
                    ->header('Content-Type', 'application/json');
                return $response;
            }
        }

        $campaign = new Mrcampaign();
        $campaign->mruser_id = $id;
        $campaign->site_url = $data[0];
        $campaign->profile = $data[1];
        $campaign->diagram = $data[2];
        $campaign->accounts = $data[3];
        $campaign->link_article = $data[4];
        $campaign->category = $data[5];
        $campaign->keywords = $data[6];
        $campaign->generic = $data[7];
        $campaign->title = $data[8];
        $campaign->body = $data[9];
        $campaign->builder = $data[10];
        $campaign->blog_title = $data[11];
        $campaign->blog_addr = $data[12];
        $campaign->youtube_url = $data[13];
        $campaign->name = $data[14];
        $campaign->run = $data[15];
        $campaign->priority = $data[16];
        $campaign->client = $data[17];
        $campaign->bulk = $isMr;//Moneyrobot or 301
        $campaign->body_files = 0;

        if ($status != 'new') {
            Mrcampaign::where('id', $status)->delete();
            $campaign->id = $status;

            $filecount = 0;
            $files2 = glob( public_path('images/'.$campaign->id) ."/*" ); 
            
            if( $files2 ) { 
                $filecount = count($files2); 
            }

            $campaign->body_files = $filecount;
        }

        $campaign->save();

        $data['id'] = $campaign->id;
        $data['name'] = $campaign->name;
        $data['body_files'] = $campaign->body_files;
        $data['bulk'] = $campaign->bulk;

        $result['success']      = true;
        $result['data'] = $data;

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    //delete Money Robot camapaign
    public function deleteMrCampaign(Request $request)
    {
        $id = $request->input('campaign_id');

        Mrcampaign::where('id', $id)->delete();        
        
        if (is_dir(public_path('images/'.$id))) {
            array_map('unlink', glob(public_path('images/'.$id).'/*.*'));
            rmdir(public_path('images/'.$id));
        }

        $result['success']      = true;

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    //Run
    public function run(Request $request)
    {
        $result = [];
        $totalSent = 0;

        $mr = Mrcampaign::where('id', $request->input('id'))->first();
        $security = Mruser::where('id', $request->input('mr_userid'))->first()->security;
        if (!$mr) {
            $result['success']  = true;
            $result['message']  = "Data doesn't exist.";
            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }

        $curl_data = array(            
            'field_PROFILE_NAME' => $mr->profile,
            'field_DIAGRAM_NAME' => $mr->diagram,
            'field_ACCOUNTS' => $mr->accounts,
            'field_LINKS_PER_ARTICLE' => $mr->link_article,
            'field_ACCOUNTS_CATEGORY' => $mr->category,
            'field_KEYWORDS' => $mr->keywords,
            'field_GENERIC_KEYWORDS' => $mr->generic,
            'field_BLOG_TITLE' => $mr->blog_title,
            'field_BLOG_ADDRESS' => $mr->blog_addr,
            'field_VIDEO_URLS' => $mr->youtube_url,
            'field_HTML_CONTENT' => '',
            'field_RUN_TIMES' => $mr->run,            
            'btn_salveaza' => 'Add'
        );

        if ($mr->priority == '1') {
            $curl_data['field_PRIORITY'] = 'Yes';
        }

        $urls = $mr->site_url;
        $articles = [];
        $totalCount = 0;

        if ($mr->bulk == "false" && (!$mr->builder)) {            
            $articles = $this->getArticleFromFile($mr->id, $request->input('repeatTimes'));        
            $totalCount = min(intval($request->input('repeatTimes')), count($articles), substr_count( $urls, "\n" )+1);
        } else {
            $totalCount = $request->input('repeatTimes');
        }        

        for ($i = 0; $i < $totalCount; $i++) {
            $moneyUrl = strtok($urls, "\n");
            if (substr_count( $urls, "\n" ) == 0)
                $urls="";
            $urls = preg_replace('/^.+\n/', '', $urls);
            
            $curl_data['field_CAMPAIGN_NAME'] = $mr->name .' '. bin2hex(random_bytes(6));
            $curl_data['field_MONEY_SITE_URLS'] = $moneyUrl;
            
            if ($mr->builder) {

                $curl_data['field_GENERATE_ARTICLE'] = 'Yes';

            } else {

                if ($mr->bulk == "false") { //is not 301 url
                    $curl_data['field_ARTICLE_TITLE']=$articles[$i]['title'];
                    $curl_data['field_ARTICLE_BODY']=$articles[$i]['content'];
                } else {
                    $curl_data['field_ARTICLE_TITLE']=$mr->title;
                    $curl_data['field_ARTICLE_BODY']=$mr->body;
                }
            }

            $handle = curl_init();

            $url = "https://www.moneyrobot.com/campaign-manager/?modifica=new";

            $header = array(
                $security
            );

            curl_setopt_array(
                $handle,
                array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER     => true,
                    CURLOPT_HTTPHEADER => $header,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS     => http_build_query($curl_data)
                )
            );

            $data = curl_exec($handle);
            curl_close($handle);
            
            $totalSent++;

            if(strlen($urls)==0)
                break;
        }

        $filecount = 0;
        $files2 = glob( public_path('images/'.$mr->id) ."/*" ); 
        
        if( $files2 ) { 
            $filecount = count($files2); 
        }

        //Reset values pck
        Mrcampaign::where('id', $mr->id)->update([
            'site_url' => $urls,
            'body_files' => $filecount
        ]);
        //End Reset values        

        $result['success']      = true;
        $result['totalSent'] = $totalSent;
        $result['body'] = $filecount;

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }


    private function getArticleFromFile($id, $count) {
        $articles = [];
        $files = glob(public_path('images/'.$id) ."/*.txt");

        $c = 0;
        foreach($files as $file) {
            $info = [];

            $f = file($file);
            $info['title'] = $f[0];

            unset($f[0]);
            file_put_contents($file, $f);
            $info['content']= file_get_contents($file);

            $articles[] = $info;

            //Remove file
            unlink($file);

            $c++;
            if ($c >= $count)
                break;            
        }

        return $articles;
    }


    public function getCampaignInfo(Request $request)
    {
        $campaign = Mrcampaign::where('id', $request->input('id'))->first();        
        
        $result = [];
        $results = [];
        
        $results[0] = $campaign->site_url;
        $results[1] = $campaign->profile;
        $results[2] = $campaign->diagram;
        $results[3] = $campaign->accounts;
        $results[4] = $campaign->link_article;
        $results[5] = $campaign->category;
        $results[6] = $campaign->keywords;
        $results[7] = $campaign->generic;
        $results[8] = $campaign->title;
        $results[9] = $campaign->body;
        $results[10] = $campaign->builder;
        $results[11] = $campaign->blog_title;
        $results[12] = $campaign->blog_addr;
        $results[13] = $campaign->youtube_url;
        $results[14] = $campaign->name;
        $results[15] = $campaign->run;
        $results[16] = $campaign->priority;
        $results[17] = $campaign->client;
        $results[18] = $campaign->bulk; //is moneyrobot url or 301 url
                
        $result['success']  = true;
        $result['message']  = "Data doesn't exist.";
        $result['data'] = $results;

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    public function uploadBodyFiles(Request $request)
    {
        $files = $request->file('file');
        if (!$files) {
            $result['success']  = false;
            $result['message']  = "Please Choose Files.";

            $response = response(
                json_encode($result),
                200
            )
                ->header('Content-Type', 'application/json');

            return $response;
        }

        $id = $request->mrCampaignId;
        foreach($files as $file) {
            $file->move(public_path('images/'.$id), rand().".".$file->getClientOriginalExtension());            
        }

        $filecount = 0;
        $files2 = glob( public_path('images/'.$id) ."/*" ); 
        
        if( $files2 ) { 
            $filecount = count($files2); 
        }

        $result['count'] = $filecount;
        Mrcampaign::where('id', $id)->update([
            'body_files' => $filecount
        ]);

        $result['success']  = true;
        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }

    public function getMrCampagins(Request $request)
    {
        $clientId = $request->input('clientId');
        $mr_userId = $request->input('mr_userId');

        $mrCampaigns = Mrcampaign::select('id', 'name', 'body_files', 'bulk')
            ->where([
                ['mruser_id', $mr_userId],
                ['client', $clientId]
            ])->get();

        $result['success']      = true;
        $result['mrCampaigns']  = $mrCampaigns;

        $response = response(
            json_encode($result),
            200
        )
            ->header('Content-Type', 'application/json');

        return $response;
    }
}
