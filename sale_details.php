<?php
session_start();
require_once "config.php";


/*
|--------------------------------------------------------------------------
| CHECK SALE ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    die("Invalid sale ID.");

}

$sale_id = intval($_GET['id']);


/*
|--------------------------------------------------------------------------
| GET SALE INFORMATION
|--------------------------------------------------------------------------
*/

$sale_sql = "
    SELECT
        s.id,
        s.total_amount,
        s.amount_paid,
        s.change_amount,
        s.created_at

    FROM sales s

    WHERE s.id = ?
";


$stmt = $conn->prepare($sale_sql);

$stmt->bind_param("i", $sale_id);

$stmt->execute();

$sale_result = $stmt->get_result();


if ($sale_result->num_rows === 0) {

    die("Sale not found.");

}


$sale = $sale_result->fetch_assoc();


/*
|--------------------------------------------------------------------------
| GET PRODUCTS IN THIS SALE
|--------------------------------------------------------------------------
*/

$items_sql = "
    SELECT
        si.id,
        si.quantity,
        si.price,
        si.subtotal,
        p.name

    FROM sale_items si

    INNER JOIN products p
        ON si.product_id = p.id

    WHERE si.sale_id = ?

    ORDER BY si.id ASC
";


$item_stmt = $conn->prepare($items_sql);

$item_stmt->bind_param("i", $sale_id);

$item_stmt->execute();

$items_result = $item_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sale Details - Simple POS</title>


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
        }


        .sidebar a:hover {

            background: #374151;

            color: white;
        }


        .sidebar a.active {

            background: #2563eb;

            color: white;
        }


        /* MAIN */

        .main {

            margin-left: 230px;

            padding: 35px;
        }


        .back {

            display: inline-block;

            text-decoration: none;

            color: #2563eb;

            font-weight: bold;

            margin-bottom: 20px;
        }


        h1 {

            color: #111827;

            margin-bottom: 25px;
        }


        /* SALE INFORMATION */

        .sale-info {

            background: white;

            padding: 25px;

            border-radius: 12px;

            box-shadow: 0 4px 15px rgba(0,0,0,0.08);

            margin-bottom: 25px;
        }


        .info-grid {

            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 20px;
        }


        .info-box {

            background: #f9fafb;

            padding: 15px;

            border-radius: 8px;
        }


        .info-box span {

            display: block;

            color: #6b7280;

            font-size: 13px;

            margin-bottom: 6px;
        }


        .info-box strong {

            font-size: 18px;

            color: #111827;
        }


        /* TABLE */

        .card {

            background: white;

            padding: 25px;

            border-radius: 12px;

            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }


        table {

            width: 100%;

            border-collapse: collapse;
        }


        th {

            background: #f3f4f6;

            text-align: left;

            padding: 14px;
        }


        td {

            padding: 14px;

            border-bottom: 1px solid #e5e7eb;
        }


        .total-row {

            font-weight: bold;

            font-size: 17px;
        }


        .total-row td {

            border-top: 2px solid #111827;
        }


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


            .info-grid {

                grid-template-columns: 1fr;
            }


            .card {

                overflow-x: auto;
            }


            table {

                min-width: 600px;
            }

        }

    </style>

</head>


<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <div class="logo">
        SIMPLEPOS
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


    <a href="sales.php" class="active">
        Sales History
    </a>


    <a href="logout.php">
        Logout
    </a>

</div>


<!-- MAIN -->

<div class="main">


    <a href="sales.php" class="back">
        ← Back to Sales History
    </a>


    <h1>
        Sale #<?php echo $sale['id']; ?> Details
    </h1>


    <!-- SALE INFORMATION -->

    <div class="sale-info">

        <div class="info-grid">


            <div class="info-box">

                <span>
                    Sale Date
                </span>

                <strong>

                    <?php

                    echo date(
                        "d M Y, h:i A",
                        strtotime($sale['created_at'])
                    );

                    ?>

                </strong>

            </div>


            <div class="info-box">

                <span>
                    Amount Paid
                </span>

                <strong>

                    <?php

                    echo number_format(
                        $sale['amount_paid']
                    );

                    ?>

                    XAF

                </strong>

            </div>


            <div class="info-box">

                <span>
                    Change
                </span>

                <strong>

                    <?php

                    echo number_format(
                        $sale['change_amount']
                    );

                    ?>

                    XAF

                </strong>

            </div>


        </div>

    </div>


    <!-- PRODUCTS -->

    <div class="card">

        <h2 style="margin-bottom:20px;">
            Products Sold
        </h2>


        <table>


            <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Product
                    </th>

                    <th>
                        Quantity
                    </th>

                    <th>
                        Unit Price
                    </th>

                    <th>
                        Subtotal
                    </th>

                </tr>

            </thead>


            <tbody>


                <?php

                $number = 1;

                while ($item = $items_result->fetch_assoc()):

                ?>


                    <tr>

                        <td>
                            <?php echo $number++; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </td>

                        <td>
                            <?php echo $item['quantity']; ?>
                        </td>

                        <td>

                            <?php

                            echo number_format(
                                $item['price']
                            );

                            ?>

                            XAF

                        </td>

                        <td>

                            <?php

                            echo number_format(
                                $item['subtotal']
                            );

                            ?>

                            XAF

                        </td>

                    </tr>


                <?php endwhile; ?>


                <!-- TOTAL -->

                <tr class="total-row">

                    <td colspan="4" style="text-align:right;">
                        TOTAL:
                    </td>

                    <td>

                        <?php

                        echo number_format(
                            $sale['total_amount']
                        );

                        ?>

                        XAF

                    </td>

                </tr>


            </tbody>

        </table>

    </div>


</div>


</body>

</html>