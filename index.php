<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppointEase</title>
    <?php include_once'Resources/include.php'?>
</head>
<body class="text-secondary">
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
                    <div class="col-md-12">
                        <label for="un"><i class="fa-solid fa-user"></i> Username</label>
                        <input type="email" class="form-control text-center" name="un" name="un">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label for="pw"><i class="fa-solid fa-key"></i> Password</label>
                        <input type="password" class="form-control text-center" name="pw" id="pw">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button class="btn btn-primary rounded-pill form-control" name="btnsignin" id="btnsignin"><i class="fa-solid fa-right-to-bracket"></i> Sign In</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>
<style>
    #login-container{
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
</style>