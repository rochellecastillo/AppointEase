<?php
session_start();
require_once'Class/Session.php';
new Session();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>AppointEase - Add Employee</title>
  <?php include_once 'Resources/include.php'; ?>
</head>
<body class="text-secondary">
  <?php include_once 'Resources/navbar.php'; ?>

  <div class="container py-4">
    <div class="card mx-auto" id="login-container" style="max-width:900px;">
      <div class="card-body">
        <h4 class="card-title mb-3"><i class="fa-solid fa-user-plus"></i> Add Employee</h4>

        <form id="addEmployeeForm" novalidate>
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">First Name</label>
              <input name="first_name" class="form-control" required />
            </div>
            <div class="col-md-4">
              <label class="form-label">Middle Name</label>
              <input name="middle_name" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Last Name</label>
              <input name="last_name" class="form-control" required />
            </div>

            <div class="col-md-4">
              <label class="form-label">Birthdate</label>
              <input name="bdate" type="date" class="form-control" required />
            </div>

            <div class="col-md-4">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select" required>
                <option value="">Select...</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Contact</label>
              <input name="contact" class="form-control" />
            </div>

            <div class="col-12">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control" rows="2"></textarea>
            </div>

            <div class="col-12 d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-primary" id="btnSubmit"><i class="fa-solid fa-floppy-disk"></i> Add Employee</button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>

  <script>
  $(function(){
    $('#addEmployeeForm').on('submit', function(e){
      e.preventDefault();
      const formData = $(this).serialize();
      $('#btnSubmit').prop('disabled', true).text('Saving...');

      $.ajax({
        url: 'Request/add_user.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(resp){
          $('#btnSubmit').prop('disabled', false).text('Add Employee');
          if(resp.success){
            Swal.fire({ icon: resp.icon, title: 'Add User', text: resp.message });
            $('#addEmployeeForm')[0].reset();
          } else {
            Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
          }
        },
        error: function(xhr){
          $('#btnSubmit').prop('disabled', false).text('Add Employee');
          Swal.fire({ icon: 'error', title: 'Request Failed', text: xhr.responseText || 'Something went wrong.' });
        }
      });
    });
  });
  </script>
</body>
</html>

<style>
  #login-container {
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
  }
</style>