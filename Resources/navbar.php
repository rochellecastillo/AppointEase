<?php
  if($_SESSION['role']=='admin'){$homepage='main.php';}
  else if($_SESSION['role']=='user'){
    $homepage='usermain.php';
    $aptcount=$a->appointmentbadge($_SESSION['user_id']);
  }else if($_SESSION['role']=='client'){$homepage='clientmain.php';}
?>
<nav class="navbar navbar-expand-sm bg-primary navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"><img src="Resources/Images/logo.png" height="30px" alt="AppointEase"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="<?=$homepage?>">Home <?php if(isset($aptcount)){echo '<div class="badge bg-danger">'.$aptcount.'</div>';}?></a>
        </li>
        <?php
          if($_SESSION['role']=='admin'){
        ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Doctor</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="adminadduser.php">Add New</a></li>
              <li><a class="dropdown-item" href="adminshowusers.php">Update</a></li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="specialization.php">Specialization</a>
          </li>
        <?php
          }else if($_SESSION['role']=='client'){
            echo'
              <li class="nav-item">
                <a class="nav-link" href="book.php">Appointment</a>
              </li>
          ';
          }else if($_SESSION['role']=='user'){
            

          }
        ?>
        <li class="nav-item">
          <a class="nav-link" href="about.php">About Us</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="modal" href="#" data-bs-target="#passwordmodal">Change Password</a>
        </li>
      </ul>
    </div>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="Resources/logout.php">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<form id="passwordform">
<!-- The Modal -->
<div class="modal" id="passwordmodal">
  <div class="modal-dialog">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title"><i class="fa-solid fa-key"></i> Change Password</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="container">
          <div class="row mt-3 justify-content-center">
            <div class="col-md-10">
              <label for="pw">Enter Password<i class="text-danger">*</i></label>
              <input type="password" class="form-control text-center" name="cpw" id="cpw" required>
            </div>
          </div>
          <div class="row mt-3 justify-content-center">
            <div class="col-md-10">
              <label for="pw">Enter New Password<i class="text-danger">*</i></label>
              <input type="password" class="form-control text-center" name="cpw2" id="cpw2" required>
            </div>
          </div>
          <div class="row mt-3 justify-content-center">
            <div class="col-md-10">
              <label for="pw">Re-Enter New Password<i class="text-danger">*</i></label>
              <input type="password" class="form-control text-center" name="cpw3" id="cpw3" required>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Change Password</button>
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
      </div>
      
    </div>
  </div>
</div>
</form>


<script>
$(document).ready(function() {
    // Custom strong password rule
    $.validator.addMethod("strongPassword", function(value, element) {
        return this.optional(element) 
            || /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(value);
    }, "Password must be at least 8 characters long and include uppercase, lowercase, number, and symbol.");

    // Validation rules
    $('#passwordform').validate({
        rules:{
            cpw:{ required: true, minlength: 5 },
            cpw2:{ required: true, strongPassword: true },
            cpw3:{ required: true, equalTo: "#cpw2" }
        },
        messages:{
            cpw:{ required: 'Please enter your current password', minlength: 'Minimum 5 characters' },
            cpw2:{ required: 'Please enter a new password' },
            cpw3:{ required: 'Please confirm your new password', equalTo: 'Passwords do not match' }
        },
        submitHandler: function(form, event) {
            event.preventDefault(); 
            var cpw = $('#cpw').val();
            var cpw2 = $('#cpw2').val();
            var cpw3 = $('#cpw3').val();
            var userid = "<?php echo $_SESSION['user_id']; ?>";

            $.ajax({
                url: 'Request/changepassword.php',
                method: 'POST',
                dataType: 'json',
                data: { userid: userid, cpw: cpw, cpw2: cpw2, cpw3: cpw3 },
                success: function(response) {
                    Swal.fire({
                        title: response.success ? 'Success' : 'Warning',
                        text: response.message,
                        icon: response.icon
                    });

                    // Optionally clear form
                    if (response.success) {
                        //$('#passwordform')[0].reset();
                        //$('#passwordmodal').modal('hide');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Something went wrong while changing your password.',
                        icon: 'error'
                    });
                }
            });

            return false; // ✅ Double safety to prevent reload
        }
    });
});
</script>
