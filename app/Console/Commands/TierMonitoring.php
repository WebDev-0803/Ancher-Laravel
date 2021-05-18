<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AnchorController;
use App\Tier1;
use App\Tier2;

class TierMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:tiers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check tiers';

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

        $this->monitoringTier1();
        $this->monitoringTier2();
    }

    public function getStatus($targetAnchorUrl, $links, $anchorText, $timeout)
    {

        $anchor = new AnchorController();

        $analyzeResults =  $anchor->analyzeUploadedFileLinks($targetAnchorUrl, $links, $timeout);

        if ($analyzeResults[0]['status'] == "Success") {

            if ($analyzeResults[0]['anchors'] == [])
                return "No Link";
            else {
                $result = $analyzeResults[0]['anchors'][0];

                if ($result['text'] == $anchorText) {
                    return "Success";
                } else if (strpos(strtolower($result['text']), strtolower($anchorText))) {
                    return "Success";
                } else {
                    if (strpos(strtolower($analyzeResults[0]['anchors'][0]['anchor']), strtolower($anchorText))) {
                        return "Success";
                    }
                    return "Different AnchorText";
                }
            }
        } else {

            return $analyzeResults[0]['status'];
        }
    }

    private function monitoringTier2()
    {

        $tier2s = Tier2::all();

        foreach ($tier2s as $key => $tier2) {

            $targetAnchorUrl = $tier2->tier1_link_id; //bad name

            $links[0] = $tier2->tier2_link;

            $status = $this->getStatus($targetAnchorUrl, $links, $tier2->anchor_text, true);

            $tier2->update([
                'status' => $status
            ]);
        }
    }

    private function monitoringTier1()
    {

        $tier1s = Tier1::all();

        foreach ($tier1s as $key => $tier1) {

            $links[0] = $tier1->tier1_link;

            if ($tier1->emUrl == '') {
                $targetAnchorUrl = $tier1->target_url;
            } else {
                $targetAnchorUrl = $tier1->emUrl;
            }

            $status = $this->getStatus($targetAnchorUrl, $links, $tier1->anchor_text);
            $tier1->update([
                'status' =>  $status
            ]);
        }
    }
}
