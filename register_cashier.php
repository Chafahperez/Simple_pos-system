<?php

session_start();

require_once "config.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
| Only administrators should be allowed to create cashier accounts.
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Only administrators can create cashier accounts.");
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";


/*
|--------------------------------------------------------------------------
| REGISTER CASHIER
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form values
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($name === "") {

        $message = "Please enter the cashier's name.";
        $message_type = "error";

    } elseif ($email === "") {

        $message = "Please enter the cashier's email.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif ($password === "") {

        $message = "Please enter a password.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK IF EMAIL ALREADY EXISTS
        |--------------------------------------------------------------------------
        */

        $check_sql = "
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {

            $message = "Database error: " . $conn->error;
            $message_type = "error";

        } else {

            $check_stmt->bind_param("s", $email);

            $check_stmt->execute();

            $check_stmt->store_result();


            if ($check_stmt->num_rows > 0) {

                $message = "An account with this email already exists.";
                $message_type = "error";

            } else {

                /*
                |--------------------------------------------------------------------------
                | HASH PASSWORD
                |--------------------------------------------------------------------------
                */

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /*
                |--------------------------------------------------------------------------
                | INSERT CASHIER
                |--------------------------------------------------------------------------
                */

                $insert_sql = "
                    INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        role
                    )
                    VALUES (?, ?, ?, 'cashier')
                ";

                $insert_stmt = $conn->prepare($insert_sql);


                if (!$insert_stmt) {

                    $message = "Database error: " . $conn->error;
                    $message_type = "error";

                } else {

                    $insert_stmt->bind_param(
                        "sss",
                        $name,
                        $email,
                        $hashed_password
                    );


                    if ($insert_stmt->execute()) {

                        /*
                        |--------------------------------------------------------------------------
                        | SUCCESS
                        |--------------------------------------------------------------------------
                        */

                        header(
                            "Location: register_cashier.php?success=1"
                        );

                        exit;

                    } else {

                        $message =
                            "Failed to create cashier account: "
                            . $insert_stmt->error;

                        $message_type = "error";
                    }

                    $insert_stmt->close();
                }
            }

            $check_stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

