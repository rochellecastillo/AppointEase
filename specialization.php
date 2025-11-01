<?php
session_start();
require_once'Class/Session.php';
require_once'Class/User.php';
$u=new User();
new Session();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>AppointEase - Specialization</title>
  <?php include_once 'Resources/include.php'; ?>
</head>
<body class="text-secondary">
  <?php include_once 'Resources/navbar.php'; ?>
  <?php
  if(isset($_POST['btnadd'])){
    $specialization=$_POST['specialization'];
    $data=$u->addspecialization($specialization);
    echo'
        <script>
            Swal.fire({
                title: "Add Specialization",
                text: "'.$data['message'].'",
                icon: "'.$data['icon'].'"
            });
        </script>
    ';
  }else if(isset($_POST['btndelete'])){
    $id=$_POST['btndelete'];
    $data=$u->deletespecialization($id);
    echo'
        <script>
            Swal.fire({
                title: "Delete Specialization",
                text: "'.$data['message'].'",
                icon: "'.$data['icon'].'"
            });
        </script>
    ';
  }
  $data=$u->displayspecialization();
  ?>
  <div class="container">
    <form method="POST">
        <div class="row mt-3">
            <div class="col-md-4">
                <label for="specialization">Specialization<i class="text-danger">*</i></label>
                <input type="text" class="form-control" name="specialization" placeholder="Medical Specialty..." required>
            </div>
            <div class="col-md-2">
                <label for="">&nbsp;</label>
                <button type="submit" class="form-control btn btn-primary" name="btnadd">Add</button>
            </div>
        </div>
    </form>
    <div class="row mt-3">
        <div class="col-md-6">
            <table class="table">
                <tr>
                    <th>#</th>
                    <th>Specialties</th>
                    <th class="text-center"><i class="fa-solid fa-bars"></i></th>
                </tr>
                <?php
                    $c=0;
                    foreach($data as $row){
                        $c++;
                        echo'
                        <form method="POST">
                            <tr>
                                <td>'.$c++.'</td>
                                <td>'.$row['specialization'].'</td>
                                <td class="text-center"><button type="submit" class="btn shadow-none" value="'.$row['id'].'" name="btndelete"><i class="fa-solid fa-trash"></i></button></td>
                            </tr>
                        </form>
                        ';
                    }
                ?>
            </table>
        </div>
    </div>
  </div>

</body>
</html>

<style>
  #login-container {
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
  }
</style>
