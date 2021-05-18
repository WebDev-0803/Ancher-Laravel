
$(document).ready(function(){

    // Databasetabe -------------------------
    $tier2Table = $('#tier2').DataTable({
        "dom":'<f<t>p>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search..."
        }
    });

    var $buttons = '<div class="ui checked checkbox" id="problemLinks">\
                        <input type="checkbox">\
                        <label>Show only problems</label>\
                    </div>\
                    <button id="addTier2Link" class="btn btn-info but-pos" data-toggle="modal" data-target="#tier2Modal" >Add Tier 2 Link</button>\
                    <button id="importTier2CSV" class="btn btn-info but-pos" data-toggle="modal" data-target="#import-tier2-modal">Import</button>';

    $('#clientOption').appendTo('#tier2_filter');
    $('#tier2_filter').append($buttons);
    $('#clientOption').show();

    $(document).on('change', '#clientOption', function(){

        $tier2Table.column( 1 ).search(
            $('#clientOption').val(),true, true
        ).draw();

    });

    $(document).on('change', '#problemLinks input', function(event) {

        if ($(this).prop("checked") == true) {
            $tier2Table.column( 6 ).search(
                // '^((?!Success).)*$'
                '^((?!\\Success).)*$', true, false
            ).draw();

        } else {
            $tier2Table.column( 6 ).search(
                '',true, false
            ).draw();
        }
    });

    // Databasetabe -------------------------    end

    //begin import csv
    $(document).on('click', '#importTier2CSV', function (event) {

        event.preventDefault();

        var $modal = $('#import-tier2-modal');

            $('#form-import-Tier2').unbind('submit').bind('submit', function (event) {

                event.preventDefault();

                var fileName = $('#source_file').val(),
                    ext = fileName.split('.').pop().toLowerCase();
                    
                if($.inArray(ext, ['csv']) == -1) {
                    $('.import-description').removeClass('hidden');
                    return false;
                } else {
                    $('.import-description').addClass('hidden');
                }

                //Clients  Provider  Tier 1 Link  301Y/N  301 URL  Target URL
                var csvData = [];

                if (typeof (FileReader) != "undefined") {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        
                        var rows = e.target.result.split("\r\n");
                        for (var i = 1; i < rows.length; i++) {
                            var cells = rows[i].split(",");
                            if (cells.length > 1) {
                                var data = {};
                                data.client = cells[0]; 
                                data.provider = cells[1]; 
                                data.tier1Link = cells[2];
                                data.anchorText = cells[3];                        
                                data.tier2Link = cells[4];

                                csvData[i-1] = data;
                            }
                        }

                        $.ajax({
                            url: $('#form-import-Tier2').attr('action'),
                            method: 'post',
                            timeout: 20000,
                            data: {
                                _token: $('#form-import-Tier2').find('[name="_token"]').val(),
                                data: csvData
                            },
                            beforeSend: function () {

                                // $(this).addClass('loading');

                            },
                            complete: function () {

                                // $(this).removeClass('loading');

                            },
                            success: function (response) {
                                
                                // Begin process response
                                if (response.success == true) {
                                    console.log(response.data);
                                    for (var i = 0; i < response.data.length; i++)
                                        addTier2Link(response.data[i]);                                    

                                } else if (response.success == false) {
                                    // end show messages

                                }
                                // End  process response
                                $modal.modal("hide");
                            },
                            error: function (error) {

                                console.log(error);

                            }
                        });

                    }
                    reader.readAsText($("#source_file")[0].files[0]);                    
                } else {
                    alert("This browser does not support HTML5.");
                    return false;
                }                
                
            });
    });

    //end import csv

    //begin add row to table
    function addTier2Link(tier2) {

        var buttons = '<div class="right floated content">\
                            <div class="ui tiny icon button teal show-edit-tier2-modal" data-toggle="modal" data-target="#editTier2">\
                                <i class="edit icon"></i>\
                            </div>\
                            <div class="ui tiny icon button red show-delete-tier2-modal" data-toggle="modal" data-target="#delete-tier2-modal">\
                                <i class="trash alternate outline icon"></i>\
                            </div>\
                        </div>';

        var $row = $tier2Table.row.add([
            '',
            tier2.client_id,
            tier2.provider_id,
            tier2.tier1_link_id,            
            tier2.anchor_text,
            tier2.tier2_link,
            tier2.status,
            buttons
        ]).node();

        $($row).find('td:first-child').addClass('counterCell');
        $($row).attr('data-tier2-id', tier2.id);

        $tier2Table.draw();
    }
    //end add row to table


    $('#tier2Modal').on('shown.bs.modal', function() {

        $("#clientId").append("<option selected disabled hidden>Select Client</option>");

        $("#providerId").empty();
        $("#providerId").append("<option selected disabled hidden>Select Provider</option>");
        
        $("#tier1Link").empty();
        $("#tier1Link").append("<option selected disabled hidden>Select Tier1Link</option>");

        $("#tier2Link").val("");
        $("#anchorText").val("");

    });
    //Begin search function

    $(document).on('keyup', '#search', function(){

        var filter =  $("#search").val().toLowerCase();

        $("#tier2 tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(filter) > -1)
        });
    })

    //end search function

    //Begin Get data when client selected
    function getProviderAndLink1(clientId, isAdd, selectedProvider, selectedTier1) {
        $.ajax({
            url: '/get-provider-tier1link',
            method: 'post',
            timeout: 20000,
            data: {
                _token: $('#token').val(),
                client_id: clientId
            },
            success: function (response){

                //Begin process response
                if(response.success == true){

                    var provider = response.data.providerName;
                    var tier1_link = response.data.tier1Link;
                    
                    $('#providerId').empty();
                    $('#tier1Link').empty();
                    $('#providerIdEdit').empty();
                    $('#tier1LinkEdit').empty();
                    
                    for($i=0; $i< provider.length; $i++){

                        rowTemplate = '<option>{provider[$i]}</option>';

                        rowTemplate = rowTemplate.replace('{provider[$i]}', provider[$i]);

                        if (isAdd)
                            $('#providerId').append(rowTemplate);
                        else
                            $('#providerIdEdit').append(rowTemplate);

                    }

                    for($i=0; $i< tier1_link.length; $i++){

                        rowTemplate = '<option>{tier1_link[$i]}</option>';

                        rowTemplate = rowTemplate.replace('{tier1_link[$i]}', tier1_link[$i]);

                        if (isAdd)
                            $('#tier1Link').append(rowTemplate);
                        else
                            $('#tier1LinkEdit').append(rowTemplate);

                    }

                    if (isAdd) {

                        rowTemplate = '<option selected disabled hidden>Select Provider</option>';
                        $('#providerId').append(rowTemplate);

                        rowTemplate = '<option selected disabled hidden>Select Tier1Link</option>';
                        $('#tier1Link').append(rowTemplate);


                    } else {
                        
                        if (selectedProvider != "") {

                            $("#providerIdEdit").val(selectedProvider);

                        } else {

                            rowTemplate = '<option selected disabled hidden>Select Provider</option>';
                            $('#providerIdEdit').append(rowTemplate);

                            rowTemplate = '<option selected disabled hidden>Select Tier1Link</option>';
                            $('#tier1LinkEdit').append(rowTemplate);

                        }

                        if (selectedTier1 != '')
                            $("#tier1LinkEdit").val(selectedTier1);
                        else {

                            rowTemplate = '<option selected disabled hidden>Select Tier1Link</option>';
                            $('#tier1LinkEdit').append(rowTemplate);

                        }

                        $("#providerIdEdit").prop("disabled", false);
                        $("#tier1LinkEdit").prop("disabled", false);

                    }
    
                }else if (response.success == false) {

                    if (response.data.action == 'reloadPage') {
    
                        window.showCSRFModal();
                        return false;
    
                    }
                }
            }
        })
    }
    // End Get data when client selected

    //Begin getProviderId function
    $(document).on('change', '#clientId', function(){

        var selectedClient = $('#clientId').val();

        getProviderAndLink1(selectedClient, true);

    });
    //End getProviderId function

    //Begin getProviderID from Edit
    $(document).on('change', '#clientIdEdit', function(){

        var selectedClient = $('#clientIdEdit').val();
                
        getProviderAndLink1(selectedClient, false, '');

    });
    //End getProviderId

     /***** Begin add a new Tier2 form *****/
     $('#form-add-Tier2').unbind('submit').bind('submit', function (event) {

        event.preventDefault();

            $.ajax({
                url: $(this).attr('action'),
                method: 'post',
                timeout: 20000,
                data: {
                    _token: $(this).find('[name="_token"]').val(),
                    client_id: $(this).find('[name="clientId"]').val(),
                    provider_id: $(this).find('[name="providerId"]').val(),
                    tier1_link_id: $(this).find('[name="tier1Link"]').val(),
                    anchor_text: $(this).find('[name="anchorText"]').val(),
                    tier2_link: $(this).find('[name="tier2Link"]').val()
                },
                beforeSend: function () {

                    $(this).addClass('loading');

                },
                complete: function () {

                    $(this).removeClass('loading');

                },
                success: function (response) {

                    // Begin process response pck
                    if (response.success == true) {
                        
                        addTier2Link(response.data.tier2);

                        $("#tier2Modal").modal("hide");

                    } else if (response.success == false) {

                        if (response.data.action == 'reloadPage') {

                            window.showCSRFModal();
                            return false;

                        }


                        // begin show messages
                        if (response.messages.length > 0) {

                            var $modal = $('#message-modal');

                            $modal.find('.content').text(response.messages[0]);
                            $modal.modal('show');

                        }
                        // end show messages

                    }
                    // End  process response

                },
                error: function (error) {

                    var $modal = $('#message-modal');

                    if (error.statusText == 'timeout') {
                        $modal.find('.content').text('The server does not respond');
                    } else {
                        $modal.find('.content').text('There was a problem, please try agian');
                    }

                    $modal.modal('show');

                }
            });

    });
    /***** End add a new Tier2 form *****/


     /***** Begin delete Tier2 *****/
     $(document).on('click', '.show-delete-tier2-modal', function (event) {

        event.preventDefault();

        var $modal = $('#delete-tier2-modal'),
            $btn = $(this),
            $item = $btn.parents('tr'),
            tier2Id = $item.attr('data-tier2-id'),
            $message = $modal.find('.message');

            $('#form-remove-Tier2').unbind('submit').bind('submit', function (event) {

                event.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'post',
                    timeout: 20000,
                    data: {
                        _token: $(this).find('[name="_token"]').val(),
                        id: tier2Id,
                    },
                    beforeSend: function () {

                        $(this).addClass('loading');

                    },
                    complete: function () {

                        $(this).removeClass('loading');

                    },
                    success: function (response) {

                        // Begin process response
                        if (response.success == true) {

                            $tier2Table.row($item).remove().draw();
                            // updatePagination();
                            // $modal.modal('hide');

                        } else if (response.success == false) {

                            if (response.data.action == 'reloadPage') {

                                window.showCSRFModal();
                                return false;

                            }


                            // begin show messages
                            if (response.messages.length > 0) {

                                var $modal = $('#message-modal');

                                $modal.find('.content').text(response.messages[0]);
                                $modal.modal('show');

                            }
                            // end show messages

                        }
                        // End  process response

                        $("#delete-tier2-modal").modal("hide");
                    },
                    error: function (error) {

                        var $modal = $('#message-modal');

                        if (error.statusText == 'timeout') {
                            $modal.find('.content').text('The server does not respond');
                        } else {
                            $modal.find('.content').text('There was a problem, please try agian');
                        }

                        $modal.modal('show');

                    }
                });
            });
    })
    /***** End delete Tier2 *****/

    
    /***** Begin retry Tier2 *****/    
    $(document).on('click', '.show-retry-tier2-modal', function (event) {

        event.preventDefault();

        var $btn = $(this),
        $item = $btn.parents('tr'),
        tier2Id = $item.attr('data-tier2-id');
        $(this).addClass('loading');
        $.ajax({
            url: '/tier2-retry',
            method: 'post',
            timeout: 90000,
            data: {
                id: tier2Id,              
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $btn.removeClass('loading');
                // Begin process response
                if (response.success == true) {

                    var index = $tier2Table.row($item).index();
                    var data = $tier2Table.row(index).data();

                    data[6] = response.status;
                    console.log(data[6]);

                    $tier2Table.row(index).data(data).invalidate();

                } else if (response.success == false) {

                    if (response.data.action == 'reloadPage') {

                        window.showCSRFModal();
                        return false;

                    }

                    // begin show messages
                    if (response.messages.length > 0) {

                        var $modal = $('#message-modal');

                        $modal.find('.content').text(response.messages[0]);
                        $modal.modal('show');

                    }
                    // end show messages

                }
                // End  process response
                
            },
            error: function (error) {
                console.log(error);
                $btn.removeClass('loading');
            }
        });
    });
    /***** End retry Tier2 *****/


    /***** Begin edit Tier2 *****/
    $(document).on('click', '.show-edit-tier2-modal', function (event) {

    event.preventDefault();

    var $modal = $('#editTier2')
        $btn = $(this),
        $item = $btn.parents('tr'),
        tier2Id = $item.attr('data-tier2-id'),
        $message = $modal.find('.message'),
        $tier2Row = [];

        for( var $i = 0; $i< 6; $i++) {
            $tier2Row[$i] = $item.children("td:nth-child("+($i+2)+")").text();
        }

        $("#clientIdEdit").val($tier2Row[0]);
        
        getProviderAndLink1($tier2Row[0], false, $tier2Row[1], $tier2Row[2]);

        $("#anchorTextEdit").val($tier2Row[3]);
        $("#tier2LinkEdit").val($tier2Row[4]);
    
    var $status = $tier2Row[5];

        $('#formEditTier2').unbind('submit').bind('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: $(this).attr('action'),
                method: 'post',
                timeout: 20000,
                data: {
                    id: tier2Id,
                    client_id: $("#clientIdEdit").val(),
                    provider_id: $("#providerIdEdit").val(),
                    tier1_link_id: $("#tier1LinkEdit").val(),
                    anchor_text: $("#anchorTextEdit").val(),
                    tier2_link: $("#tier2LinkEdit").val(),
                    status: $status,
                    _token: $(this).find('[name="_token"]').val()
                },
                beforeSend: function () {

                    $(this).addClass('loading');

                },
                complete: function () {

                    $(this).removeClass('loading');

                },
                success: function (response) {

                    // Begin process response
                    if (response.success == true) {

                        var index = $tier2Table.row($item).index();
                        var data = $tier2Table.row(index).data();

                        data[1] = response.data.client_id;
                        data[2] = response.data.provider_id;
                        data[3] = response.data.tier1_link_id;
                        data[4] = response.data.anchor_text;
                        data[5] = response.data.tier2_link;

                        $tier2Table.row(index).data(data).invalidate();

                        $("#editTier2").modal("hide");

                    } else if (response.success == false) {

                        if (response.data.action == 'reloadPage') {

                            window.showCSRFModal();
                            return false;

                        }

                        // begin show messages
                        if (response.messages.length > 0) {

                            var $modal = $('#message-modal');

                            $modal.find('.content').text(response.messages[0]);
                            $modal.modal('show');

                        }
                        // end show messages

                    }
                    // End  process response

                },
                error: function (error) {

                    var $modal = $('#message-modal');

                    if (error.statusText == 'timeout') {
                        $modal.find('.content').text('The server does not respond');
                    } else {
                        $modal.find('.content').text('There was a problem, please try agian');
                    }

                    $modal.modal('show');

                }
            });
        });
    })
    /***** End edit Tier2 *****/


})
