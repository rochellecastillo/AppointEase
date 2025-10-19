<?php
date_default_timezone_set("Asia/Manila");
require_once'Database.php';
Class OTP extends Database{
    public function send($user_id){
        $otp = mt_rand(100000, 999999);
        $time=date("Y-m-d H:i:s");
        $sql="insert into tblotp values(NULL,?,?,?)";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$user_id,$otp,$time]);
    }
    public function display($user_id){
        $sql="select * from tblotp where user_id=? and timegenerated >= NOW() - INTERVAL 5 MINUTE order by id DESC limit 1";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$user_id]);
        $data=$stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }
    public function validateotp($user_id,$otp){
        $valotp=$this->display($user_id);
        if(isset($valotp['otp']) && $valotp['otp']==$otp){
            return true;
        }else{
            return false;
        }
    }
}
?>