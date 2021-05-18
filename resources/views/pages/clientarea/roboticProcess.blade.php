@extends('masters.clientarea')

@section('ExtraStylesheets')
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/v/dt/dt-1.10.16/sl-1.2.5/datatables.min.css">
<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
@endsection

@section('content')
<div class="ui text container segment login-field" style="display:none;">
    <form class="ui form">
        <div class="field">
            <label>Email & Password</label>
            <div class="three fields">
                <div class="field">
                    <input type="text" id="email" name="email" placeholder="Email">
                </div>
                <div class="field">
                    <input type="password" id="password" name="password" placeholder="Password">
                </div>
                <div class="field">
                    <button type="button" id="loginMr" class="primary ui button">Login</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="progress" id="progressBar" style="margin-bottom:10px; display:none;">
  <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">0%</div>
</div>

<div class="ui">
    <div class="ui tiny button labled red" id="deleteMrCamapign" style="float:right;margin-bottom: 10px;" data-toggle="modal" data-target="#delete-mrCampaign-modal">
        Delete
    </div>
    <div class="ui tiny button green disabled" id="processAll" style="float:right;margin-bottom: 10px;">
        Process
    </div>
    <div class="ui tiny button yellow disabled" id="retryProcess" style="float:right;margin-bottom: 10px;">
        Retry
    </div>
</div>

<table class="table table-striped table-bordered" id="campaignlists" style="width:100%">
    <thead>
        <tr>
            <th style="width:10px"></th>
            <th style="width:10px">No</th>
            <th>Campaign Name</th>
            <th>Anchor Url</th>
            <th>Status</th>
            <!-- <th class="width-112">Actions</th> -->
        </tr>
    </thead>
    <tbody id="campaignListTbody">

    </tbody>
</table>


{{-- Begin delete modal --}}
<div class="modal fade" id="delete-mrCampaign-modal" tabindex="0" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title title-weight" id="exampleModalLabel">Delete Campaign on MoneyRobot</h5>
            </div>
            <div class="modal-body">
                <form id="form-delete-campaign" method="POST" action="{{ route('do.deleteMrCampaignItem') }}">

                    <div class="form-group">
                        <p>Are you going to delete Campaigns?</p>
                        <div class="ui hidden message">
                            <div class="header"></div>
                            <div class="content"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary but-width">Delete</button>
                        <hr>
                        <button type="button" class="btn btn-secondary but-width" data-dismiss="modal">Close</button>
                    </div>
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
{{-- End delete modal --}}


@endsection

@section('ExtraJavascript')
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<!-- <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js"></script> -->

<script src="https://cdn.datatables.net/v/dt/dt-1.10.16/sl-1.2.5/datatables.min.js"></script>
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>

<script type="text/javascript" src="{{ asset('assets/js/robotic-process.js') }}"></script>
@endsection