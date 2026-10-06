<?php

require_once "config.php";

$message = "";
$message_type = "";

/*
|--------------------------------------------------------------------------
| ADD PRODUCT
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $quantity = trim($_POST["quantity"]);
    $low_stock_level = trim($_POST["low_stock_level"]);

    // Validate product name
    if ($name === "") {

        $message = "Please enter the product name.";
        $message_type = "error";

    } elseif (!is_numeric($price) || $price <= 0) {

        $message = "Please enter a valid price.";
        $message_type = "error";

    } elseif (!is_numeric($quantity) || $quantity < 0) {

        $message = "Please enter a valid quantity.";
        $message_type = "error";

    } elseif (!is_numeric($low_stock_level) || $low_stock_level < 0) {

        $message = "Please enter a valid low stock level.";
        $message_type = "error";

    } else {

        $price = floatval($price);
        $quantity = intval($quantity);
        $low_stock_level = intval($low_stock_level);

        /*
        |--------------------------------------------------------------------------
        | INSERT PRODUCT
        |--------------------------------------------------------------------------
        */
        $sql = "
            INSERT INTO products
            (name, price, quantity, low_stock_level)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "sdii",
                $name,
                $price,
                $quantity,
                $low_stock_level
            );

            if ($stmt->execute()) {

                // Redirect back to products page
                header("Location: products.php?success=Product added successfully");
                exit;

            } else {

                $message = "Failed to add product: " . $stmt->error;
                $message_type = "error";
            }

            $stmt->close();

        } else {

            $message = "Database error: " . $conn->error;
            $message_type = "error";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Product - Simple POS</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #222;
        }

        .sidebar {
            width: 240px;
            height: 100vh;
            background: #111827;
            position: fixed;
            left: 0;
            top: 0;
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
            margin-left: 240px;
            padding: 40px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .top h1 {
            margin: 0;
            font-size: 30px;
        }

        .back-btn {
            text-decoration: none;
            background: #6b7280;
            color: white;
            padding: 11px 18px;
            border-radius: 7px;
        }

        .card {
            background: white;
            max-width: 650px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 16px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .add-btn {
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

        .add-btn:hover {
            background: #1d4ed8;
        }

        .message {
            max-width: 650px;
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 7px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {

            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                padding: 25px;
            }
        }

    </style>

</head>

<body>

    <!-- SIDEBAR -->

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

        <a href="logout.php">
            Logout
        </a>

    </div>


    <!-- MAIN CONTENT -->

    <div class="main">

        <div class="top">

            <h1>
                Add Product
            </h1>

            <a href="products.php" class="back-btn">
                ← Back to Products
            </a>

        </div>


        <?php if ($message !== ""): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <div class="card">

            <form method="POST" action="add_product.php">

                <div class="form-group">

                    <label for="name">
                        Product Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter product name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="price">
                        Price (XAF)
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        placeholder="Enter price"
                        min="1"
                        step="0.01"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="quantity">
                        Quantity
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        placeholder="Enter quantity"
                        min="0"
                        required
                    >

                </div>


                <!-- LOW STOCK LEVEL -->

                <div class="form-group">

                    <label for="low_stock_level">
                        Low Stock Level
                    </label>

                    <input
                        type="number"
                        id="low_stock_level"
                        name="low_stock_level"
                        placeholder="Enter minimum stock level"
                        min="0"
                        value="5"
                        required
                    >

                </div>


                <button type="submit" class="add-btn">
                    Add Product
                </button>

            </form>

        </div>

    </div>

</body>

</html>