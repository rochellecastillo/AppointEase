<?php
require_once'Database.php';
Class Report extends Database{
    public function userinfo($userid){
        $sql="select i.*,u.user_name,u.password,u.user_type from tblinfo i inner join tbluser u on i.user_id =u.user_id where i.user_id=?";

    }  
}
?>