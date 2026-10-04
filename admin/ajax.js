$(document).ready(function () {
  $.ajax({
    type: 'GET',              // Change method to GET
    success: function(response) {
        console.log(response);
        // Check if response is not empty
        if (response && response.length > 0) {
            // Assuming only one pending request to show by default
            const request = response[0];
            // Update modal content dynamically
            $('#modal-request-id').text(request.id);
            $('#modal-student-request').text(request.student_request);
            $('#modal-request-status').text(request.request_status);
            $('#modal-request-date').text(request.request_date);
            $('#modal-admin-notes').text(request.admin_notes);
            
            // Open modal by triggering it
            $('#exampleModal').modal('show');
        }
    },
    error: function() {
        alert('Error fetching request data');
    }
});


  
  
  // ============== Add Student ============== //
  $("#admin_add").click(function (e) {
    e.preventDefault();

    var fname = $("#fname").val();
    var mname = $("#mname").val();
    var lname = $("#lname").val();
    var username = $("#username").val();
    var password = $("#password").val();

    console.log(fname);

    $.ajax({
      type: "POST",
      url: "code.php",
      data: {
        add_admin_btn: true,
        fname: fname,
        mname: mname,
        lname: lname,
        username: username,
        password: password,
      },

      success: function (response) {
        $("#adminADD").modal("hide");
        console.log(response);

        // Clear form fields by resetting the form
        $("#adminFormAdd")[0].reset();

        Swal.fire({
          title: "Successfully",
          text: response,
          icon: "success",
          confirmButtonText: "Ok",
        }).then(function () {
          // Reload the page after the Swal.fire alert is closed
          window.location.reload();
        });
      },
    });
  });

  //View admin
  $(".view_btn").click(function () {
    // Retrieve the admin ID from the data attribute
    var adminId = $(this).data("admin-id");

    // Use the admin ID to fetch data or perform other actions
    console.log("Admin ID:", adminId);

    $.ajax({
      type: "POST",
      url: "code.php",
      data: { adminId: adminId },
      success: function (response) {
        var adminDetails = JSON.parse(response);

        // Populate the input fields in the "View" modal
        $("#adminVIEW #fname").val(adminDetails.firstname);
        $("#adminVIEW #mname").val(adminDetails.middlename);
        $("#adminVIEW #lname").val(adminDetails.lastname);
        $("#adminVIEW #username").val(adminDetails.username);
        $("#adminVIEW #password").val(adminDetails.password);
      },
      error: function () {
        console.log("Error fetching admin details.");
      },
    });
  });

  $(".edit_btn").click(function () {
    var adminId = $(this).data("admin-id");

    console.log("Admin ID:", adminId);

    $.ajax({
      type: "POST",
      url: "code.php",
      data: { adminId: adminId },
      success: function (response) {
        var adminDetails = JSON.parse(response);

        $("#adminEDIT #aID").val(adminDetails.id);
        $("#adminEDIT #fname").val(adminDetails.firstname);
        $("#adminEDIT #mname").val(adminDetails.middlename);
        $("#adminEDIT #lname").val(adminDetails.lastname);
        $("#adminEDIT #username").val(adminDetails.username);
        $("#adminEDIT #password").val(adminDetails.password);
      },
      error: function () {
        console.log("Error fetching admin details.");
      },
    });
  });

  $("#admin_update").click(function () {
    var updateData = {
      aID: $("#adminEDIT #aID").val(),
      fname: $("#adminEDIT #fname").val(),
      mname: $("#adminEDIT #mname").val(),
      lname: $("#adminEDIT #lname").val(),
      username: $("#adminEDIT #username").val(),
      password: $("#adminEDIT #password").val(),
      updateAdmin: true,
    };

    // Send an AJAX request to update the admin details
    $.ajax({
      type: "POST",
      url: "code.php",
      data: updateData,
      success: function (response) {
        $("#adminEDIT").modal("hide");
        console.log(response);

        Swal.fire({
          title: "Successfully",
          text: response,
          icon: "success",
          confirmButtonText: "Ok",
        }).then(function () {
          // Reload the page after the Swal.fire alert is closed
          window.location.reload();
        });
      },
      error: function () {
        console.log("Error updating admin details.");
      },
    });
  });

  $(".remove_btn").click(function () {
    var adminId = $(this).data("admin-id");

    $.ajax({
      type: "POST",
      url: "code.php",
      data: { adminId: adminId },
      success: function (response) {
        var adminDetails = JSON.parse(response);

        // Populate the input field in the "Delete" modal
        $("#adminDELETE #aID").val(adminDetails.id);
      },
      error: function () {
        console.log("Error fetching admin details.");
      },
    });
  });

  $("#admin_delete").click(function () {
    var adminId = $("#adminDELETE .admin-id").val();

    // Send an AJAX request to delete the admin
    $.ajax({
      type: "POST",
      url: "code.php",
      data: { adminId: adminId, deleteAdmin: true },
      success: function (response) {
        Swal.fire({
          title: "Successfully",
          text: "Admin deleted Successfully",
          icon: "success",
          confirmButtonText: "Ok",
        }).then(function () {
          // Reload the page after the Swal.fire alert is closed
          window.location.reload();
        });
      },
      error: function () {
        console.log("Error deleting admin.");
      },
    });
  });
});
