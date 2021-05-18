<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mruser;
use App\Mrstatus;
use KubAT\PhpSimple\HtmlDomParser;

class MoneyRobotToOhi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:mr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check MoneyRobot Status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $this->getMrInfo();
    }

    private function getMrInfo()
    {
        
        $mrUsers = Mruser::all();        

        foreach ($mrUsers as $uKey => $user) {
            $this->scrapingRobot($user);
        }
      
    }

    private function scrapingRobot($user)
    {
        $nr_page = 1;
        $noData = false;
      
        $processedId = [];

        while (!$noData) {
            $campaigns = [];
            $handle = curl_init();
            $url = "https://www.moneyrobot.com/campaign-manager/index.php?nr_pag=" . ($nr_page++);

            $header = array(
                $user->security
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

            if (!HTMLDomParser::str_get_html($data)->find('.row_comanda')) {
                $noData = false;
                break;
            }

            foreach (HTMLDomParser::str_get_html($data)->find('.row_comanda') as $campaign) {
                $product = array();
                $product['mr_id'] = $campaign->id;
                $processedId [] = $product['mr_id'];

                $sameData = Mrstatus::where([
                    ['mr_userId', $user->id],
                    ['mr_id', $product['mr_id']],
                ])->first();

                if($sameData && 
                    (($sameData->status == 'Processed') || (strpos($sameData->status, '(') > 0)))  {}
                else {

                    $product['name'] = $campaign->find('div')[1]->plaintext;
                    $pos = strpos($product['name'], 'http');
                    $product['anchor_url'] = substr($product['name'], $pos, strlen($product['name']) - $pos);
                    $product['name'] = substr($product['name'], 0, $pos - 2);
                    $product['status'] = $this->getCampaignStatus($campaign->id, $product['name'], $header);
                    $product['progress'] = 0;

                    $sameData2 = Mrstatus::where([
                        ['mr_userId', $user->id],
                        ['mr_id', $product['mr_id']],
                    ])->first();

                    if($sameData2 && 
                        (($sameData2->status == 'Processed') || (strpos($sameData2->status, '(') > 0))) {}
                    else {
                        
                        if ($sameData2) {
                            $sameData2->status = $product['status'];
                            $sameData2->save();
                        } else {                        
                            Mrstatus::create([
                                'mr_userId' =>      $user->id,
                                'mr_id'     =>      $product['mr_id'],
                                'name'      =>      $product['name'],
                                'status'    =>      $product['status'],
                                'anchor_url'    =>      $product['anchor_url'],
                                'progress'    =>     $product['progress'],
                            ]);
                        }
                    }
                }
            }
        }

        Mrstatus::whereNotIn('mr_id', $processedId)->where('mr_userId', $user->id)->delete();
    }

    private function getCampaignStatus($id, $campaignName, $header)
    {
        $handle = curl_init();
        $campaignName = str_replace(' ', '%20', $campaignName);
        $url = "https://www.moneyrobot.com/campaign-manager/view/?campaign-name=" . $campaignName;

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

        $isFinished = strpos($data, 'Resubmission Task') > 0 || strpos($data, '>Running</td>') > 0 ? false : true;

        preg_match('/Running (.*?) campaigns/', $data, $match);
        if (count($match) < 2)
            return 'Unknown Error!';
        $array = explode('/', $match[1]);
        if (intval($array[1]) == 0)
            return 'Pending';
        else if ((intval($array[0]) == 0) && $isFinished)
            return $this->getCompletedLinks($id, $header);
        return "Processing";
    }

    private function getCompletedLinks($id, $header)
    {
        $handle = curl_init();

        $url = "https://www.moneyrobot.com/campaign-manager/export_successfully_links.php";

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
        $length = substr_count($text, 'http:/') + substr_count($text, 'https:/');
        if ($length == 0)
            return "Error Length=0";
        return "Completed (".$length.")";
    }
}