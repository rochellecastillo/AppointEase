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
  <title>AppointEase - Show Employee</title>
  <?php include_once 'Resources/include.php'; ?>
</head>
<body class="text-secondary">
  <?php include_once 'Resources/navbar.php'; ?>
  <?php
    require_once'Class/User.php';
    $u=new User();
    if(isset($_POST['btnupdate'])){
      $userid=$_POST['userid'];
      $ln=$_POST['ln'];
      $fn=$_POST['fn'];
      $mn=$_POST['mn'];
      $gender=$_POST['gender'];
      $bdate=$_POST['bdate'];
      $address=$_POST['address'];
      $contact=$_POST['contact'];
      $data=$u->updateuserinfo($userid,$ln,$fn,$mn,$bdate,$gender,$address,$contact);
      echo'
      <script>
        Swal.fire({
          title: "Update Status",
          text: "'.$data['message'].'",
          icon: "'.$data['icon'].'"
        });
      </script>
      ';
    }
    $data=$u->displayallusers();
  ?>
    <div class="container">
        <div class="row mt-3">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table text-nowrap">
                  <tr>
                    <th>#</th>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Birth Date</th>
                    <th>Gender</th>
                    <th>Address</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th class="text-center"><i class="fa-solid fa-bars"></i></th>
                  </tr>
                  <?php
                    $c=0;
                   foreach($data as $row){
                    $c++;
                    $gender=$row['gender']=='male' ? '<i class="fa-solid fa-mars text-primary"></i>': '<i class="fa-solid fa-venus" style="color:#FFB6C1"></i>';
                    $status=$row['status']==1 ? 'checked':'';
                    echo"
                      <tr>
                        <td>{$c}</td>
                        <td>
                          <div>{$row['user_id']}</div>
                          <div id='role'><small>{$row['user_type']}</small></div>
                        </td>
                        <td class='fw-bold'>{$row['last_name']}, {$row['first_name']} - {$row['middle_name']}</td>
                        <td>{$row['bdate']}</td>
                        <td class='text-center'>{$gender}</td>
                        <td>{$row['address']}</td>
                        <td>{$row['contact']}</td>
                        <td class='text-center'><input type='checkbox' class='form-check-input stat' name='stat' {$status} value='{$row['user_id']}'></td>
                        <td class='text-center'>
                          <button class='btn text-primary btnedit' value='{$row['user_id']}' data-bs-toggle='modal' data-bs-target='#myModal'><i class='fa-solid fa-pen-to-square'></i></button>
                          <button class='btn text-success btnprint' value='{$row['user_id']}' ><i class='fa-solid fa-print'></i></button>
                          <button class='btn text-danger btnschedule' value='{$row['user_id']}' data-bs-toggle='modal' data-bs-target='#schedmodal'><i class='fa-solid fa-calendar-days'></i></button>
                        </td>
                      </tr>
                    ";
                   }
                  ?>
                </table>
              </div>
            </div>
        </div>
    </div>




    <!-- The Modal -->
    <div class="modal" id="myModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <!-- Modal Header -->
          <div class="modal-header bg-primary text-white">
            <h4 class="modal-title"><i class="fa-solid fa-pencil"></i> Update Doctor's Info</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <!-- Modal body -->
          <form method="POST" >
          <div class="modal-body">
            <div class="container">
              <div class="row">
                <div class="col-md-5">
                  <label for="ln">User ID</label>
                  <input type="text" class="form-control" name="userid" id="userid" readonly>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-4">
                  <label for="ln">Last Name</label>
                  <input type="text" class="form-control" name="ln" id="ln" required>
                </div>
                <div class="col-md-4">
                  <label for="ln">First Name</label>
                  <input type="text" class="form-control" name="fn" id="fn" required>
                </div>
                <div class="col-md-4">
                  <label for="ln">Middle Name</label>
                  <input type="text" class="form-control" name="mn" id="mn">
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-4">
                  <label for="gender">Gender</label>
                  <select name="gender" id="gender" class="form-control">
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label for="bdate">Birth Date</label>
                  <input type="date" class="form-control" name="bdate" id="bdate" required>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-12">
                  <label for="address">Address</label>
                  <input type="text" class="form-control" name="address" id="address" required>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-5">
                  <label for="contact">Contact</label>
                  <input type="tel" class="form-control" name="contact" id="contact" required>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Modal footer -->
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary" name="btnupdate"><i class="fa-solid fa-floppy-disk"></i> Update</button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
          </div>
        </form>

        </div>
      </div>
    </div>

    <!-- The Modal -->
    <div class="modal" id="schedmodal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <!-- Modal Header -->
          <div class="modal-header bg-primary text-white">
            <h4 class="modal-title"><i class="fa-solid fa-calendar"></i> Doctor's Schedule</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <!-- Modal body -->
          <form method="POST" >
          <div class="modal-body">
            <div class="container">
              <div class="row">
                <div class="d-flex">
                  <div><img src="Resources/Images/default_profile.webp" height="90px" alt=""></div>
                  <div>
                    <div><h3 id="drname">Juan dela Cruz</h3></div>
                    <div id="userid2">Doctor's ID</div>
                    <div id="specialty">Medical Specialty</div>
                  </div>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-3">
                  <label for="day">Day</label>
                  <select class="form-control" name="day" id="day" required>
                      <option value=NULL selected disabled>-</option>
                      <option value="1">Monday</option>
                      <option value="2">Tuesday</option>
                      <option value="3">Wednesday</option>
                      <option value="4">Thursday</option>
                      <option value="5">Friday</option>
                      <option value="6">Saturday</option>
                      <option value="7">Sunday</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label for="time">Start Time</label>
                   <input type="time" class="form-control" name="time1" id="time1" required>
                </div>
                <div class="col-md-3">
                  <label for="time">End Time</label>
                   <input type="time" class="form-control" name="time2" id="time2" required>
                </div>
                <div class="col-md-3">
                  <label for="appointmentlimit">Max Appointment</label>
                   <input type="number" class="form-control" min="1" name="appointmentlimit" id="appointmentlimit" required>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-12">
                  <button type="submit" class="btn btn-primary" name="btnadd"><i class="fa-solid fa-floppy-disk"></i> Add Schedule</button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Modal footer -->
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
          </div>
        </form>

        </div>
      </div>
    </div>
