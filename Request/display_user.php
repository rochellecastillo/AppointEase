<?php
require_once'../Class/User.php';
$u=new User();
$userid=$_POST['userid'];
$data=$u->displayuserinfo($userid);
if($data){
    echo json_encode([
        'userid'=>$data['user_id'],
        'lastname'=>$data['last_name'],
        'firstname'=>$data['first_name'],
        'middlename'=>$data['middle_name'],
        'gender'=>$data['gender'],
        'bdate'=>$data['bdate'],
        'address'=>$data['address'],
        'contact'=>$data['contact']
    ]);
}
?>
