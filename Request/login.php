<?php
session_start();
    require_once'../Class/User.php';
    require_once'../Class/OTP.php';
    $u=new User();
    $o=new OTP();
    $un=$_POST['un'];
    $pw=$_POST['pw'];
    $data=$u->login($un,$pw);
    if($data!=false){
        if($data['status']==1){
            $_SESSION['user_id']=$data['user_id'];
            $o->send($data['user_id']);
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
                    'data'=> 'User has been locked! Contact your administrator!',
                    'icon'=>'warning'
                ]
            ); 
        }
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