</body>
</html>

<style>
  #login-container {
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
  }
  #role{
    margin-top:-0.6em;
    color:blue;
  }
</style>
<script>
  $(document).ready(function(){
    let btnprint=$('.btnprint');
    let btnschedule=$('.btnschedule');
    let btnedit=$('.btnedit');
    btnprint.click(function(){
      window.open("Report/printuserdata.php?userid="+$(this).val());
    });
    btnedit.click(function(){
      //let formData = $('#myForm').serialize();
      $.ajax({
          url: 'Request/display_user.php',
          method: 'POST',
          dataType: 'json',
          data: {userid: $(this).val()},
          success: function(response){
              //alert(response.userid);
              $('#userid').val(response.userid);
              $('#ln').val(response.lastname);
              $('#fn').val(response.firstname);
              $('#mn').val(response.middlename);
              $('#gender').val(response.gender);
              $('#bdate').val(response.bdate);
              $('#address').val(response.address);
              $('#contact').val(response.contact);
          }
      });
    });
    let chk=$('.stat');
    chk.click(function(){
      //alert($(this).is(':checked'));
      $.ajax({
          url: 'Request/changestatus.php',
          method: 'POST',
          dataType: 'json',
          data: {userid: $(this).val(),stat: $(this).is(':checked')},
          success: function(response){
              if(response.success==true){
                   Swal.fire({
                      title: 'Success',
                      text: response.message,
                      icon: response.icon
                  });
              }else{
                  Swal.fire({
                      title: 'Warning',
                      text: response.data,
                      icon: response.icon
                  });
              }
          }
      });

    });
    $(".btnschedule").click(function(){
      $.ajax({
          url: 'Request/display_user.php',
          method: 'POST',
          dataType: 'json',
          data: {userid: $(this).val()},
          success: function(response){
              $('#userid2').html(response.userid);
              $('#drname').html(response.lastname+', '+ response.firstname);
          }
      });
    });
  });
</script>