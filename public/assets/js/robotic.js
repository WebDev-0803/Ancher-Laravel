$(document).ready(function() {
  var moneyRobotId = localStorage.getItem("mrvalue");

  if (moneyRobotId != null) {
    $('.login-field').hide();
    loginMoneyRobot(false);
  } else {
    $('.login-field').show();
  }

  $(".radio_mr_301").on('change', function() {
    initArticleEditorStatus();
  });

  $("#generate_article").click(function() {//pck
    initArticleEditorStatus();
  });

  function initArticleEditorStatus() {
    var isMr = $('#setMrUrl').prop("checked");
    var isBuilder = $('#generate_article').prop("checked");

    if (!isMr) {
      $('input[name="field_8"]').prop("disabled", true);
      $('textarea[name="field_9"]').prop("disabled", true);
    } else {
      if(!isBuilder){
        $('input[name="field_8"]').prop("disabled", false);
        $('textarea[name="field_9"]').prop("disabled", false);
      } else {
        $('input[name="field_8"]').prop("disabled", true);
        $('textarea[name="field_9"]').prop("disabled", true);
      }
    }
  }

  // Begin add new campaign
  $(document).on("click", "#addNewCampaign", function(event) {
    
    for (i = 0; i < 17; i++) {
      if (i == 10 || i == 16) {       
        continue;
      }      
      if (!(i > 0 && i < 6) && (i != 15)) $('[name="field_' + i + '"]').val("");
    }

    $('[name="field_15"]').text("1");    
    $('#newCampaignField').attr('campaign-id', 'new');

    $('#newCampaignField').show();
    $('.campaignItem').removeClass('selected');
  });

  $(document).on("change", "#fieldClient", function() {
    $('#newCampaignField').hide();
    $('#previewCampaignField').addClass('loading');
    var clientId = $('#fieldClient option:selected').data('id');
    if (clientId=="") {
      $("#campaignLists").html("");
      return false;
    }

    var mr_userId = 'NULL';
    if (localStorage.getItem('mrvalue') != null) {
      console.log(clientId);
      mr_userId = localStorage.getItem('mrvalue');
    }
    else
      return;

    $.ajax({
      url: "/robotic/mrcampaign",
      method: "GET",
      timeout: 60000,
      data: {
        clientId: clientId,
        mr_userId: mr_userId,
        _token: $('meta[name="csrf-token"]').attr("content")
      },
      success: function(response) {        
        if (response.success == true) {
          $("#campaignLists").html("");
          $("#campaignLists").removeClass('hidden');

          for (i = 0; i < response.mrCampaigns.length; i++) {
            addNewMrCampaign(response.mrCampaigns[i]);
          }

        } else {
          alert(response.messages);
          $('#addNewCampaign').removeClass('disabled loading');
        }
        $('#previewCampaignField').removeClass('loading');
      },
      error: function(error) {
       alert(error);
       $('#previewCampaignField').removeClass('loading');
      }
    });
  });

  // Begin Login
  $(document).on("click", "#loginMr", function(event) {
    $("#loginMr").addClass("disabled loading");
    loginMoneyRobot(true);
  });

  function loginMoneyRobot(fromBtn) {
    $('#addNewCampaign').addClass('disabled loading');

    var id = 'NULL';
    if ((!fromBtn) && (localStorage.getItem('mrvalue') != null))
      id = localStorage.getItem('mrvalue');
    
    $.ajax({
      url: "/loginmr",
      method: "POST",
      timeout: 60000,
      data: {
        email: $("#email").val(),
        password: $("#password").val(),
        id: id,
        _token: $('meta[name="csrf-token"]').attr("content")
      },
      success: function(response) {
        $("#loginMr").removeClass("disabled loading");
        
        if (response.success == true) {

          $('#addNewCampaign').removeClass('disabled loading');
          $('.login-field').hide();
          $("#campaignLists").html("");
          $("#campaignLists").removeClass('hidden');

          localStorage.setItem("mrvalue", response.id);

          initDropdowns(response.id, response.dropdowns, response.clients);

        } else {
          alert(response.messages);
          $('#addNewCampaign').removeClass('disabled loading');
        }
      },
      error: function(error) {
        $("#loginMr").removeClass("disabled loading");
        alert('Connection Failed! Login again.');
        $('#addNewCampaign').removeClass('loading');
        $('.login-field').show();
      }
    });

    // prevent close
    return false;
  };
  // End add new campaign

  //Edit added campaign
  $(document).on("click", ".list-item", function(event) {
    $parent = $(this).parent().parent();
    id = $parent.attr('campaign-id');
    $('#newCampaignField').show();
    $('#newCampaignField').addClass('loading');

    $('.campaignItem').removeClass('selected');
    $parent.addClass('selected');

    $.ajax({
      url: "/robotic/newCampaignInfo",
      method: "GET",
      timeout: 60000,
      data: {        
        id: id,
        _token: $('meta[name="csrf-token"]').attr("content")
      },
      success: function(response) {        
        
        if (response.success == true) {
          var data = response.data;
          for (i = 0; i < 17; i++) {
            if (i == 10) {
              $('[name="field_' + i + '"]').prop("checked", data[i]=='1'?true:false);
            } else if (i==16) {
              $('[name="field_' + i + '"]').prop("checked", data[i]=='1'?true:false);
              continue;
            }
            $('[name="field_' + i + '"]').val(data[i]);            
          }
          if (data[18] == 'true') {
            $('#setMrUrl').prop('checked', true);
          } else {
            $('#set301Url').prop('checked', true);
          }
          
          initArticleEditorStatus();
          $('#fieldClientDropdown option[data-id="'+data[17]+'"]').prop("selected",true);
          $('#newCampaignField').attr('campaign-id', id);
        } else {
          alert("Request Failed!");
        }
        $('#newCampaignField').removeClass('loading');
      },
      error: function(error) {
        $("#loginMr").removeClass("disabled loading");
        $('#newCampaignField').removeClass('loading');
      }      
    });
    
  });

  var $currentFormId;
  //begin init Money Robot campaigns
  function addNewMrCampaign(data) {
    var $content = $("#campaignLists"),
      temp =
        `<div campaign-id="{id}" class="campaignItem ui segments">
            <div class="ui grid">
                <div class="two wide column list-item">
                    <div class="field campaign-title">
                        <label>{name}</label>
                    </div>
                </div>
                <div class="fourteen wide column">
                    <div class="field">
                        <div class="upload-item" style="float:left;">
                          <form id="upload-{id}" class="upload_form {hidden}" action="/upload-body-files" method="post" enctype="multipart/form-data">
                              <input type="hidden" name="_token" value="{token}">
                              <input type="hidden" name="mrCampaignId" value="{id}">
                              <input type="file" class="ui tiny button" name="file[]" accept="text/*" multiple>
                              <button type="submit" name="upload" class="ui tiny primary button">Upload</button>
                              <span class="textFileCount">{body_files} files</span>
                          </form>
                        </div>
                        <div style="float:right;">
                            <div class="ui mini input">
                                <input class="repeatTimes" type="text" placeholder="Times...">
                            </div>
                            <button class="ui tiny primary button runMrCampaign">
                                Run
                            </button>
                            <div class="ui tiny button removeMrCampaign" tabindex="0" data-toggle="modal" data-target="#delete-mrCampaign-modal">
                                Delete
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    
    temp = temp.replace(/{id}/g, data.id);
    temp = temp.replace("{name}", data.name);
    temp = temp.replace("{body_files}", data.body_files);
    temp = temp.replace('{token}', $('meta[name="csrf-token"]').attr("content"));
    
    if (data.bulk == 'true') {
      temp = temp.replace("{hidden}", 'hidden');
    } else {
      temp = temp.replace("{hidden}", '');
    }

    $content.append(temp);
    
    
    $('form').ajaxForm({
      beforeSubmit: function(formData, jqForm) {        
        $currentFormId = jqForm[0].id;
      },
      beforeSend: function() {                  
        $("#"+$currentFormId).find('button[name="upload"]').addClass('loading');
      },
      success: function(response) {      
        if (response.success) {          
          $("#"+$currentFormId).find('.textFileCount').text(response.count+" files");          
        } else {
          alert(response.message);
        }
        $("#"+$currentFormId).find('button[name="upload"]').removeClass('loading');
        $("#"+$currentFormId).find('input[name="file[]"]').val("");
      },
      error: function(error) {
        alert(error);
        $("#"+$currentFormId).find('button[name="upload"]').removeClass('loading');
      }
    });
  }
  //end init Money Robot campaigns
  
  // $('.upload_form').on('submit', function(event) {
  //   alert('aaa');
  //   event.preventDefault();
    
  //   $.ajax({
  //     url: "/upload-body-files",
  //     method: 'get',
  //     data: new FormData(this),
  //     dataType: 'JSON',
  //     contentType: false,
  //     // cache: false,
  //     processData: false,
  //     success: function(response) {
  //       alert(response.message);
  //     }
  //   });
  // });

  // Begin Run
  $(document).on("click", ".runMrCampaign", function(event) {
    event.preventDefault();

    var $parent = $(this).parents(".campaignItem:first");
    id = $parent.attr("campaign-id");
    mr_userid = $("#newCampaignField").attr('mruser-id');
    repeatTimes = $parent.find(".repeatTimes").val();

    $.ajax({
      url: "/run",
      method: "POST",
      timeout: 60000,
      data: {
        id: id,
        mr_userid: mr_userid,
        repeatTimes: repeatTimes,
        _token: $('meta[name="csrf-token"]').attr("content")
      },
      beforeSend: function() {
        $parent.find(".button").addClass("disabled loading");
      },
      complete: function() {
        $parent.find(".button").removeClass("disabled loading");
      },
      success: function(response) {
        $parent.find(".button").removeClass("disabled loading");
        if (response.success == true) {
          alert(response.totalSent + " campaigns are sent to MR!");
          $parent.find('.textFileCount').text(response.body+" files");          
        } else {
          alert("response.message");
        }
        $('#newCampaignField').hide();
      },
      error: function(error) {
        alert("Internal Server Error!");
        $parent.find(".button").removeClass("disabled loading");
        $('#newCampaignField').hide();
      }
    });
  });
  // End Run

  //begin init dropdowns
  function initDropdowns(id, data, clients) {
    $("#newCampaignField").attr("mruser-id", id);
    //5: dropdown category count;
    var count = 5,
      $dom = [];

    $dom[0] = $("select[name='field_1'");
    $dom[1] = $("select[name='field_2'");
    $dom[2] = $("select[name='field_3'");
    $dom[3] = $("select[name='field_4'");
    $dom[4] = $("select[name='field_5'");

    for (var i = 0; i < count; i++) {
      for (var j = 0; j < data[i].length; j++) {
        var temp =
          '<option value="' + data[i][j] + '">' + data[i][j] + "</option>";
        $dom[i].append(temp);
      }
    }
    $dom[2].val($dom[2].find('option').eq(1).val());
    $('.fieldClient').append('<option data-id="">Select Client</option>');
    for (var i =0; i< clients.length; i++) {
      var temp1 =
          '<option data-id="' + clients[i].uuid + '">' + clients[i].title + "</option>";
        $('.fieldClient').append(temp1);

    }
  }
  // end init dropdowns

  // Begin register Money Robot
  $(document).on("click", "#registerMr", function(event) {
    event.preventDefault();

    var $modal = $("#registermrModal"),
      modalId = "edit-client-modal",
      action = $("#form_registermr").attr("action");
    $message = $modal.find(".message");
    $messageContent = $message.find(".content");

    $("#mrEmail").val("");
    $("#mrPassword").val("");

    $modal
      .modal({
        closable: false,
        onHidden: function() {
          window.modal = "";

          $messageContent.text("");

          $message.addClass("hidden").removeClass("yellow");
        },
        onShow: function() {
          window.modal = modalId;
        },
        onApprove: function() {
          $.ajax({
            url: action,
            method: "POST",
            timeout: 120000,
            data: {
              email: $("#mrEmail").val(),
              password: $("#mrPassword").val(),
              _token: $('meta[name="csrf-token"]').attr("content")
            },
            beforeSend: function() {
              $modal.find(".button").addClass("disabled loading");
            },
            complete: function() {
              $modal.find(".button").removeClass("disabled loading");
            },
            success: function(response) {
              if (response.success == true) {
                $modal.modal("hide");
              } else {
                $messageContent.text(response.messages);
                $message.removeClass("hidden").addClass("yellow");
              }

              $modal.find(".button").removeClass("disabled loading");
            },
            error: function(error) {
              $modal.find(".button").removeClass("disabled loading");

              $messageContent.text("Register Failed! Try Again please.");

              $message.removeClass("hidden").addClass("yellow");
            }
          });

          // prevent close
          return false;
        }
      })
      .modal("show");
  });
  // End register Money Robot

  //Begin Save Moneyrobot campaign
  $(document).on("click", "#btn_save", function(event) {
    event.preventDefault();

    var data = [];
    for (var i = 0; i < 17; i++) {
      if (i == 10 || i == 16) {
        data[i] = $('[name="field_' + i + '"]').prop("checked") ? "1" : "0";
        continue;
      }

      data[i] = $('[name="field_' + i + '"]').val();
      if (!(i > 0 && i < 6) && i != 15) $('[name="field_' + i + '"]').val("");
    }
    data[17] = $('#fieldClientDropdown option:selected').data('id');
    $('[name="field_15"]').text("1");
    $("#newCampaignField").addClass("disabled loading");

    status = $('#newCampaignField').attr('campaign-id');
    var isMr = $('#setMrUrl').prop("checked");
    mrid = $("#newCampaignField").attr("mruser-id");
    
    if (status != 'new') {
      if (isMr)
        $('.campaignItem[campaign-id="'+status+'"] form').addClass('hidden');
      else
        $('.campaignItem[campaign-id="'+status+'"] form').removeClass('hidden');
    }

    $.ajax({
      url: "/savemrcampaign",
      method: 'post',
      // timeout: 60000,
      data: {
        mrid: mrid,
        ismr: isMr,
        data: data,
        status: status,
        _token: $('meta[name="csrf-token"]').attr("content")
      },
      success: function(response) {
        if (response.success == true) {
          if (status == 'new') {
            var clientId = $('#fieldClientDropdown option:selected').data('id');            
            var $item = $('#fieldClientDropdown option[data-id="'+clientId+'"]');
            var title = $item.text();
            var count = title.substr(title.indexOf('-')+4, 3);//count less than 1000
            title = title.replace(count, parseInt(count, 10)+1);            
            $item.text(title);            
            $('#fieldClient option[data-id="'+clientId+'"]').text(title);

            if( clientId == $('#fieldClient option:selected').data('id'))
              addNewMrCampaign(response.data);
          }
          else {
            var clientId = $('#fieldClientDropdown option:selected').data('id');
            var $item = $('#fieldClientDropdown option[data-id="'+clientId+'"]');
            var title = $item.text();
            var count = title.substr(title.indexOf('-')+4, 3);//count less than 1000
            title = title.replace(count, parseInt(count, 10)+1);            
            $item.text(title);            
            $('#fieldClient option[data-id="'+clientId+'"]').text(title);

            clientId2 = $('#fieldClient option:selected').data('id');
            $item = $('#fieldClientDropdown option[data-id="'+clientId2+'"]');
            title = $item.text();
            count = title.substr(title.indexOf('-')+4, 3);//count less than 1000
            title = title.replace(count, Math.max(parseInt(count, 10)-1, 0));
            $item.text(title);            
            $('#fieldClient option[data-id="'+clientId2+'"]').text(title);

            $item = $("div[campaign-id='"+status+"']");
            $item.find('.campaign-title').text(response.data[14]);

            if (clientId != clientId2)
              $item.remove();

          }

          $('#newCampaignField').hide();          

        } else {
          alert(response.messages);
        }
        $("#newCampaignField").removeClass("disabled loading");
      },
      error: function(error) {        
        alert("Internal Server Error!");
        $("#newCampaignField").removeClass("disabled loading");
      }
    });    
  });
  //End save moneyrobot campaign

  //Begin delete money robot campaign
  $(document).on("click", ".removeMrCampaign", function(event) {
    event.preventDefault();

    var $modal = $("#delete-mrCampaign-modal"),
      $btn = $(this),
      $parent = $btn.parents(".campaignItem:first"),
      campaign_id = $parent.attr("campaign-id"),
      action = $modal.attr("action");

    $("#form-delete-campaign").unbind("submit").bind("submit", function(event) {
      event.preventDefault();
      var $this = $(this);
      $this.find(".btn-primary").addClass("ui button loading");

        $.ajax({
          url: '/deletecampaign',
          method: "POST",
          timeout: 60000,
          data: {
            campaign_id: campaign_id,
            _token: $('meta[name="csrf-token"]').attr("content")
          },
          success: function(response) {
            if (response.success == true) {
              $parent.remove();
              clearCampaignField();
              
              clientId = $('#fieldClient option:selected').data('id');
              var $item = $('#fieldClientDropdown option[data-id="'+clientId+'"]');
              var title = $item.text();
              var count = title.substr(title.indexOf('-')+4, 3);//count less than 1000
              title = title.replace(count, Math.max(parseInt(count, 10)-1, 0));            
              $item.text(title);            
              $('#fieldClient option[data-id="'+clientId+'"]').text(title);

            } else {
              alert("Internal Server Error!");
            }
            $this.find(".btn-primary").removeClass("ui button loading");
            $modal.modal("hide");
          },
          error: function(error) {
            alert("Internal Server Error!");
            $this.find(".btn-primary").removeClass("ui button loading");
            $modal.modal("hide");
          }
        });
    });
  });
  //End delete money robot campaign

  function clearCampaignField() {
    for (i = 0; i < 17; i++) {
      if (i == 10 || i == 16) {       
        continue;
      }      
      if (!(i > 0 && i < 6) && i != 15) $('[name="field_' + i + '"]').val("");
    }
    $('[name="field_15"]').text("1");
    $('#newCampaignField').attr('campaign-id', 'new');

    $('#newCampaignField').hide();
    $('.campaignItem').removeClass('selected');    
  }
});
