<?php
session_start();
require_once "config.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/

$product_id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;


if ($product_id <= 0) {

    $_SESSION['error'] = "Invalid product.";

    header("Location: products.php");
    exit;
}


$stmt = $conn->prepare("
    SELECT *
    FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

$product = $result->fetch_assoc();


if (!$product) {

    $_SESSION['error'] = "Product not found.";

    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| PROCESS STOCK ADJUSTMENT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $movement_type = $_POST['movement_type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($quantity <= 0) {

        $error = "Quantity must be greater than zero.";

    } elseif ($reason === '') {

        $error = "Please provide a reason.";

    } elseif (
        !in_array(
            $movement_type,
            ['replenishment', 'increase', 'decrease', 'correction']
        )
    ) {

        $error = "Invalid stock movement type.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | START DATABASE TRANSACTION
        |--------------------------------------------------------------------------
        */

        $conn->begin_transaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | GET CURRENT STOCK
            |--------------------------------------------------------------------------
            */

            $check = $conn->prepare("
                SELECT quantity
                FROM products
                WHERE id = ?
                FOR UPDATE
            ");

            $check->bind_param("i", $product_id);
            $check->execute();

            $stock_result = $check->get_result();
            $stock_data = $stock_result->fetch_assoc();

            $previous_quantity = intval(
                $stock_data['quantity']
            );


            /*
            |--------------------------------------------------------------------------
            | CALCULATE NEW STOCK
            |--------------------------------------------------------------------------
            */

            if (
                $movement_type === 'replenishment'
                ||
                $movement_type === 'increase'
            ) {

                $new_quantity =
                    $previous_quantity + $quantity;

            } else {

                $new_quantity =
                    $previous_quantity - $quantity;
            }


            /*
            |--------------------------------------------------------------------------
            | PREVENT NEGATIVE STOCK
            |--------------------------------------------------------------------------
            */

            if ($new_quantity < 0) {

                throw new Exception(
                    "Stock cannot become negative. Current stock is "
                    . $previous_quantity
                    . "."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE PRODUCT STOCK
            |--------------------------------------------------------------------------
            */

            $update = $conn->prepare("
                UPDATE products
                SET quantity = ?
                WHERE id = ?
            ");

            $update->bind_param(
                "ii",
                $new_quantity,
                $product_id
            );

            if (!$update->execute()) {

                throw new Exception(
                    "Unable to update product stock."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | RECORD STOCK MOVEMENT
            |--------------------------------------------------------------------------
            */

            $movement = $conn->prepare("
                INSERT INTO stock_movements
                (
                    product_id,
                    user_id,
                    movement_type,
                    quantity_changed,
                    previous_quantity,
                    new_quantity,
                    reason
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $movement->bind_param(
                "iisiiis",
                $product_id,
                $user_id,
                $movement_type,
                $quantity,
                $previous_quantity,
                $new_quantity,
                $reason
            );


            if (!$movement->execute()) {

                throw new Exception(
                    "Unable to record stock movement."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | COMPLETE TRANSACTION
            |--------------------------------------------------------------------------
            */

            $conn->commit();


            $_SESSION['success'] =
                "Stock updated successfully. "
                . "New quantity: "
                . $new_quantity;


            header("Location: products.php");
            exit;


        } catch (Exception $e) {

            $conn->rollback();

            $error = $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Stock - Simple POS</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
        }


        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 230px;
            height: 100vh;
            background: #111827;
            padding: 25px 15px;
        }


        .logo {
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 35px;
        }


        .sidebar a {
            display: block;
            text-decoration: none;
            color: #d1d5db;
            padding: 13px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
        }


        .sidebar a:hover {
            background: #374151;
            color: white;
        }


        .sidebar a.active {
            background: #2563eb;
            color: white;
        }


        .main {
            margin-left: 230px;
            padding: 35px;
        }


        .card {
            background: white;
            max-width: 700px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }


        h1 {
            margin-bottom: 25px;
            color: #111827;
        }


        .product-info {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }


        .product-info p {
            margin-bottom: 8px;
        }


        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 7px;
            font-weight: bold;
        }


        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 15px;
        }


        textarea {
            min-height: 100px;
            resize: vertical;
        }


        button {
            margin-top: 25px;
            background: #16a34a;
            color: white;
            border: none;
            padding: 13px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
        }


        button:hover {
            background: #15803d;
        }


        .back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #2563eb;
            font-weight: bold;
        }


        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

    </style>

</head>


<body>


<div class="sidebar">

    <div class="logo">
        SIMPLE POS
    </div>


    <a href="dashboard.php">
        Dashboard
    </a>


    <a href="products.php" class="active">
        Products
    </a>


    <a href="pos.php">
        Point of Sale
    </a>


    <a href="sales.php">
        Sales History
    </a>


    <a href="stock_history.php">
        Stock History
    </a>


    <a href="logout.php">
        Logout
    </a>

</div>


<div class="main">


    <a href="products.php" class="back">
        ← Back to Products
    </a>


    <div class="card">

        <h1>
            Manage Stock
        </h1>


        <div class="product-info">

            <p>
                <strong>Product:</strong>
                <?php echo htmlspecialchars($product['name']); ?>
            </p>

            <p>
                <strong>Current Stock:</strong>
                <?php echo $product['quantity']; ?>
            </p>

            <p>
                <strong>Minimum Stock:</strong>
                <?php echo $product['low_stock_level']; ?>
            </p>

        </div>


        <?php if (isset($error)): ?>

            <div class="error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <label>
                Stock Operation
            </label>


            <select name="movement_type" required>

                <option value="">
                    -- Select Operation --
                </option>

                <option value="replenishment">
                    Replenishment - Add New Stock
                </option>

                <option value="increase">
                    Increase - Add Stock
                </option>

                <option value="decrease">
                    Decrease - Remove Stock
                </option>

                <option value="correction">
                    Correction - Remove Incorrect Stock
                </option>

            </select>


            <label>
                Quantity
            </label>


            <input
                type="number"
                name="quantity"
                min="1"
                required
            >


            <label>
                Reason
            </label>


            <textarea
                name="reason"
                placeholder="Example: Received 20 new bottles from supplier"
                required
            ></textarea>


            <button type="submit">
                Update Stock
            </button>


        </form>

    </div>

</div>


</body>

</html>