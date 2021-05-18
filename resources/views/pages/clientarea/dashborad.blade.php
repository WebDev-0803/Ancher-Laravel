@extends('masters.clientarea')

@section('content')
    <div class="ui middle aligned tow column centered grid">
        <div class="row">
            <div class="column">
                <div class="ui">
                    <pre>
                    {{-- {{ print_r($user) }} --}}
                    </pre>
                </div>
            </div>
        </div>
    </div>

    <div class="ui segment">
        <div class="x_panel">
            <div class="x_title">
                <h3>OHI Processing Status</h3>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div id="follower_chart" style="height:350px;"></div>
            </div>
        </div>  
    </div>

@endsection

@section('ExtraJavascript')

<script type="text/javascript" src="{{ asset('assets/js/echarts.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/dashboard.js') }}"></script>    

@endsection