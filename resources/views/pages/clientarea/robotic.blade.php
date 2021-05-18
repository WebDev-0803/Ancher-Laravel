@extends('masters.clientarea')

@section('ExtraStylesheets')
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
@endsection

@section('content')
{{csrf_field()}}
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
                    <!-- <button type="button" id="registerMr" class="primary ui button">Register</button> -->
                </div>
            </div>
        </div>
    </form>
</div>


<div class="ui container segment ui" id="previewCampaignField">
    <h3>Customer / Project</h3>
    <div class="m-2">
        <select id="fieldClient" name="field_19" class="fieldClient field_mic" style="float:none;">
        </select>
    </div>
    
    <div style="clear:both"></div>

    <div class="ui container ui hidden" id="campaignLists">

    </div>
</div>


<div class="ui text container" style="border:none;">
    <button type="button" id="addNewCampaign" class="primary ui button disabled">Add New Campaign</button>    
</div>

<div class="ui text container segment ui" id="newCampaignField" campaign-id="new" style="display:none;">

    <div class="etichete" style="border:0">
        <div>
            <label>Client:</label>
            <select id="fieldClientDropdown" name="field_17" class="fieldClient field_mic">
            </select>
        </div>

        <div style="clear:both"></div>

        <div style="float:left">
            <div class="mr-check">
                <div class="ui form">
                    <div class="grouped fields">                        
                        <div class="field" style="margin:0;">
                            <div class="ui radio checkbox">
                                <input type="radio" name="urlOption" class="radio_mr_301" id="setMrUrl"><br>
                                <p>MoneyRobot Url:</p>
                            </div>
                        </div>
                        <div class="field" style="margin:0;">
                            <div class="ui radio checkbox">
                                <input type="radio" name="urlOption"  class="radio_mr_301" id="set301Url" checked="checked"><br>
                                <p>301 URLs:</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="height:128px; width:326px; display:inline-block">
                <textarea name="field_0" class="field_mic" style="height:128px;"></textarea>
            </div>
        </div>

        <div style="float:left">
            <label>&nbsp;&nbsp; Profile:</label>
            <select name="field_1" class="field_mic">
            </select>
        </div>

        <div style="float:left">
            <label>&nbsp;&nbsp; Diagram:</label>
            <select name="field_2" class="field_mic">
            </select>
        </div>

        <div style="float:left">
            <label>&nbsp;&nbsp; Accounts:</label>
            <select name="field_3" class="field_mic">
            </select>
        </div>

        <div style="float:left">
            <label>&nbsp;&nbsp; link/article:</label>
            <select name="field_4" class="field_mic">
            </select>
        </div>

        <div style="float:left">
            <label>&nbsp;&nbsp; Category:</label>
            <select name="field_5" class="field_mic">
            </select>
        </div>

        <div style="clear:both"></div>

        <div style="clear:both; padding-top:10px; padding-bottom:0px;"><b>Keywords</b>:
        </div>

        <div style="float:left">
            <label>Keywords<br>to rank for:</label>
            <textarea name="field_6" class="field_mic"></textarea>
        </div>

        <div style="float:left">
            <label>&nbsp; &nbsp;Generic &nbsp;&nbsp;Keywords:</label>
            <textarea name="field_7" class="field_mic"></textarea>
        </div>

        <div style="clear:both; padding-top:10px; padding-bottom:0px;"><b>Article</b>:</div>

        <div style="float:left;">
            <label>Title:</label>
            <input name="field_8" class="field_mic" type="text" value="" >

            <div style="clear:both"></div>

            <label>Body:</label>
            <textarea name="field_9" class="field_mic" type="text" value="0"></textarea>

            <div style="clear:both"></div>
            <label>&nbsp;</label>
            <label for="generate_article" style="width:auto;">Automatically build article using MR article builder&nbsp; </label>
            <input id="generate_article" style="float:left; padding-left:0px; margin-left:0px;" name="field_10" type="checkbox" value="Yes">
        </div>

        <div style="float:left">
            <label>&nbsp;&nbsp; Blog Title:</label>
            <input name="field_11" class="field_mic" type="text" value="">
        </div>
        <div style="float:left"> <label>&nbsp;&nbsp; Blog Addr.:</label>
            <input name="field_12" class="field_mic" type="text" value="">
        </div>

        <div style="float:left">
            <label>&nbsp;&nbsp; Youtube<br> &nbsp;&nbsp; URLs:</label>
            <textarea name="field_13" class="field_mic"></textarea>
        </div>

        <div style="clear:both; line-height:10px;">&nbsp;</div>
        <label><b>Name</b>:</label>
        <input name="field_14" class="field_mic" type="text" value="Campaign 1" maxlength="50" placeholder="Campaign Name">


        <label style="width:auto; margin-left:10px">Run&nbsp;</label>
        <input name="field_15" class="field_mic" style="width:30px; padding-left:2px" type="text" value="1">
        <label style="width:auto;">&nbsp;times</label>

        <label for="priority" style="width:auto;"> &nbsp;&nbsp;&nbsp; Priority: &nbsp; </label>
        <input style="float:left; padding-left:0px; margin-left:0px;" id="priority" name="field_16" type="checkbox" value="Yes">

        <div style="position:relative; top:-8px">
            <input name="btn_save" id="btn_save" type="button" class="ui primary button" value="Save">
        </div>

    </div>
</div>


{{-- Begin Register MoneyRobot User modal --}}
<div class="ui tiny modal" id="registermrModal">
    <div class="header">
        <span>Register MoneyRobot User</span>
    </div>
    <div class="content">

        <form class="ui form" method="POST" id="form_registermr" action="{{ route('do.roboticRegister') }}">

            <div class="two fields">
                <div class="field">
                    <label>Email</label>
                    <div class="ui left input">
                        <input type="email" id="mrEmail" name="mrEmail" placeholder="email" required>
                    </div>
                </div>
                <div class="field">
                    <label>Password</label>
                    <div class="ui left input">
                        <input type="password" id="mrPassword" name="mrPassword" placeholder="Password" required>
                    </div>
                </div>
            </div>
        </form>

        <div class="ui hidden message">
            <div class="header"></div>
            <div class="content"></div>
        </div>

    </div>
    <div class="actions">
        <div class="ui black deny button">
            <span>Cancel</span>
        </div>
        <div class="ui positive  button">
            <span>Register</span>
        </div>
    </div>

</div>
{{-- End Register MoneyRobot User modal --}}

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
<!-- 
{{-- Begin confirm delete campaign modal --}}
<div class="ui tiny modal" id="delete-mrCampaign-modal" action="{{ route('do.deleteMrCampaign') }}">
    <div class="header">
        <span>Delete Campaign</span>
    </div>
    <div class="content">
        <p>Are you going to delete current Campaign?</p>
    </div>
    <div class="actions">
        <div class="ui black deny button">
            <span>No</span>
        </div>
        <div class="ui positive left labeled icon button">
            <span>Yes</span>
            <i class="trash alternate outline icon"></i>
        </div>
    </div>
</div>
{{-- End confirm delete campaign modal --}} -->

@endsection

@section('ExtraJavascript')
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<script type="text/javascript" src="{{ asset('assets/js/robotic.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.form.js') }}"></script>
@endsection