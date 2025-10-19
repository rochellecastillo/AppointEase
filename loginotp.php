<?php
session_start();
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
    $u=new User();
    $data=$u->displayuserinfo($_SESSION['user_id']);
    if(isset($_POST['btnverify'])){
        $otp=$_POST['otp'];
        echo'
            <script>
                alert("'.$otp.'");
            </script>
        ';
    }
    ?>
    <form method="POST">
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-4 p-4 m-2 border rounded" id="login-container">
                    <div class="row mt-2">
                        <h3 class="text-center"><i class="fa-solid fa-lock"></i>Login</h3>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12 text-center">
                            <img class="w-50" src="Resources/Images/logo.png" alt="">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <h5 class="text-center">Please enter OTP we sent to <?=$data['contact']?></h5>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="otp"><i class="fa-solid fa-lock"></i> OTP</label>
                            <input type="text" class="form-control text-center" name="otp" id="otp" placeholder="Enter 6-digits OTP">
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