<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppointEase</title>
    <?php include_once'Resources/include.php'?>
</head>
<body class="text-secondary">
    <form id="loginform">
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
                            <input type="text" class="form-control text-center" name="un" id="un">
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
                            <button class="btn btn-primary rounded-pill form-control"><i class="fa-solid fa-right-to-bracket"></i> Sign In</button>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12 text-end">
                            No account yet? <a href="signup.php">Sign-up</a>
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

<script>
    $(document).ready(function(){
        $('#loginform').validate({
            rules:{
                un:{
                    required: true,
                    minlength: 5
                },
                pw:{
                    required: true,
                    minlength: 8
                }
            },
            messages:{
                un:{
                    required: 'Please enter a username',
                    minlength: 'username must be atleast 5 characters'
                },
                pw:{
                    required: 'Please enter a password',
                    minlength: 'Pasword must be atleast 8 characters'
                }
            },
            submitHandler:function(form,event){
                event.preventDefault();
                var un=$('#un').val();
                var pw=$('#pw').val();
                $.ajax({
                    url: 'Request/login.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {un: un,pw: pw},
                    success: function(response){
                        if(response.success==true){
                            window.open("loginotp.php","_self");
                        }else{
                            Swal.fire({
                                title: 'Warning',
                                text: response.data,
                                icon: response.icon
                            });
                        }
                    }
                });
            }
        });

    });
</script>