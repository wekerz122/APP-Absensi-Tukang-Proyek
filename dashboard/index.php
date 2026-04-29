<?php
session_start();

if(!isset($_SESSION['role'])){
    header("Location: ../auth/login.php");
    exit;
}

if($_SESSION['role'] == "admin"){
    header("Location: admin.php");
}
elseif($_SESSION['role'] == "spv"){
    header("Location: spv.php");
}
elseif($_SESSION['role'] == "tukang"){
    header("Location: tukang.php");
}
else{
    echo "Role tidak dikenali";
}
