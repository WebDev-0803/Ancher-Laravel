<?php

namespace App\Http\Controllers;

use App\OhiStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    
    /**
     * Display dashboard home page
     * 
     * @return View
     */
    public function index() {

        $user = Auth::user();

        return view('pages.clientarea.dashborad', [
            'user'          => $user,
            'breadcrumb'    => [
                                    'Home'      => ''
                                ]
        ]);
        
    }


    /**
     * return ohi status for chart
     * 
     * @return ohi
     */
    public function getOhiStatus(Request $request) {

        $ohis = OhiStatus::orderBy('completed_at', 'DESC')->limit(8)->get();

        $result = array (
            'success'   => true,
            'messages'  => array('Get Ohi status successfully'),
            'ohis'      => $ohis
        );

        $response = response(
                        json_encode($result),
                        200
                    )
                    ->header('Content-Type', 'application/json');

        return $response;
    }

}
