<?php

/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

session_start();


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
| This function prevents users from accessing protected pages
| without logging in.
*/

function require_login()
{
    if (!isset($_SESSION["user_id"])) {

        header("Location: login.php");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN
|--------------------------------------------------------------------------
| This function allows only administrators to access
| admin-only pages.
*/

function require_admin()
{
    require_login();

    if ($_SESSION["role"] !== "admin") {

        die("Access denied. Administrator permission required.");
    }
}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML OUTPUT
|--------------------------------------------------------------------------
| Helps protect the application from XSS attacks.
*/

function e($value)
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>