<?php
Class Session{
    public function __construct(){
        if(!isset($_SESSION['user_id'])){
            header('location: index.php');
        }
    }
}
?>