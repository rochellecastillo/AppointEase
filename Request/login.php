<?php
session_start();
    require_once'../Class/User.php';
    $u=new User();
    $un=$_POST['un'];
    $pw=$_POST['pw'];
    $data=$u->login($un,$pw);
    if($data!=false){
        $_SESSION['user_id']=$data['user_id'];
        echo json_encode(
            [
                'success'=> true,
                'data'=> $data,
                'icon'=>'success'
            ]
        );
    }else{
        echo json_encode(
            [
                'success'=> false,
                'data'=> 'invalid username or password',
                'icon'=>'warning'
            ]
        );
    }
?>