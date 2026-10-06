<?php

require_once "config.php";


/*
|--------------------------------------------------------------------------
| ADMIN PASSWORD
|--------------------------------------------------------------------------
*/

$admin_password = password_hash(
    "Admin123!",
    PASSWORD_DEFAULT
);


/*
|--------------------------------------------------------------------------
| CASHIER PASSWORD
|--------------------------------------------------------------------------
*/

$cashier_password = password_hash(
    "Cashier123!",
    PASSWORD_DEFAULT
);


/*
|--------------------------------------------------------------------------
| INSERT ADMIN
|--------------------------------------------------------------------------
*/

$admin_sql = "
    INSERT INTO users
    (name, email, password, role)
    VALUES (?, ?, ?, ?)
";

$stmt = $conn->prepare($admin_sql);

$admin_name = "POS Administrator";
$admin_email = "admin@pos.com";
$admin_role = "admin";

$stmt->bind_param(
    "ssss",
    $admin_name,
    $admin_email,
    $admin_password,
    $admin_role
);

$stmt->execute();

$stmt->close();


/*
|--------------------------------------------------------------------------
| INSERT CASHIER
|--------------------------------------------------------------------------
*/

$cashier_sql = "
    INSERT INTO users
    (name, email, password, role)
    VALUES (?, ?, ?, ?)
";

$stmt = $conn->prepare($cashier_sql);

$cashier_name = "POS Cashier";
$cashier_email = "cashier@pos.com";
$cashier_role = "cashier";

$stmt->bind_param(
    "ssss",
    $cashier_name,
    $cashier_email,
    $cashier_password,
    $cashier_role
);

$stmt->execute();

$stmt->close();


echo "<h2>Users created successfully!</h2>";

echo "<p><strong>Admin</strong></p>";
echo "Email: admin@pos.com<br>";
echo "Password: Admin123!<br>";

echo "<br>";

echo "<p><strong>Cashier</strong></p>";
echo "Email: cashier@pos.com<br>";
echo "Password: Cashier123!<br>";

?>