if (isset($_GET["success"]) && $_GET["success"] == "1") {

    $message = "Cashier account created successfully.";
    $message_type = "success";
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Create Cashier Account - SimplePOS</title>


<style>

    * {
        box-sizing: border-box;
    }


    body {

        margin: 0;

        font-family:
            Arial,
            Helvetica,
            sans-serif;

        background: #f4f6f8;

        color: #1f2937;
    }


    /* =========================================================
       SIDEBAR
    ========================================================= */

    .sidebar {

        position: fixed;

        left: 0;
        top: 0;

        width: 240px;

        height: 100vh;

        background: #111827;

        padding: 25px 15px;
    }


    .logo {

        color: white;

        font-size: 25px;

        font-weight: bold;

        text-align: center;

        margin-bottom: 35px;
    }


    .sidebar a {

        display: block;

        color: #d1d5db;

        text-decoration: none;

        padding: 14px 18px;

        margin-bottom: 8px;

        border-radius: 8px;

        transition: 0.2s;
    }


    .sidebar a:hover {

        background: #374151;

        color: white;
    }


    .sidebar a.active {

        background: #2563eb;

        color: white;
    }


    /* =========================================================
       MAIN CONTENT
    ========================================================= */

    .main {

        margin-left: 240px;

        padding: 40px;
    }


    .page-header {

        display: flex;

        justify-content: space-between;

        align-items: center;

        margin-bottom: 30px;
    }


    .page-header h1 {

        margin: 0;

        font-size: 30px;
    }


    .page-header p {

        margin: 8px 0 0;

        color: #6b7280;
    }


    .back-btn {

        display: inline-block;

        padding: 11px 18px;

        background: #6b7280;

        color: white;

        text-decoration: none;

        border-radius: 7px;

        font-weight: bold;
    }


    .back-btn:hover {

        background: #4b5563;
    }


    /* =========================================================
       MESSAGE
    ========================================================= */

    .message {

        max-width: 700px;

        padding: 15px 18px;

        border-radius: 8px;

        margin-bottom: 20px;

        font-weight: 600;
    }


    .message.error {

        background: #fee2e2;

        color: #991b1b;

        border: 1px solid #fecaca;
    }


    .message.success {

        background: #dcfce7;

        color: #166534;

        border: 1px solid #bbf7d0;
    }


    /* =========================================================
       FORM CARD
    ========================================================= */

    .card {

        background: white;

        max-width: 700px;

        padding: 30px;

        border-radius: 12px;

        box-shadow:
            0 4px 15px rgba(0,0,0,0.06);
    }


    .card h2 {

        margin-top: 0;

        margin-bottom: 8px;

        font-size: 22px;
    }


    .card-description {

        color: #6b7280;

        margin-bottom: 25px;
    }


    /* =========================================================
       FORM
    ========================================================= */

    .form-group {

        margin-bottom: 20px;
    }


    label {

        display: block;

        margin-bottom: 8px;

        font-weight: bold;

        color: #374151;
    }


    input {

        width: 100%;

        padding: 13px 14px;

        border: 1px solid #d1d5db;

        border-radius: 7px;

        font-size: 15px;

        background: white;
    }


    input:focus {

        outline: none;

        border-color: #2563eb;

        box-shadow:
            0 0 0 3px rgba(37,99,235,0.10);
    }


    .role-box {

        background: #eff6ff;

        border: 1px solid #bfdbfe;

        padding: 14px;

        border-radius: 7px;

        margin-bottom: 22px;

        color: #1e40af;
    }


    .role-box strong {

        display: block;

        margin-bottom: 4px;
    }


    .create-btn {

        width: 100%;

        padding: 14px;

        border: none;

        border-radius: 7px;

        background: #2563eb;

        color: white;

        font-size: 16px;

        font-weight: bold;

        cursor: pointer;
    }


    .create-btn:hover {

        background: #1d4ed8;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 768px) {

        .sidebar {

            width: 200px;
        }


        .main {

            margin-left: 200px;

            padding: 25px;
        }


        .page-header {

            flex-direction: column;

            align-items: flex-start;

            gap: 15px;
        }

    }

</style>


</head>

<body>

<!-- =============================================================
     SIDEBAR
============================================================= -->

<div class="sidebar">

<div class="logo">
       SIMPLE POS
</div>


<a href="dashboard.php">
    Dashboard
</a>


<a href="products.php">
    Products
</a>


<a href="pos.php">
    Point of Sale
</a>


<a href="sales.php">
    Sales History
</a>


<a href="register_cashier.php" class="active">
    Create Cashier
</a>


<a href="logout.php">
    Logout
</a>


</div>

<!-- =============================================================
     MAIN CONTENT
============================================================= -->

<div class="main">

<!-- PAGE HEADER -->

<div class="page-header">

    <div>

        <h1>
            Create Cashier Account
        </h1>

        <p>
            Create a new account for a POS cashier.
        </p>

    </div>


    <a
        href="dashboard.php"
        class="back-btn"
    >
        ← Back to Dashboard
    </a>

</div>


<!-- MESSAGE -->

<?php if ($message !== ""): ?>

    <div class="message <?php echo $message_type; ?>">

        <?php
        echo htmlspecialchars($message);
        ?>

    </div>

<?php endif; ?>


<!-- FORM CARD -->

<div class="card">

    <h2>
        Cashier Information
    </h2>


    <p class="card-description">
        Enter the details below to create a new cashier account.
    </p>


    <!-- ROLE INFORMATION -->

    <div class="role-box">

        <strong>
            Account Role
        </strong>

        This account will automatically be created with the
        <strong>cashier</strong> role.

    </div>


    <!-- FORM -->

    <form
        method="POST"
        action="register_cashier.php"
    >


        <!-- NAME -->

        <div class="form-group">

            <label for="name">
                Cashier Name
            </label>


            <input
                type="text"
                id="name"
                name="name"
                placeholder="Enter cashier's full name"
                required
                value="<?php
                    echo isset($_POST['name'])
                        ? htmlspecialchars($_POST['name'])
                        : '';
                ?>"
            >

        </div>


        <!-- EMAIL -->

        <div class="form-group">

            <label for="email">
                Email Address
            </label>


            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter cashier's email"
                required
                value="<?php
                    echo isset($_POST['email'])
                        ? htmlspecialchars($_POST['email'])
                        : '';
                ?>"
            >

        </div>


        <!-- PASSWORD -->

        <div class="form-group">

            <label for="password">
                Password
            </label>


            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter password"
                minlength="6"
                required
            >

        </div>


        <!-- CONFIRM PASSWORD -->

        <div class="form-group">

            <label for="confirm_password">
                Confirm Password
            </label>


            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm password"
                minlength="6"
                required
            >

        </div>


        <!-- SUBMIT -->

        <button
            type="submit"
            class="create-btn"
        >
            Create Cashier Account
        </button>


    </form>

</div>


</div>

</body>

</html>
