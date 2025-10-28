<?php
session_start();
$user_id=$_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppointEase</title>
    <?php include_once'Resources/include.php'?>
</head>
<body class="text-secondary">
    <?php
    require_once'Class/User.php';
    require_once'Class/OTP.php';
    require_once'Class/SMS.php';
    $u=new User();
    $o=new OTP();
    $data=$u->displayuserinfo($user_id);
    $otpdata= $o->display($user_id);
    if ($otpdata && isset($otpdata['otp'])) {
        echo $otpdata['otp'];
        $contact=$data['contact'];
        $message='Your One-Time PIN is '.$otpdata['otp'].'. Please do not share this with anyone';
        new SMS($contact,$message);
    }
    if(isset($_POST['btnverify'])){
        $otp=$_POST['otp'];
        $verify=$o->validateotp($user_id,$otp);
        if($verify==true){
            if($_SESSION['role']=='admin'){
                header('location: main.php');
            }else if($_SESSION['role']=='user'){
                header('location: usermain.php');
            }else if($_SESSION['client']=='user'){
                header('location: clientmain.php');
            }
        }else{
            echo'
                <script>
                    Swal.fire({
                        title: "Warning",
                        text: "Incorrect or expired OTP",
                        icon: "warning"
                    });
                </script>
            ';
        }
    }
    ?>
    <form method="POST">
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-4 p-4 m-2 border rounded" id="login-container">
                    <div class="row mt-2">
                        <div class="col-md-12 text-center">
                            <img class="w-50" src="Resources/Images/logo.png" alt="">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <h6 class="text-center">A One-Time Password (OTP) has been sent to <?=$data['contact']?></h6>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="otp"><i class="fa-solid fa-lock"></i> OTP</label>
                            <input type="text" class="form-control text-center" name="otp" id="otp" placeholder="Enter 6-digits OTP" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button class="btn btn-primary rounded-pill form-control" name="btnverify"><i class="fa-solid fa-circle-check"></i> Verify</button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <a href="Resources/logout.php"><button type="button" class="btn btn-secondary rounded-pill form-control"><i class="fa-solid fa-rotate-left"></i> Back</button></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</body>
</html>
<style>

</style>