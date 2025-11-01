<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppointEase - Signup</title>
    <?php include_once'Resources/include.php'?>
</head>
<body class="text-secondary">
    <?php
    include_once'Class/User.php';
    $u=new User();
         if($_SERVER['REQUEST_METHOD']=='POST'){
            $ok=1;
            $un=$_POST['un'];
            $pw=$_POST['pw'];
            $ln=$_POST['ln'];
            $fn=$_POST['fn'];
            $mn=$_POST['mn'];
            $bdate=$_POST['bdate'];
            $gender=$_POST['gender'];
            $address=$_POST['address'];
            $contact=$_POST['contact'];
            if (!preg_match("/^.{5,}$/", $un)) {
                $unErr = "Username must be at least 5 characters long.";
                $ok=0;
            }
            if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\W).{8,}$/", $pw)) {
                $pwErr = "Password must be at least 8 characters long, include an uppercase letter, lowercase letter, and a symbol.";
                $ok=0;
            }
            if($ok==1){
                $data=$u->adduser($ln,$fn,$mn,'',$bdate,$gender,$address,$contact,'client','',$un,$pw);
                echo'
                <script>
                    Swal.fire({
                        title: "Sign Up",
                        text: "'.$data['message'].'",
                        icon: "'.$data['icon'].'"
                        });
                    </script>
                    ';
            }
        }
        $un=isset($_POST['un'])?$un:'';
        $pw=isset($_POST['pw'])?$pw:'';
        $ln=isset($_POST['ln'])?$ln:'';
        $fn=isset($_POST['fn'])?$fn:'';
        $mn=isset($_POST['mn'])?$mn:'';
        $bdate=isset($_POST['bdate'])?$bdate:'';
        $gender=isset($_POST['gender'])?$gender:'';
        $address=isset($_POST['address'])?$address:'';
        $contact=isset($_POST['contact'])?$contact:'';
    ?>
    <form id="loginform" action="signup.php" method="POST">
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-4 p-4 m-2 border rounded" id="login-container">
                    <div class="row mt-2">
                        <h3 class="text-center"><i class="fa-solid fa-right-to-bracket"></i> Signup</h3>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12 text-center">
                            <img class="w-25" src="Resources/Images/logo.png" alt="">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="un"><i class="fa-solid fa-user"></i> Username</label>
                            <input type="text" class="form-control" name="un" id="un" value="<?=$un?>" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="pw"><i class="fa-solid fa-key"></i> Password</label>
                            <input type="password" class="form-control" name="pw" id="pw" value="<?=$pw?>" required>
                            <div class="text-danger"><small><?php if(isset($pwErr)){echo $pwErr;}?></small></div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="ln">Last Name</label>
                            <input type="text" class="form-control" name="ln" id="ln" value="<?=$ln?>" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="fn">First Name</label>
                            <input type="text" class="form-control" name="fn" id="fn" value="<?=$fn?>" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="mn">Middle Name</label>
                            <input type="text" class="form-control" name="mn" id="mn" value="<?=$mn?>">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="bdate">Birth Date</label>
                            <input type="date" class="form-control" name="bdate" id="bdate" value="<?=$bdate?>" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="pw">Gender</label>
                            <select name="gender" id="gender" class="form-control" required>
                                <option disabled <?= empty($gender) ? 'selected' : '' ?>>-</option>
                                <option value="male" <?= ($gender == 'male') ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($gender == 'female') ? 'selected' : '' ?>>Female</option>
                            </select>

                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="bdate">Address</label>
                            <input type="text" class="form-control" name="address" id="address" value="<?=$address?>" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="bdate">Contact</label>
                            <input type="tel" class="form-control" name="contact" id="contact" value="<?=$contact?>" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button class="btn btn-primary rounded-pill form-control"><i class="fa-solid fa-right-to-bracket"></i> Sign Up</button>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12 text-end">
                            <a href="index.php">Back to login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
</body>
</html>
<style>
    #login-container{
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
</style>
