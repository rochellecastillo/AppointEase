<?php
require_once'Database.php';
Class User extends Database{
    public function login($un,$pw){
        $sql="select * from tbluser where user_name=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$un]);
        $data=$stmt->fetch(PDO::FETCH_ASSOC);
        if($data){
            if(password_verify($pw,$data['pw'])){
                return $data;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}
?>