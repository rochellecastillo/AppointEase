<?php session_start(); ?>
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
                    <th class="text-center"><i class="fa-solid fa-bars"></i></th>
                  </tr>
                  <?php
                    $c=0;
                   foreach($data as $row){
                    $c++;
                    $gender=$row['gender']=='male' ? '<i class="fa-solid fa-mars text-primary"></i>': '<i class="fa-solid fa-venus" style="color:#FFB6C1"></i>';
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
                        <td class='text-center'>
                          <button class='btn text-primary' data-bs-toggle='modal' data-bs-target='#myModal'><i class='fa-solid fa-pen-to-square'></i></button>
                          <button class='btn text-success'><i class='fa-solid fa-print'></i></button>
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
          <div class="modal-header">
            <h4 class="modal-title">Update User Info</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <!-- Modal body -->
          <div class="modal-body">
            Modal body..
          </div>

          <!-- Modal footer -->
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
          </div>

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