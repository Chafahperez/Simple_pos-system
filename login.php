<?php

session_start();

require_once "config.php";

$error = "";


/*
|--------------------------------------------------------------------------
| CHECK IF FORM WAS SUBMITTED
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT id, name, email, password, role
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        $user = $result->fetch_assoc();


        /*
        |--------------------------------------------------------------------------
        | CHECK PASSWORD
        |--------------------------------------------------------------------------
        */

        if ($user && password_verify($password, $user["password"])) {

            /*
            |--------------------------------------------------------------------------
            | STORE USER INFORMATION IN SESSION
            |--------------------------------------------------------------------------
            */

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["role"] = $user["role"];


            /*
            |--------------------------------------------------------------------------
            | REDIRECT BASED ON ROLE
            |--------------------------------------------------------------------------
            */

            if ($user["role"] === "admin") {

                header("Location: dashboard.php");

            } else {

                header("Location: pos.php");
            }

            exit;

        } else {

            $error = "Invalid email or password.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>POS Login</title>

    <link rel="stylesheet"
          href="css/style.css">

</head>

<body>

<div class="login-container">

    <div class="login-box">

        <h1>Simple POS System</h1>

        <h2>Login</h2>


        <?php if ($error !== ""): ?>

            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                required
            >


            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                required
            >


            <button type="submit">
                Login
            </button>

        </form>

    </div>

</div>

</body>

</html>