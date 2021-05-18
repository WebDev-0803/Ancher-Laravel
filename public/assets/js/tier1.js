$(document).ready(function() {
  window.ItemsOnPage = 5;

  // Databasetabe -------------------------
  $tier1Table = $("#tier1").DataTable({
    dom: "<f<t>p>",
    language: {
      search: "_INPUT_",
      searchPlaceholder: "Search..."
    }
  });

  var $buttons =
    '<div class="ui checked checkbox" id="problemLinks">\
                        <input type="checkbox">\
                        <label>Show only problems</label>\
                    </div>\
                    <button id="addTier1Link" class="btn btn-info but-pos" data-toggle="modal" data-target="#tier1Modal" >Add Tier1 Link</button>\
                    <button id="importTier1CSV" class="btn btn-info but-pos" data-toggle="modal" data-target="#import-tier1-modal">Import</button>';

  $("#clientOption").appendTo("#tier1_filter");
  $("#tier1_filter").append($buttons);
  $("#clientOption").show();

  $(document).on("change", "#clientOption", function() {
    $tier1Table.column(1).search($("#clientOption").val(), true, true).draw();
  });

  $(document).on("change", "#problemLinks input", function(event) {
    if ($(this).prop("checked") == true) {
      $tier1Table
        .column(7)
        .search(
          // '^((?!Success).)*$'
          "^((?!\\Success).)*$",
          true,
          false
        )
        .draw();
    } else {
      $tier1Table.column(7).search("", true, false).draw();
    }
  });
  // Databasetabe -------------------------    end

  //begin import csv
  $(document).on("click", "#importTier1CSV", function(event) {
    event.preventDefault();

    var $modal = $("#import-tier1-modal");

    $("#form-import-Tier1").unbind("submit").bind("submit", function(event) {
      event.preventDefault();

      var fileName = $("#source_file").val(),
        ext = fileName.split(".").pop().toLowerCase();

      if ($.inArray(ext, ["csv"]) == -1) {
        $(".import-description").removeClass("hidden");
        return false;
      } else {
        $(".import-description").addClass("hidden");
      }

      //Clients  Provider  Tier 1 Link  301Y/N  301 URL  Target URL
      var csvData = [];

      if (typeof FileReader != "undefined") {
        var reader = new FileReader();
        reader.onload = function(e) {
          var rows = e.target.result.split("\r\n");
          console.log("here");
          for (var i = 1; i < rows.length; i++) {
            var cells = rows[i].split(",");
            console.log(cells);
            if (cells.length > 1) {
              var data = {};
              data.client = cells[0];
              data.provider = cells[1];
              data.tier1Link = cells[2];
              if (
                cells[3].toLowerCase() == "y" ||
                cells[3].toLowerCase() == "yes"
              ) {
                data.emUrl = cells[4];
                data.targetUrl = "null";
              } else {
                data.emUrl = "null";
                data.targetUrl = cells[6];
              }
              data.anchorText = cells[5];

              csvData[i - 1] = data;
            }
          }

          $.ajax({
            url: $("#form-import-Tier1").attr("action"),
            method: "post",
            timeout: 20000,
            data: {
              _token: $("#form-import-Tier1").find('[name="_token"]').val(),
              data: csvData
            },
            beforeSend: function() {
              // $(this).addClass('loading');
            },
            complete: function() {
              // $(this).removeClass('loading');
            },
            success: function(response) {
              // Begin process response
              if (response.success == true) {
                console.log(response.data);
                for (var i = 0; i < response.data.length; i++)
                  addTier1Link(response.data[i]);
              } else if (response.success == false) {
                alert(response.message);
              }
              // End  process response
              $modal.modal("hide");
            },
            error: function(error) {
              console.log(error);
            }
          });
        };
        reader.readAsText($("#source_file")[0].files[0]);
      } else {
        alert("This browser does not support HTML5.");
        return false;
      }
    });
  });

  //end import csv

  // begin search function
  $("#tier1Modal").on("shown.bs.modal", function() {
    // $("#clientId option:not([value])");
    $("#sel_client").prop("disabled", true);
    $("#sel_client").prop("hidden", true);
    $("#sel_client").prop("selected", true);
    $("#sel_client").val("Select Client");
    $("#sel_provider").prop("disabled", true);
    $("#sel_provider").prop("hidden", true);
    $("#sel_provider").prop("selected", true);
    $("#sel_provider").val("Select Provider");

    $("#tier1Link").val("");
    $("#emUrl").val("");
    $("#anchorText").val("");
    $("#targetUrl").val("");
    $("#radio-disable").prop("checked", true);
    $("#emUrl").prop("disabled", true);
    $("#targetUrl").prop("disabled", false);
  });

  // begin 301Url check, uncheck function

  $(document).on("click", "#radio-enable", function(event) {
    $("#emUrl").prop("disabled", false);
    $("#targetUrl").prop("disabled", true);
    $("#emUrl").val("");
    $("#targetUrl").val("");
  });

  $(document).on("click", "#radio-disable", function(event) {
    $("#emUrl").prop("disabled", true);
    $("#targetUrl").prop("disabled", false);
    $("#emUrl").val("");
    $("#targetUrl").val("");
  });

  $(document).on("click", "#radio-enableEdit", function(event) {
    $("#emUrlEdit").prop("disabled", false);
    $("#targetUrlEdit").prop("disabled", true);
    // $("#emUrlEdit").val("");
    // $("#targetUrlEdit").val("");
  });

  $(document).on("click", "#radio-disableEdit", function(event) {
    $("#emUrlEdit").prop("disabled", true);
    $("#targetUrlEdit").prop("disabled", false);
    // $("#emUrlEdit").val("");
    // $("#targetUrlEdit").val("");
  });
  // End 301Url check, uncheck (Edit function)

  /***** Begin add a new Tier1 form *****/
  $("#form-add-Tier1").unbind("submit").bind("submit", function(event) {
    event.preventDefault();
    var is301Url = $("#radio-enable").prop("checked");

    $.ajax({
      url: $(this).attr("action"),
      method: "post",
      timeout: 20000,
      data: {
        _token: $(this).find('[name="_token"]').val(),
        client_id: $("#clientId").val(),
        provider_id: $("#providerId").val(),
        tier1_link: $("#tier1Link").val(),
        emUrl: is301Url ? $("#emUrl").val() : "null",
        anchor_text: $("#anchorText").val(),
        target_url: is301Url ? "null" : $("#targetUrl").val()
      },
      beforeSend: function() {
        $(this).addClass("loading");
      },
      complete: function() {
        $(this).removeClass("loading");
      },
      success: function(response) {
        // Begin process response
        if (response.success == true) {
          var tier1 = response.data.tier1;

          addTier1Link(tier1);

          $("#tier1Modal").modal("hide");
        } else if (response.success == false) {
          if (response.data.action == "reloadPage") {
            window.showCSRFModal();
            return false;
          }

          // begin show messages
          if (response.messages.length > 0) {
            var $modal = $("#message-modal");

            $modal.find(".content").text(response.messages[0]);
            $modal.modal("show");
          }
          // end show messages
        }
        // End  process response
      },
      error: function(error) {
        var $modal = $("#message-modal");

        if (error.statusText == "timeout") {
          $modal.find(".content").text("The server does not respond");
        } else {
          $modal.find(".content").text("There was a problem, please try agian");
        }

        $modal.modal("show");
      }
    });
  });

  function addTier1Link(tier1) {
    var buttons =
      '<div class="right floated content">\
                            <div class="ui tiny icon button green show-retry-tier1-modal" data-toggle="modal" data-target="#retry-tier1-modal">\
                                <i class="sync icon"></i>\
                            </div>\
                            <div class="ui tiny icon button teal show-edit-tier1-modal" data-toggle="modal" data-target="#editTier1">\
                                <i class="edit icon"></i>\
                            </div>\
                            <div class="ui tiny icon button red show-delete-tier1-modal" data-toggle="modal" data-target="#delete-tier1-modal">\
                                <i class="trash alternate outline icon"></i>\
                            </div>\
                        </div>';

    var $row = $tier1Table.row
      .add([
        "",
        tier1.client_id,
        tier1.provider_id,
        tier1.tier1_link,
        tier1.emUrl,
        tier1.anchor_text,
        tier1.target_url,
        tier1.status,
        buttons
      ])
      .node();

    $($row).find("td:first-child").addClass("counterCell");
    $($row).attr("data-tier1-id", tier1.id);

    $tier1Table.draw();
  }
  /***** End add a new Tier1 form *****/

  /***** Begin delete Tier1 *****/
  $(document).on("click", ".show-delete-tier1-modal", function(event) {
    event.preventDefault();

    var $modal = $("#delete-tier1-modal"),
      $btn = $(this),
      $item = $btn.parents("tr"),
      tier1Id = $item.attr("data-tier1-id"),
      $message = $modal.find(".message");

    $("#form-remove-Tier1").unbind("submit").bind("submit", function(event) {
      event.preventDefault();
      $.ajax({
        url: $(this).attr("action"),
        method: "post",
        timeout: 20000,
        data: {
          _token: $(this).find('[name="_token"]').val(),
          id: tier1Id
        },
        beforeSend: function() {
          $(this).addClass("loading");
        },
        complete: function() {
          $(this).removeClass("loading");
        },
        success: function(response) {
          // Begin process response
          if (response.success == true) {
            $tier1Table.row($item.remove()).remove().draw();
            // $tier1Table.draw();
          } else if (response.success == false) {
            if (response.data.action == "reloadPage") {
              window.showCSRFModal();
              return false;
            }

            // begin show messages
            if (response.messages.length > 0) {
              var $msgModal = $("#message-modal");

              $msgModal.find(".content").text(response.messages[0]);
              $msgModal.modal("show");
            }
            // end show messages
          }
          // End  process response
          $modal.modal("hide");
        },
        error: function(error) {
          var $modal = $("#message-modal");

          if (error.statusText == "timeout") {
            $modal.find(".content").text("The server does not respond");
          } else {
            $modal
              .find(".content")
              .text("There was a problem, please try agian");
          }

          $modal.modal("show");
        }
      });
    });
  });
  /***** End delete Tier1 *****/

  /***** Begin retry Tier2 *****/

  $(document).on("click", ".show-retry-tier1-modal", function(event) {
    event.preventDefault();

    var $btn = $(this),
      $item = $btn.parents("tr"),
      tier1Id = $item.attr("data-tier1-id");
    $(this).addClass("loading");
    $.ajax({
      url: "/tier1-retry",
      method: "post",
      timeout: 90000,
      data: {
        id: tier1Id,
        _token: $('meta[name="csrf-token"]').attr("content")
      },
      success: function(response) {
        $btn.removeClass("loading");
        // Begin process response
        if (response.success == true) {
          var index = $tier1Table.row($item).index();
          var data = $tier1Table.row(index).data();

          data[7] = response.status;

          $tier1Table.row(index).data(data).invalidate();
        } else if (response.success == false) {
          if (response.data.action == "reloadPage") {
            window.showCSRFModal();
            return false;
          }

          // begin show messages
          if (response.messages.length > 0) {
            var $modal = $("#message-modal");

            $modal.find(".content").text(response.messages[0]);
            $modal.modal("show");
          }
          // end show messages
        }
        // End  process response
      },
      error: function(error) {
        console.log(error);
        $btn.removeClass("loading");
      }
    });
  });
  /***** End retry Tier2 *****/

  /***** Begin edit Tier1 *****/
  $(document).on("click", ".show-edit-tier1-modal", function(event) {
    event.preventDefault();

    var $modal = $("#editTier1"),
      $btn = $(this),
      $item = $btn.parents("tr"),
      tier1Id = $item.attr("data-tier1-id"),
      $message = $modal.find(".message"),
      $tier1Row = [];

    for (var $i = 0; $i < 7; $i++) {
      $tier1Row[$i] = $item.children("td:nth-child(" + ($i + 2) + ")").text();
    }

    $("#clientIdEdit").val($tier1Row[0]);
    $("#providerIdEdit").val($tier1Row[1]);
    $("#tier1LinkEdit").val($tier1Row[2]);
    $("#emUrlEdit").val($tier1Row[3]);
    $("#anchorTextEdit").val($tier1Row[4]);
    $("#targetUrlEdit").val($tier1Row[5]);

    var $status = $tier1Row[6];

    if ($("#emUrlEdit").val() == "") {
      $("#radio-disableEdit").prop("checked", true);
      $("#emUrlEdit").prop("disabled", true);
      $("#targetUrlEdit").prop("disabled", false);
    } else {
      $("#radio-enableEdit").prop("checked", true);
      $("#emUrlEdit").prop("disabled", false);
      $("#targetUrlEdit").prop("disabled", true);
    }

    $("#formEditTier1").unbind("submit").bind("submit", function(event) {
      event.preventDefault();
      var is301Url = $("#radio-enableEdit").prop("checked");

      $.ajax({
        url: $(this).attr("action"),
        method: "post",
        timeout: 20000,
        data: {
          id: tier1Id,
          client_id: $("#clientIdEdit").val(),
          provider_id: $("#providerIdEdit").val(),
          tier1_link: $("#tier1LinkEdit").val(),
          emUrl: is301Url ? $("#emUrlEdit").val() : "null",
          anchor_text: $("#anchorTextEdit").val(),
          target_url: is301Url ? "null" : $("#targetUrlEdit").val(),
          status: $status,
          _token: $(this).find('[name="_token"]').val()
        },
        beforeSend: function() {
          $(this).addClass("loading");
        },
        complete: function() {
          $(this).removeClass("loading");
        },
        success: function(response) {
          // Begin process response
          if (response.success == true) {
            var index = $tier1Table.row($item).index();
            var data = $tier1Table.row(index).data();

            data[1] = response.data.client_id;
            data[2] = response.data.provider_id;
            data[3] = response.data.tier1_link;
            data[4] = is301Url ? response.data.emUrl : "";
            data[5] = response.data.anchor_text;
            data[6] = response.data.target_url;

            $tier1Table.row(index).data(data).invalidate();

            $("#editTier1").modal("hide");
          } else if (response.success == false) {
            if (response.data.action == "reloadPage") {
              window.showCSRFModal();
              return false;
            }

            // begin show messages
            if (response.messages.length > 0) {
              var $modal = $("#message-modal");

              $modal.find(".content").text(response.messages[0]);
              $modal.modal("show");
            }
            // end show messages
          }
          // End  process response
          $("#editTier1").modal("hide");
        },
        error: function(error) {
          var $modal = $("#message-modal");

          if (error.statusText == "timeout") {
            $modal.find(".content").text("The server does not respond");
          } else {
            $modal
              .find(".content")
              .text("There was a problem, please try agian");
          }

          $modal.modal("show");
        }
      });
    });
  });
  /***** End edit Tier1 *****/

  /***** Begin initiate message mosal *****/
  $("#message-modal").modal({
    closable: false,
    onHidden: function() {
      var $modal = $("#message-modal"),
        $message = $modal.find(".content");

      $message.text("");
    }
  });
  /***** End initiate message mosal *****/
});
