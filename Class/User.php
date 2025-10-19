<?php
require_once'Database.php';
Class User extends Database{
    public function login($un,$pw){
        $sql="select u.*,i.contact from tbluser u inner join tblinfo i on u.user_id=i.user_id where u.user_name=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$un]);
        $data=$stmt->fetch(PDO::FETCH_ASSOC);
        if($data){
            //if(password_verify($pw,$data['password'])){
            if($pw==$data['password']){
                return $data;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    public function displayuserinfo($userid){
        $sql="select i.*,u.user_name,u.password,u.user_type from tblinfo i inner join tbluser u on i.user_id =u.user_id where i.user_id=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$userid]);
        $data=$stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }
}
?>