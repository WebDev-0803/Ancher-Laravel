@extends('masters.clientarea')

@section('content')

@section('ExtraStylesheets')
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css">
@endsection

<div class="bs-example">
    
    <select id="clientOption" class="form-control" style="display: none;">
        <option value="" selected >All Clients</option>
        @foreach ( $client as $clients)
            <option name="clientIdEdit">{{ $clients['client_id']}}</option>
        @endforeach
    </select>
    
    <table class="table table-striped" id="tier2">
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Provider</th>
                <th>Tier 1 Link</th>
                <th>Anchor Text</th>
                <th>Tier 2 Link</th>
                <th>Status</th>
                <th class="width-112">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ( $tier2 as $key=>$tiers2)
            <tr data-tier2-id="{{ $tiers2['id'] }}">
                <td class="counterCell"></td>
                <td>{{$tiers2['client_id']}}</td>
                <td>{{$tiers2['provider_id']}}</td>
                <td>{{$tiers2['tier1_link_id']}}</td>
                <td>{{$tiers2['anchor_text']}}</td>
                <td>{{$tiers2['tier2_link']}}</td>
                <td>{{$tiers2['status']}}</td>
                <td class="buttons-group">
                    <div class="right floated content">
                        <div class="ui tiny icon button green show-retry-tier2-modal" data-toggle="modal" data-target="#retry-tier2-modal">
                            <i class="sync icon"></i>
                        </div>
                        <div class="ui tiny icon button teal show-edit-tier2-modal" data-toggle="modal" data-target="#editTier2">
                            <i class="edit icon"></i>
                        </div>
                        <div class="ui tiny icon button red show-delete-tier2-modal" data-toggle="modal" data-target="#delete-tier2-modal">
                            <i class="trash alternate outline icon"></i>
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Begin tier2 modal --}}
    <div class="modal fade" id="tier2Modal" tabindex="0" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title-weight" id="exampleModalLabel">Add Tier 2 Link</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="form-add-Tier2" method="POST" action="{{ route('do.addTier2') }}">
                        <div class="form-group">
                            <label for="exampleFormControlSelect1" class="title-weight">Select Clients</label>
                            <select class="form-control" id="clientId" name="clientId" selected="disabled" required>
                                <option id="sel_client" selected disabled hidden>Select Clients</option>
                                @foreach ( $client as $clients)
                                    <option>{{$clients['client_id']}}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">

                        <div class="form-group">
                            <label for="exampleFormControlSelect1" class="title-weight">Select Providers</label>
                            <select class="form-control" id="providerId" name="providerId" selected="disabled" required>
                                <option selected disabled hidden>Select Providers</option>
                                <option></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="exampleFormControlInput1" class="title-weight">Tier 2 Link</label>
                            <input type="text" class="form-control" id="tier2Link" name="tier2Link" placeholder="http://example_Tier2_Link.com" required>
                        </div>

                        <div class="form-group">
                            <label for="exampleFormControlInput1" class="title-weight">Anchor Text</label>
                            <input type="text" class="form-control" id="anchorText" name="anchorText" placeholder="Put Anchor Text here" required>
                        </div>

                        <div class="form-group">
                            <label for="exampleFormControlSelect1" class="title-weight">Select Tier 1 Link</label>
                            <select class="form-control" id="tier1Link" name="tier1Link" required>
                                <option selected disabled hidden>Select Tier1Link</option>
                                <option></option>
                            </select>
                        </div>

                        <hr>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary but-width">Save</button>
                            <hr>
                            <button type="button" class="btn btn-secondary but-width" data-dismiss="modal">Cancel</button>
                        </div>
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- End tier2 modal --}}

    {{-- Begin removeTier2 modal --}}
    <div class="modal fade" id="delete-tier2-modal" tabindex="0" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title-weight" id="exampleModalLabel">Remove Tier2</h5>
                </div>
                <div class="modal-body">
                    <form id="form-remove-Tier2" method="POST" action="{{ route('do.removeTier2') }}">

                        <div class="form-group">
                            <p>All things belonging the Tier2 will be deleted.</p>
                            <p>Are you sure you want to delete the Tier2?</p>
                            <div class="ui hidden message">
                                <div class="header"></div>
                                <div class="content"></div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary but-width">Yes, Delete</button>
                            <hr>
                            <button type="button" class="btn btn-secondary but-width" data-dismiss="modal">No</button>
                        </div>
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- End removeTier2 modal --}}


    {{-- Begin editTier2 modal --}}
     <div class="modal fade" id="editTier2" tabindex="0" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title-weight" id="exampleModalLabel">Edit Tier 2 Link</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditTier2" method="POST" action="{{ route('do.updateTier2') }}">
                        <div class="form-group">
                            <label for="exampleFormControlSelect1" class="title-weight">Select Clients</label>
                            <select class="form-control" id="clientIdEdit" name="clientIdEdit" selected="disabled" required>
                                @foreach ( $client as $clients)
                                    <option>{{$clients['client_id']}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="exampleFormControlSelect1" class="title-weight">Select Providers</label>
                            <select class="form-control" id="providerIdEdit" name="providerIdEdit" selected="disabled" required>
                                
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="exampleFormControlInput1" class="title-weight">Tier 2 Link</label>
                            <input type="text" class="form-control" id="tier2LinkEdit" name="tier2LinkEdit" placeholder="http://example_Tier2_Link.com" required>
                        </div>

                        <div class="form-group">
                            <label for="exampleFormControlInput1" class="title-weight">Anchor Text</label>
                            <input type="text" class="form-control" id="anchorTextEdit" name="anchorTextEdit" placeholder="Put Anchor Text here" required>
                        </div>

                        <div class="form-group">
                            <label for="exampleFormControlSelect1" class="title-weight">Select Tier 1 Link</label>
                            <select class="form-control" id="tier1LinkEdit" name="tier1LinkEdit" required>

                            </select>
                        </div>

                        <hr>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary but-width">Save</button>
                            <hr>
                            <button type="button" class="btn btn-secondary but-width" data-dismiss="modal">Cancel</button>
                        </div>
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- End editTier2 modal --}}

    
{{-- Begin import .csv files modal --}}
<div class="modal fade" id="import-tier2-modal" tabindex="0" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title-weight" id="exampleModalLabel">Import Tier2 CSV File</h5>
                </div>
                <div class="modal-body">
                <form id="form-import-Tier2" method="post" action="{{ route('do.importTier2CSV') }}">

                    <div class="form-group">
                        <div class="field">
                            <label>Select your file to upload</label>
                            <div class="ui input">
                                <input type="file" id="source_file" name="source_file" accept=".csv">
                            </div>
                        </div>
                        <label class="import-description hidden" style="color:red">Please input CSV file.</label>                        
                    </div>

                    <hr>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary but-width">Import</button>
                        <hr>
                        <button type="button" class="btn btn-secondary but-width" data-dismiss="modal">Cancel</button>
                    </div>
                    @csrf
                    </form>

                </div>
            </div>
        </div>
    </div>
{{-- End import .csv files modal --}}


</div>

@endsection

@section('ExtraJavascript')

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js"></script>

<script type="text/javascript" src="{{ asset('assets/js/tier2.js') }}"></script>
@endsection
