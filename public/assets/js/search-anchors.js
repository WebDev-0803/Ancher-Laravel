$(document).ready(function () {

    $('#campaign-text').text('No campaigns selected');
    readTempData();

    function readTempData() {

        $.ajax({
            method: "GET",
            url: "/temp-tier1",
            timeout: 60000,
            data: {
                _token: $('meta[name="csrf-token"]').attr("content")
            },
            success: function (response) {

                $('#temp-tier1').html('');
                for (var i = 0; i < response.data.length; i++)  {

                    appendTempTier1(response.data[i]); //true: exist false: exist

                }
                
            }, 
            error: function (error) {
                console.log(error);
                console.log('loading temp-campaign error');
            }
        })
    }

    // Get Temp Tier1 Campagins
    function appendTempTier1(data, isExist = false) {

        var row = '<div class="item" data-number="{number}" >\
                            <div class="right floated content">\
                                <div class="ui button mini green labled icon retry-temp-item">\
                                    <i class="sync icon"></i>\
                                </div>\
                                <div class="ui button mini red labled icon remove-temp-item">\
                                    <i class="trash alternate outline icon"></i>\
                                </div>\
                            </div>\
                            <div class="right floated content">\
                                <span class="temp-percent button ui">{completed_percent}</span>\
                            </div>\
                            <div class="content">\
                                <span class="temp-title button ui">{title} </span>\
                            </div>\
                        </div>';

        
        row = row.replace(/\{number\}/g, data.id);
        row = row.replace(/\{title\}/g, data.title);
            
        if( data.completed_percent == 100 ) {

            row = row.replace(/\{completed_percent\}/g, 'Completed');
            $('#temp-tier1').append(row);

        } else {

            var date = new Date(Date.parse(data.created_at, 'yyyy-mm-dd HH:mm:ss')),
                today = new Date(),
                diff = today.getTime() - date.getTime();


            if (diff > 5 * 60 * 1000) {
                
                row = row.replace(/\{completed_percent\}/g, 'Processing failed');
                $('#temp-tier1').append(row);
                $('#temp-tier1 .item:last-child span').addClass('disabled');
                
            } else {
                row = row.replace(/\{completed_percent\}/g, 'Processing...');
                $('#temp-tier1').append(row);
                $('#temp-tier1 .item:last-child').find('.button').addClass('disabled');
            }            
        }

    }

    // Begin refresh temp tier1 
    $(document).on('click', '.retry-temp-item', function (event) {
        event.preventDefault();
        
        var $this = $(this);

        $item = $this.parents('.item:first');
        number = $item.attr('data-number');

        // $item.addClass('disabled');
        $item.find('.button').addClass('disabled');
        $item.find('.temp-percent').text('Processing...');

        console.log(number);
        sendSearchAnchorRequest(number);//pck

    });
    // End refresh temp tier1

    // Begin removing temp tier1 campaign
    $(document).on('click', '.remove-temp-item', function (event) {
        event.preventDefault();
        
        var $this = $(this);

        $('#delete-temp-campaign')
            .modal({
                closable  : false,
                onDeny    : function(){
                    return true;
                },
                onApprove : function() {                    

                    $item = $this.parents('.item:first');
                    number = $item.attr('data-number');
                    $item.remove();

                    deleteTempTier1(number);
                }
            })
        .modal('show');

    });
    // End removing temp tier1 campaign    

    // call /delete-temp-tier1
    function deleteTempTier1(id) {

        $('#anchor-search-result-table tbody').html('');
        $('.do-save-csv').addClass('disabled');
        $('#campaign-text').text('No campaigns selected');
        $('#temp-tier1 .checkedCampaign').remove();

        refreshView();

        $.ajax({
            method: "POST",
            url: "/delete-temp-tier1",
            timeout: 60000,
            data: {
                '_token': $('meta[name="csrf-token"]').attr('content'),
                'id'     : id
            },
            success: function (response) {
                console.log("removing success");
            },
            error: function(error) {
                console.log(error);
                console.log('delete temp-tier1 error');
            }

        });
    }

    // Begin Temp tier1 links show
    $(document).on('click', '#temp-tier1 .temp-title', function (event) {

        event.preventDefault();

        //change Campaign Name
        $('#campaign-text').text($(this).text());

        $item = $(this).parents('.item:first');

        $('#temp-tier1').find('.checkedCampaign').removeClass('checkedCampaign');
        $item.addClass('checkedCampaign');

        number = $item.attr('data-number');
        $('#form-temp-campaign').addClass('loading');

        $('#anchor-search-result-table tbody').html('');
        $('#anchor-search-result-table').attr('data-campaign-name',$(this).text());
        $('#anchor-search-result-table').attr('data-campaign-id',number);

        $.ajax({
            method: "GET",
            url: "/temp-tier1-link",
            timeout: 60000,
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                'id' : number
            },
            success: function (response) {

                var data = response.data;

                if (response.success == true) {

                    $.each(data, function (index, item) {

                        var n = index + 1;
                        appendTempLinks('success', item, n);

                    });

                    refreshView();       
                    $('#form-temp-campaign').removeClass('loading');
                    return true;

                }
            },
            error: function (error) {
                console.log(error);
                console.log('show temp tier1 links error');
                $('#form-temp-campaign').removeClass('loading');
            }
        });
    });

    function appendTempLinks (networkStatus, data, number = 1) {
        var $table = $('#anchor-search-result-table tbody'),
            rowTemplate = '',
            tableRow = '',
            status;

        rowTemplate = '<tr data-row="{id}">\
                            <td class="center aligned "><input type="checkbox" class="check-temp-links"></td>\
                            <td class="center aligned collapsing counterCell"></td>\
                            <td class="left aligned">{url}</td>\
                            <td class="center aligned collapsing">{status}</td>\
                            <td class="center aligned collapsing">{found}</td>\
                            <td class="center aligned ">{anchorText}</td>\
                            <td class="center aligned ">{anchorURL}</td>\
                            <td class="center aligned collapsing">{fileSize}</td>\
                            <td class="center aligned ">{date}</td>\
                        </tr>';



        // Begin set status
        if (networkStatus == 'success') {

            status = data.status;

        } else {

            status = 'Network Error';

        }
        // End set status


        // Begin make row
        if (networkStatus == 'failed')
        {

            rowTemplate = rowTemplate.replace('{url}', data.url);
            rowTemplate = rowTemplate.replace('{status}', status);
            rowTemplate = rowTemplate.replace('{anchorText}', '');
            rowTemplate = rowTemplate.replace('{anchorURL}', '');
            rowTemplate = rowTemplate.replace('{found}', 'Unknown');
            rowTemplate = rowTemplate.replace('{fileSize}', 'Unknown');
            rowTemplate = rowTemplate.replace('{date}', '');
            rowTemplate = rowTemplate.replace('{id}', data.id);

            tableRow += rowTemplate;

        } else {

            rowTemplate = rowTemplate.replace('{url}', data.url);
            rowTemplate = rowTemplate.replace('{status}', status);
            rowTemplate = rowTemplate.replace('{anchorText}', data.anchor_text);
            rowTemplate = rowTemplate.replace('{anchorURL}', data.anchor_url);
            rowTemplate = rowTemplate.replace('{found}', data.anchor_text == '' ? 'Not Found' : 'Found');
            rowTemplate = rowTemplate.replace('{fileSize}', data.file_size);
            rowTemplate = rowTemplate.replace('{date}', data.processed_at);
            rowTemplate = rowTemplate.replace('{id}', data.id);

            tableRow += rowTemplate;
        }

        $table.append(tableRow);
    }


    $('#add-new-source-modal form').on('submit', function (event) {

        event.preventDefault();

    });

    /***** Begin add new source *****/
    $(document).on('click', '#show-add-new-source-modal', function (event) {

        event.preventDefault();

        var $addSouceModal = $('#add-new-source-modal'),
            $notificationModal = $('#enter-target-anchor-notification-modal'),
            $anchorURLInput = $('#anchor_url'),
            anchorURL = $anchorURLInput.val().trim(),
            urlRegex = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/;

        if (urlRegex.test(anchorURL) == false) {

            $notificationModal.modal({
                onHidden: function () {

                    $anchorURLInput.focus();

                }
            }).modal('show');

        } else {

            $addSouceModal
                .modal({
                    closable: false,
                    onHidden: function () {

                        $addSouceModal
                            .find('.field')
                            .removeClass('error')
                            .find('p.error')
                            .remove();

                    },
                    onShow: function () {

                        var $sourceInput = $addSouceModal.find('#modal-source-input');
                        $sourceInput.val('');
                        // window.modal = 'add-new-source-modal';

                    },
                    onApprove: function () {

                        var $form = $('#form-send-plain-text-source');

                        $form.submit();
                        return false;

                    }
                })
                .modal('show');
        }

    });


    $('#form-send-plain-text-source').on('submit', function (event) {
        event.preventDefault();
    });


    $('#form-send-plain-text-source').validate({
        errorPlacement: function (error, element) {

            var $parent = $(element).parents('.input:first');
            error.insertAfter($parent);

        },
        highlight: function (element, errorClass) {

            $(element)
                .parents('.field:first')
                .addClass('error');

        },
        unhighlight: function (element, errorClass) {

            $(element)
                .parents('.field:first')
                .removeClass('error');

        },
        rules: {
            modal_source_input: {
                required: true
            }
        },
        messages: {
            modal_source_input: {
                required: "Please put your source hear",
            },
        },
        submitHandler: function (form) {

            var $plainTextInput = $('#modal-source-input'),
                targetAnchorURLInput = $('input#anchor_url').val().trim();
                $modal = $('#add-new-source-modal');

                $(form).addClass('loading');
                $modal.find('.approve').addClass('loading disabled');
                $modal.find('.deny').addClass('disabled');

                $modal
                    .find('.field')
                    .removeClass('error')
                    .find('p.error')
                    .remove();

                //Store campaign name/anchour url/anchor text to temp database
                $.ajax({
                    method: 'post',
                    url: '/add-temp-tier1',
                    timeout: 600000,
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        'anchor_url' : targetAnchorURLInput,
                        'plain_text' : $plainTextInput.val()
                    },
                    success: function (response) {

                        var temptier1_id = '';

                        if( response.success) {
                            temptier1_id = response.id;

                            $modal.modal('hide');

                            $(form).removeClass('loading');
                            $modal.find('.approve').removeClass('loading disabled');
                            $modal.find('.deny').removeClass('disabled');

                            $('input#anchor_url').val('');

                            var data = {
                                number: response.id,
                                title: response.campaign_name,
                                completed_percent: 0
                            };

                            //Add campaign name to name list.
                            appendTempTier1(data);
                            
                        } else {
                            $('#modal-source-input').val('');

                            if (response.validate) {
                                temptier1_id = 'Tier2';
                                $modal
                                    .find('.field')
                                    .append('<p class="tier2_error error"> ' + response.messages[0] + ' </p>');

                                $(form).removeClass('loading');
                                $modal.find('.approve').removeClass('loading disabled');
                                $modal.find('.deny').removeClass('disabled');

                            } else {
                                
                                $modal
                                    .find('.field')
                                    .addClass('error')
                                    .append('<p class="error">' + response.messages[0] + '</p>');

                                $(form).removeClass('loading');
                                $modal.find('.approve').removeClass('loading disabled');
                                $modal.find('.deny').removeClass('disabled');

                                return false;
                            }
                        }

                        // start process datapck
                        sendSearchAnchorRequest(temptier1_id);                        
                    
                    },
                    error: function(error) {
                        $modal.modal('hide');

                        $(form).removeClass('loading');
                        $modal.find('.approve').removeClass('loading disabled');
                        $modal.find('.deny').removeClass('disabled');

                        $('input#anchor_url').val('');

                        alert("500: Internal Server Error.");
                    }
                    
                });

            return false;

        }
    });
    /***** End add new source *****/

    //Begin Send search anchor process
    function sendSearchAnchorRequest(temptier1_id) {
        $.ajax({
            url: '/search-anchors-plain-text',
            method: 'post',
            timeout: 2000000,
            data: {
                '_token': $('meta[name="csrf-token"]').attr('content'),
                'temptier1_id': temptier1_id
            },
            success: function (response) {

                readTempData();

                if (response.success == true) {

                    return true;

                } else {
                    console.log(response.messages[0]);
                }
                
                $(form).removeClass('loading');
                $modal.find('.approve').removeClass('loading disabled');
                $modal.find('.deny').removeClass('disabled');
                
            },
            error: function (error) {
                alert("500: Internal Server Error.(Processing Anchors Error.)");
            }
        });
    }
    //End Send search anchor process

    /***** Begin upload source file *****/
    $(document).on('click', '#show-upload-source-file-modal', function (event) {

        event.preventDefault();

        var $uploadModal = $('#upload-source-file-modal'),
            $notificationModal = $('#enter-target-anchor-notification-modal'),
            $anchorURLInput = $('input#anchor_url'),
            anchorURL = $anchorURLInput.val().trim(),
            urlRegex = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/;


        if (urlRegex.test(anchorURL) == false) {

            $notificationModal.modal({
                onHidden: function () {

                    $anchorURLInput.focus();

                }
            }).modal('show');

        } else {

            $uploadModal
                .modal({
                    closable: false,
                    onHidden: function () {

                        var $inputs = $uploadModal.find('.field.error'),
                            $form = $('#form-upload-source-file');

                        $form[0].reset();
                        $inputs.removeClass('error');

                    },
                    onShow: function () {

                        window.modal = 'upload-source-file-modal';

                    },
                    onApprove: function () {

                        var $form = $('#form-upload-source-file');

                        $form.submit();
                        return false;

                    }
                })
                .modal('show');

        }

    });


    $('#form-upload-source-file').on('submit', function (event) {
        event.preventDefault();
    });


    $('#form-upload-source-file').validate({
        errorPlacement: function (error, element) {

            var $parent = $(element).parents('.input:first');
            error.insertAfter($parent);

        },
        highlight: function (element, errorClass) {

            $(element)
                .parents('.field:first')
                .addClass('error');

        },
        unhighlight: function (element, errorClass) {

            $(element)
                .parents('.field:first')
                .removeClass('error');

        },
        rules: {
            source_file: {
                required: true,
                fileExtension: "rtf|txt"
            }
        },
        messages: {
            source_file: {
                required: "Please choose your file",
                fileExtension: "Please choose .RTF or .TXT file"
            },
        },
        submitHandler: function (form) {

            var formData = new FormData(),
                $sourceFile = $(form).find('[name="source_file"]'),
                $targetAnchorURLInput = $('input#anchor_url'),
                $modal = $('#upload-source-file-modal'),
                targetAnchorURL = $targetAnchorURLInput.val().trim();

            formData.append('_token', $(form).find('[name="_token"]').val());
            formData.append('target_anchor', targetAnchorURL);
            formData.append('source_file', $sourceFile[0].files[0]);

            $.ajax({
                url: $(form).attr('action'),
                method: 'post',
                timeout: 300000,
                processData: false,
                contentType: false,
                data: formData,
                beforeSend: function () {

                    $(form).addClass('loading');
                    $('#upload-source-file-modal').find('.approve').addClass('loading disabled');
                    $('#upload-source-file-modal').find('.deny').addClass('disabled');

                },
                success: function (response) {

                    var data = response.data;

                    if (response.success == true) {

                        $.each(data.analyzeResult, function (index, item) {

                            var number = index + 1;

                            appendRowToTable('success', item, number);

                        });


                        refreshView();
                        $modal.modal('hide');

                        return true;

                    } else {

                        if (response.data.action == 'reloadPage') {

                            window.showCSRFModal();

                        }

                    }


                },
                error: function (error) {



                },
                complete: function () {

                    $(form).removeClass('loading');
                    $('#upload-source-file-modal').find('.approve').removeClass('loading disabled');
                    $('#upload-source-file-modal').find('.deny').removeClass('disabled');

                }
            });

            return false;

        }
    });
    /***** End upload source file *****/


    /***** Begin show page preview */
    $(document).on('click', '.show-anchor-preview', function (event) {

        event.preventDefault();

        var $btn = $(this),
            $modal = $('#page-preview-modal'),
            $iframe = $('#page-preview-iframe'),
            pageContent = $btn.data('pageContent'),
            src = 'data:text/html;base64,' + pageContent;

        $modal
            .modal({
                onShow: function () {

                    $iframe.attr('src', src);
                    $iframe.css({
                        'height': window.innerHeight * 0.7 + 'px'
                    });

                },
                onHidden: function () {
                    $iframe.attr('src', '');
                }
            })
            .modal('show');

    });
    /***** End show page preview */


    /***** Begin clear all sources *****/
    $(document).on('click', '#remove-all-source', function (event) {

        event.preventDefault();

        var $modal = $('#clear-all-sources-modal');

        $modal
            .modal({
                closable: false,
                onApprove: function () {

                    refreshView();

                }
            })
            .modal('show');

    });
    /***** End clear all sources *****/


    /***** Begin save the CSV file *****/
    $(document).on('click', '.do-save-csv', function () {
        event.preventDefault();

        var $btn = $(this),
            csvData = [],
            campaignName = '',
            action = $btn.attr('data-action'),
            $table = $('#anchor-search-result-table');


        // Begin prepaire data
        campaignName = $table.attr('data-campaign-name');
        campaignId = $table.attr('data-campaign-id');
        $table.find('[data-row]').each(function (index, row) {

            var $row = $(row),
                rowNum = $row.attr('data-row');

            csvData.push({
                'num': rowNum,
                'url': $row.children('td:eq(1)').text(),
                'status': $row.children('td:eq(2)').text(),
                'found': $row.children('td:eq(3)').text(),
                'anchor_text': $row.children('td:eq(4)').text(),
                'anchor_url': $row.children('td:eq(5)').text(),
                'file_size': $row.children('td:eq(6)').text(),
                'date_checked': $row.children('td:eq(7)').text()
            });


            // Begin process sub rows
            $table.find('[data-main-row=' + rowNum + ']').each(function (index, subRow) {

                var $subRow = $(subRow);

                csvData.push({
                    'num': '',
                    'url': '',
                    'status': '',
                    'found': '',
                    'anchor_text': $subRow.children('td:eq(0)').text(),
                    'anchor_url': $subRow.children('td:eq(1)').text(),
                    'file_size': '',
                    'date_checked': ''
                });

            });
            // End process sub rows

        });
        // End prepaire data


        // Begin send ajax request
        $.ajax({
            url: action,
            method: 'POST',
            timeout: 60000,
            data: {
                csv: csvData,
                campaign: campaignName,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $btn.addClass('loading');
            },
            success: function (response) {

                var $modal = $('#store-csv-modal');

                if (response.success == true) {

                    $modal.find('.failed').addClass('hidden');
                    $modal.find('.success').removeClass('hidden');
                    $modal.modal('show');

                    //pck
                    deleteTempTier1(campaignId);

                } else if (response.success == false) {

                    if (response.data.action == 'reloadPage') {

                        window.showCSRFModal();

                    } else {

                        $modal.find('.failed').removeClass('hidden');
                        $modal.find('.success').addClass('hidden');
                        $modal.modal('show');

                    }

                }

            },
            error: function (error) {

                $('#store-csv-modal')
                    .addClass('failed')
                    .removeClass('success')
                    .modal('show');

            },
            complete: function () {

                $btn.removeClass('loading');

            }

        })
        // End send ajax request

    });
    /***** End save the CSV file *****/


    /**
     * Update list state
     *
     * @return Void
     */
    function refreshView() { //pck

        var $tblResult = $('#anchor-search-result-table'),
            $btnStoreCSV = $('.do-save-csv');
            $btnCheckAll = $('.btn-check-all');

        // Begin change the Store csv button state
        if ($tblResult.find('[data-row]').length > 0) {

            $btnStoreCSV
                .removeClass('disabled')
                .addClass('blue');
            
            $btnCheckAll
                .removeClass('disabled')
                .addClass('olive');

        } else {

            $btnStoreCSV
                .addClass('disabled')
                .removeClass('blue');

            $btnCheckAll
                .addClass('disabled')
                .removeClass('olive');

        }
        // End change the Store csv button state        
        
        selectedCount = $(".check-temp-links:checked").length;

        var $btnDelete = $('.btn-delete'),
            $btnUncheck = $('.btn-uncheck');

        if (selectedCount > 0){

            $btnDelete
                .removeClass('disabled')
                .addClass('red');

            $btnUncheck
                .removeClass('disabled')
                .addClass('green');

        } else {

            $btnDelete
                .removeClass('red')
                .addClass('disabled');
            
            $btnUncheck
                .removeClass('green')
                .addClass('disabled');
                
        }
        
    }

    // Begin check all
    $(document).on('click', '.btn-check-all', function () {
        $(".check-temp-links").prop('checked', true);

        refreshView();        
    });
    // End check all

    // Begin uncheck all
    $(document).on('click', '.btn-uncheck', function () {

        $(".check-temp-links").prop('checked', false);

        refreshView();
    });
    // End uncheck all

    // Begin get the checked rows
    $(document).on('click', '.check-temp-links', function () {

        refreshView();

    });
    // End get the checked rows

    // Begin delete tempLinks rows pck
    $(document).on('click', '.btn-delete', function () {
        
        event.preventDefault();

        $('#delete-result-row')
            .modal({
                closable  : false,
                onDeny    : function(){
                    return true;
                },
                onApprove : function() {

                    var $tempLinks = $('.check-temp-links:checked'),
                        linksIds = [];

                    for (var i=0; i<$tempLinks.length; i++) {
                        linksIds[i] = $tempLinks.eq(i).parents('tr:first').attr('data-row');
                    }

                    for (var i=0; i<$tempLinks.length; i++) {
                        $tempLinks.eq(i).parents('tr:first').remove();
                    }

                    refreshView();

                    $.ajax({
                        method: "delete",
                        url: "/temp-tier1-link",
                        timeout: 60000,
                        data: {
                            '_token': $('meta[name="csrf-token"]').attr('content'),
                            'ids'     : linksIds
                        },
                        success: function (response) {                            
                            console.log("removing success");
                        },
                        error: function(error) {
                            console.log("error")
                        }
                    });

                }
            })
            .modal('show');
        
    });
    // End  delete tempLinks rows
    
    /**
     * Append new search result rows to the result table
     *
     * @param Array data
     * @param String networkStatus
     * @retur Void
     */
    function appendRowToTable (networkStatus, data, number = 1) {

        var $table = $('#anchor-search-result-table tbody'),
            rowTemplate = '',
            extendRowTemplate = '<tr data-main-row="{number}">\
                                    <td class="center aligned collapsing"><span class="show-anchor-preview" data-content="{pageContent}">{anchorText}</span></td>\
                                    <td class="center aligned collapsing">{anchorURL}</td>\
                                </tr>',
            tableRow = '',
            status;

        // Begin row definition
        if (data.anchors.length == 0) {

            rowTemplate = '<tr data-row="{number}">\
                                <td class="center aligned collapsing"><input type="checkbox" class="check-temp-links"></td>\
                                <td class="center aligned collapsing counterCell"></td>\
                                <td class="left aligned">{url}</td>\
                                <td class="center aligned collapsing">{status}</td>\
                                <td class="center aligned collapsing">{found}</td>\
                                <td class="center aligned collapsing">{anchorText}</td>\
                                <td class="center aligned collapsing">{anchorURL}</td>\
                                <td class="center aligned collapsing">{fileSize}</td>\
                                <td class="center aligned collapsing">{date}</td>\
                            </tr>';

        } else if (data.anchors.length == 1) {

            rowTemplate = '<tr data-row="{number}">\
                                <td class="center aligned collapsing"><input type="checkbox" class="check-temp-links"></td>\
                                <td class="center aligned collapsing counterCell"></td>\
                                <td class="left aligned">{url}</td>\
                                <td class="center aligned collapsing">{status}</td>\
                                <td class="center aligned collapsing">{found}</td>\
                                <td class="center aligned"><span class="show-anchor-preview" data-content="{pageContent}">{anchorText}</span></td>\
                                <td class="center aligned">{anchorURL}</td>\
                                <td class="center aligned collapsing">{fileSize}</td>\
                                <td class="center aligned collapsing">{date}</td>\
                            </tr>';

        } else {

            rowTemplate = '<tr data-row="{number}">\
                                <td rowspan="' + data.anchors.length + '" class="center aligned collapsing"><input type="checkbox" class="check-temp-links"></td>\
                                <td rowspan="' + data.anchors.length + '" class="center aligned collapsing counterCell"></td>\
                                <td rowspan="' + data.anchors.length + '" class="left aligned">{url}</td>\
                                <td rowspan="' + data.anchors.length + '" class="center aligned collapsing">{status}</td>\
                                <td rowspan="' + data.anchors.length + '" class="center aligned collapsing">{found}</td>\
                                <td class="center aligned"><span class="show-anchor-preview" data-content="{pageContent}">{anchorText}</span></td>\
                                <td class="center aligned">{anchorURL}</td>\
                                <td rowspan="' + data.anchors.length + '" class="center aligned collapsing">{fileSize}</td>\
                                <td rowspan="' + data.anchors.length + '" class="center aligned collapsing">{date}</td>\
                            </tr>';

        }
        // End row definition


        // Begin set status
        if (networkStatus == 'success') {

            status = data.status;

        } else {

            status = 'Network Error';

        }
        // End set status


        // Begin make row
        if (networkStatus == 'failed')
        {

            rowTemplate = rowTemplate.replace(/\{number\}/g, number);
            rowTemplate = rowTemplate.replace('{url}', data.url);
            rowTemplate = rowTemplate.replace('{status}', status);
            rowTemplate = rowTemplate.replace('{anchorText}', '');
            rowTemplate = rowTemplate.replace('{anchorURL}', '');
            rowTemplate = rowTemplate.replace('{found}', 'Unknown');
            rowTemplate = rowTemplate.replace('{fileSize}', 'Unknown');
            rowTemplate = rowTemplate.replace('{date}', '');

            tableRow += rowTemplate;

        }
        else {

            if (data.anchors.length == 0) {

                rowTemplate = rowTemplate.replace(/\{number\}/g, number);
                rowTemplate = rowTemplate.replace('{url}', data.url);
                rowTemplate = rowTemplate.replace('{status}', status);
                rowTemplate = rowTemplate.replace('{anchorText}', '');
                rowTemplate = rowTemplate.replace('{anchorURL}', '');
                rowTemplate = rowTemplate.replace('{found}', 'Not Found');
                rowTemplate = rowTemplate.replace('{fileSize}', data.file_size);
                rowTemplate = rowTemplate.replace('{date}', data.checked_at);

                tableRow += rowTemplate;

            }
            else {

                $.each(data.anchors, function (index, anchor) {

                    var pageContent = data.page_content,
                        pageSource = '',
                        newAnchorHTML,
                        customJs = '<script type="text/javascript">window.onload = function () {var element = document.getElementById("kass-anchor-highlight"),rect = element.getBoundingClientRect(),scrollTop = window.pageYOffset || document.documentElement.scrollTop;window.scrollTo(null , rect.top + scrollTop - 50);}</script></body>';

                    // Begin prepare page content
                    newAnchorHTML = '<a id="kass-anchor-highlight" style="display: inline-block !important; background: yellow !important; color: #333 !important;" href="' + anchor.url + '">' + anchor.text + '</a>';
                    pageContent = pageContent.replace(anchor.anchor, newAnchorHTML);
                    pageContent = pageContent.replace('</body>', customJs);
                    pageSource = window.btoa(unescape(encodeURIComponent(pageContent)));
                    // End prepare page content


                    if (index == 0) {

                        rowTemplate = rowTemplate.replace(/\{number\}/g, number);
                        rowTemplate = rowTemplate.replace('{url}', data.url);
                        rowTemplate = rowTemplate.replace('{status}', status);
                        rowTemplate = rowTemplate.replace('{anchorText}', anchor['text']);
                        rowTemplate = rowTemplate.replace('{anchorURL}', anchor['url']);
                        rowTemplate = rowTemplate.replace('{found}', 'Found');
                        rowTemplate = rowTemplate.replace('{fileSize}', data.file_size);
                        rowTemplate = rowTemplate.replace('{date}', data.checked_at);
                        rowTemplate = rowTemplate.replace('{pageContent}', pageSource);

                        tableRow += rowTemplate;

                    } else {

                        var extendRow = extendRowTemplate;

                        extendRow = extendRow.replace(/\{number\}/g, number);
                        extendRow = extendRow.replace('{anchorText}', anchor['text']);
                        extendRow = extendRow.replace('{anchorURL}', anchor['url']);
                        extendRow = extendRow.replace('{pageContent}', pageSource);

                        tableRow += extendRow;

                    }

                });

            }

        }
        // End make row

        $table.append(tableRow);

        // Begin remove anchor data-content attribute
        var $showPreviewButtons = $table.find('.show-anchor-preview[data-content]');

        $showPreviewButtons.each(function () {

            var $btn = $(this),
                content = $btn.attr('data-content');

            $btn.data('pageContent', content);
            $btn.removeAttr('data-content');

        })
        // End remove anchor data-content attribute

    }


    /***** Begin add file type validation to jquery.validator *****/
    jQuery.validator.addMethod("fileExtension", function (value, element, param) {

        param = typeof param === "string" ? param.replace(/,/g, '|') : "png|jpe?g|gif";
        return this.optional(element) || value.match(new RegExp(".(" + param + ")$", "i"));

    }, jQuery.format("Please enter a value with a valid extension."));
    /***** End add file type validation to jquery.validator *****/


});