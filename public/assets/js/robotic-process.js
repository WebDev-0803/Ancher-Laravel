$(document).ready(function() {
  var moneyRobotId = localStorage.getItem("mrvalue");
  
  if (moneyRobotId != null) {
    $('.login-field').hide();
    loginMoneyRobot(false);
  } else {
    $('.login-field').show();
  }

  
  // Databasetabe -------------------------
  $campaignTable = $("#campaignlists").DataTable({
    // dom: "<f<t>p>",
    "dom": '<"top"lif>rt<"bottom"ip><"clear">',
    language: {
      search: "_INPUT_",
      searchPlaceholder: "Search..."
    },
    'pageLength': 100,
    'columnDefs': [
      {
         'targets': 0,
         'checkboxes': {
            'selectRow': true,
            'selectAllPages': false
         },

      }
   ],
   'select': {
      'style': 'multi'
   },
   'order': [[1, 'asc']]
  });

  var $options = 
      `<select id="filterOptions" class="form-control">
          <option class="filterOption" value="" selected >All</option>                  
          <option class="filterOption" name="clientIdEdit">Error</option>
          <option class="filterOption" name="clientIdEdit">Pending</option>
          <option class="filterOption" name="clientIdEdit">Processing</option>
          <option id="filterEdit" name="clientIdEdit">Completed</option>          
          <option id="filterProcessed" name="clientIdEdit">Processed</option>
      </select>`;    

  $('#campaignlists_filter').append($options);
  
  $(document).on('change', '#filterOptions', function(){
      $campaignTable.column( 4 ).search(
          $('#filterOptions').val(),true, true
      ).draw();

      initSelectOption();
  });

  function initSelectOption()
  {
    var selectedOption = $('#filterOptions'). children("option:selected").val();      
    if (selectedOption.toLowerCase() == 'completed') {
      $('#processAll').removeClass('disabled');      
    } else {
      $('#processAll').addClass('disabled');      
    }
  }

  //login money robot and scraping
  $(document).on("click", "#loginMr", function(event) {
    $("#loginMr").addClass("disabled loading");
    loginMoneyRobot(true);
  });

  function loginMoneyRobot(fromBtn) {

    var id = 'NULL';
    if ((!fromBtn) && (localStorage.getItem('mrvalue') != null))
      id = localStorage.getItem('mrvalue');

    $.ajax({
      url: "/robotic/getmrstatus",
      method: "GET",
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
          $('.login-field').hide();
          $("#campaignlists").attr("mr_userId", response.mr_userId);
          localStorage.setItem("mrvalue", response.mr_userId);
          $campaignTable.clear().draw();
          for (i = 0; i < response.mrCampaigns.length; i++) {
            addMrCampaign(response.mrCampaigns[i]);
          }          
          $('#progressBar').show();
          getProgressStatus();
        } else {
          alert(response.messages);
        }
      },
      error: function(error) {
        $("#loginMr").removeClass("disabled loading");
        alert('Connection Failed! Login again.');
        $('.login-field').show();
      }
    });

    // prevent close
    return false;
  };

  function addMrCampaign(data) {

    var $row = $campaignTable.row
      .add([data.mr_id,"", data.name, data.anchor_url, data.status])
      .node();

    $($row).find("td:nth-child(2)").addClass("counterCell");
    $($row).attr("campaign-id", data.mr_id);    

    $campaignTable.draw();
  }

  // //Begin Convert to OHI
  // $(document).on("click", ".converToOhi", function(event) {
  //   event.preventDefault();

  //   $parent = $(this).parents("tr");
  //   id = $parent.attr("campaign-id");

  //   $.ajax({
  //     url: "/robotic/convert-to-ohi",
  //     method: "POST",
  //     timeout: 60000,
  //     data: {
  //       id: id,
  //       mr_userId: $("#campaignlists").attr("mr_userId"),
  //       _token: $('meta[name="csrf-token"]').attr("content")
  //     },
  //     beforeSend: function() {
  //       $parent.find(".button").addClass("disabled loading");
  //     },
  //     complete: function() {
  //       $parent.find(".button").removeClass("disabled loading");
  //     },
  //     success: function(response) {
  //       if (response.success == true) {
  //         $parent.find(".button").removeClass("disabled loading");
  //         var index = $campaignTable.row($parent).index();
  //         var data = $campaignTable.row(index).data();

  //         data[3] = "Sent to OHI";
  //         $campaignTable.row(index).data(data).invalidate();

  //         console.log("success");
  //       } else {
  //         alert(response.message);
  //       }
  //     }
  //   });
  // });

  //Delete campaign from Money Robot
  $(document).on("click", "#deleteMrCamapign", function(event) {
    event.preventDefault();    

    var $modal = $("#delete-mrCampaign-modal");

    var  rows_selected = $campaignTable.column(0).checkboxes.selected();    
    var rows = rows_selected.length == 0 ?
      null : rows_selected.join(',').split(",");

    $("#form-delete-campaign").unbind("submit").bind("submit", function(event) {
      event.preventDefault();
      $(this).find(".btn-primary").addClass("ui button loading");
      var $this = $(this);
      $.ajax({
        url: $(this).attr("action"),
        method: "delete",
        data: {
          rows: rows,
          mr_userId: $("#campaignlists").attr("mr_userId"),
          _token: $('meta[name="csrf-token"]').attr("content")
        },
        success: function(response) {
          if (response.success == true) {

            $.each(response.rows, function (index, value) {              
              $tr = $('#campaignlists tr[campaign-id="'+value+'"]');
              $campaignTable.row($tr).remove();
            });
            $campaignTable.draw();

          } else if (response.success == false) {
            alert(response.message);
          }
          $this.find(".btn-primary").removeClass("ui button loading");
          $modal.modal("hide");
        },
        error: function(error) {
          alert("Internal Server Error!");
          $this.find(".btn-primary").removeClass("ui button loading");
          $modal.modal("show");
        }
      });
    });
  });
  
  $(document).on("click", "#retryProcess", function(event) {

    event.preventDefault();
    var user_id = localStorage.getItem("mrvalue");
    if (user_id == null)
      return false;

    $.ajax({
      url: '/robotic/process-retry',
      method: "get",
      data: {
        id: user_id,
        _token: $('meta[name="csrf-token"]').attr("content")
      },
      success: function(response) {
        if (response.success == true) {
          // alert(response.count+" campaigns are processed.");          
          console.log('retry');
        } else if (response.success == false) {
          console.log('error');
        }
      },
      error: function(error) {
        console.log(error);
      }
    });

  });

  $(document).on("click", "#processAll", function(event) {
    event.preventDefault();    

    var rows_selected = $campaignTable.column(0).checkboxes.selected();
    if(rows_selected.length == 0) {
      alert("Please select rows!");
      return false;
    }
    
    var rows = [];
    $('#processAll').addClass("disabled");
    for (i=0; i<rows_selected.length; i++)
    {
      rows[i] = rows_selected[i];
    }
    
    console.log(rows);

    var user_id = localStorage.getItem("mrvalue");
    if (user_id == null)
      return false;

    $.ajax({
      url: '/robotic/process-all',
      method: "get",
      data: {
        id: user_id,
        rows: rows,
        _token: $('meta[name="csrf-token"]').attr("content")
      },
      success: function(response) {
        if (response.success == true) {
          // alert(response.count+" campaigns are processed.");          
          console.log(response.count);
        } else if (response.success == false) {
          alert("Please login again!")
        }
      },
      error: function(error) {
        console.log(error);
      }
    });
  });

  
  function getProgressStatus() {
    var id = 'NULL';

    if (localStorage.getItem('mrvalue') != null)
      id = localStorage.getItem('mrvalue');

    $.ajax({
      url: "/progress-status",
      method: "GET",
      data: {
        id: id,
        _token: $('meta[name="csrf-token"]').attr("content")
      },
      timeout: 20000,
      success: function(response) {
        if (response.success == true) {

          var processingCount = response.processingIds.length;
          var processedCount = response.processedIds.length;

          var logStr="Processing: ";
          for (i=0; i<processingCount;i++)
            logStr += response.processingIds[i].mr_id + " ";
          console.log(logStr);

          var logStr="Completed: ";
          for (i=0; i<processedCount;i++)
            logStr += response.processedIds[i].mr_id + " ";
          console.log(logStr);

          var logStr="Current: ";
          for (i=0; i<response.now.length;i++)
            logStr += response.now[i].mr_id + " ";
          console.log(logStr);
          
          var value = 0;
          if (processingCount == 0) {
            $('#progressBar').hide();
            $('.progress-bar').text("0%");
            $('.progress-bar').width('0%').attr('aria-valuenow', 0);
            $('#retryProcess').addClass('disabled');
            initSelectOption();
          }
          else {
            $('#progressBar').show();
            value = Math.ceil(processedCount / (processedCount+processingCount) * 100);
            $('.progress-bar').text(value+"%");
            $('.progress-bar').width(value+'%').attr('aria-valuenow', value);
            $('#processAll').addClass("disabled");
            $('#retryProcess').removeClass('disabled');
          }
          
          if (processedCount > 0) {
            var $selectedOption = $('#filterOptions').children("option:selected").val();
            if (!($selectedOption.toLowerCase() == 'processed' || $selectedOption.toLowerCase() == '')) {
              $.each(response.processedIds, function (index, value) {                
                $tr = $('#campaignlists tr[campaign-id="'+value.mr_id+'"]');
		            if ($tr.length < 1)
                  return;
                $campaignTable.row($tr).deselect();
                $td = $('#campaignlists tr[campaign-id="'+value.mr_id+'"] td');
                $tr.removeClass('selected');
                $cell = $td.eq(4);
                $campaignTable.cell($cell).data('Processed');
              });
              $campaignTable.draw();
            }
          }

          setTimeout(function() {
            getProgressStatus();
          }, 10000);
        } 
      }
    });
  }

});
