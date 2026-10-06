<?php
require_once "config.php";

$email = "admin@pos.com";
$new_password = "Admin123!";

$hashed_password = password_hash(
    $new_password,
    PASSWORD_DEFAULT
);

$stmt = $conn->prepare(
    "UPDATE users SET password = ? WHERE email = ?"
);

$stmt->bind_param("ss", $hashed_password, $email);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Administrator password reset successfully.";
    echo "<br>Email: admin@pos.com";
    echo "<br>New password: Admin123!";
} else {
    echo "No password change was made. Check whether the account exists.";
}

$stmt->close();
$conn->close();
?>