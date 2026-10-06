<?php

session_start();

if (isset($_SESSION["user_id"])) {

    if ($_SESSION["role"] === "admin") {
        header("Location: dashboard.php");
    } else {
        header("Location: pos.php");
    }

    exit;
}

header("Location: login.php");
exit;

?>