<?php
session_start();
require_once "config.php";

/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    // Check if this product has already been used in a sale
    $check = $conn->prepare("SELECT COUNT(*) AS total FROM sale_items WHERE product_id = ?");
    $check->bind_param("i", $id);
    $check->execute();

    $result = $check->get_result();
    $row = $result->fetch_assoc();

    if ($row['total'] > 0) {
        $_SESSION['error'] = "This product cannot be deleted because it has already been used in a sale.";
    } else {

        $delete = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete->bind_param("i", $id);

        if ($delete->execute()) {
            $_SESSION['success'] = "Product deleted successfully.";
        } else {
            $_SESSION['error'] = "Unable to delete product.";
        }
    }

    header("Location: products.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCTS
|--------------------------------------------------------------------------
*/
$sql = "SELECT * FROM products ORDER BY id ASC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Products - Simple POS</title>

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

        /* SIDEBAR */

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
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: #374151;
            color: white;
        }

        .sidebar a.active {
            background: #2563eb;
            color: white;
        }

        /* MAIN CONTENT */

        .main {
            margin-left: 230px;
            padding: 35px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .top-bar h1 {
            font-size: 30px;
            color: #111827;
        }

        .add-button {
            background: #2563eb;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        .add-button:hover {
            background: #1d4ed8;
        }

        /* ALERTS */

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        /* TABLE CARD */

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin-bottom: 20px;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            padding: 14px;
            color: #374151;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb;
        }

        /* STATUS */

        .stock-good {
            color: #166534;
            font-weight: bold;
        }

        .stock-low {
            color: #ca8a04;
            font-weight: bold;
        }

        .stock-out {
            color: #dc2626;
            font-weight: bold;
        }

        /* ACTION BUTTONS */

        .edit-btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 6px;
            margin-right: 5px;
            font-size: 14px;
        }

        .edit-btn:hover {
            background: #1d4ed8;
        }

        .delete-btn {
            display: inline-block;
            background: #dc2626;
            color: white;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 14px;
        }

        .delete-btn:hover {
            background: #b91c1c;
        }

        /* MOBILE */

        @media (max-width: 800px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            table {
                min-width: 700px;
            }

            .card {
                overflow-x: auto;
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

    <div class="top-bar">

        <h1><u>Product Management</u></h1>

        <a href="add_product.php" class="add-button">
            + Add Product
        </a>

    </div>


    <!-- SUCCESS MESSAGE -->

    <?php if (isset($_SESSION['success'])): ?>

        <div class="success">
            <?php

            echo htmlspecialchars($_SESSION['success']);

            unset($_SESSION['success']);

            ?>
        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if (isset($_SESSION['error'])): ?>

        <div class="error">
            <?php

            echo htmlspecialchars($_SESSION['error']);

            unset($_SESSION['error']);

            ?>
        </div>

    <?php endif; ?>


    <!-- PRODUCTS -->

    <div class="card">

        <h2>All Products</h2>

        <table>

            <thead>

                <tr>

                    <th>No.</th>

                    <th>Product ID</th>

                    <th>Product Name</th>

                    <th>Price</th>

                    <th>Quantity</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

                <?php

                $number = 1;

                if ($result->num_rows > 0):

                    while ($product = $result->fetch_assoc()):

                        /*
                        |--------------------------------------------------------------------------
                        | LOW STOCK LEVEL
                        |--------------------------------------------------------------------------
                        | Each product now has its own low stock level.
                        | The value comes from the products.low_stock_level column.
                        */

                        $low_stock_level = (int) $product['low_stock_level'];


                        /*
                        |--------------------------------------------------------------------------
                        | STOCK STATUS
                        |--------------------------------------------------------------------------
                        */

                        if ($product['quantity'] == 0) {

                            $status = "Out of Stock";
                            $status_class = "stock-out";

                        } elseif ($product['quantity'] <= $low_stock_level) {

                            $status = "Low Stock";
                            $status_class = "stock-low";

                        } else {

                            $status = "In Stock";
                            $status_class = "stock-good";

                        }

                ?>

                    <tr>

                        <!-- Continuous display number -->

                        <td>
                            <?php echo $number++; ?>
                        </td>


                        <!-- Real database ID -->

                        <td>
                            #<?php echo $product['id']; ?>
                        </td>


                        <!-- Product name -->

                        <td>
                            <?php echo htmlspecialchars($product['name']); ?>
                        </td>


                        <!-- Price -->

                        <td>
                            <?php echo number_format($product['price']); ?> XAF
                        </td>


                        <!-- Quantity -->

                        <td>
                            <?php echo $product['quantity']; ?>
                        </td>


                        <!-- Stock status -->

                        <td class="<?php echo $status_class; ?>">
                            <?php echo $status; ?>
                        </td>


                        <!-- ACTIONS -->

                        <td>

                            <!-- EDIT -->

                            <a
                                href="edit_product.php?id=<?php echo $product['id']; ?>"
                                class="edit-btn"
                            >
                                Edit
                            </a>


                            <!-- DELETE -->

                            <a
                                href="products.php?delete=<?php echo $product['id']; ?>"
                                class="delete-btn"
                                onclick="return confirm('Are you sure you want to delete this product?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php

                    endwhile;

                else:

                ?>

                    <tr>

                        <td colspan="7" style="text-align:center;">
                            No products found.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>