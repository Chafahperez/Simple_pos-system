<?php

session_start();

require_once "config.php";

/*
|--------------------------------------------------------------------------
| FETCH SALES
|--------------------------------------------------------------------------
| Join sales with users so we can display the cashier
| who performed each transaction.
*/

$sql = "
    SELECT
        s.id,
        s.user_id,
        s.total_amount,
        s.amount_paid,
        s.change_amount,
        s.created_at,
        u.name AS cashier_name,

        GROUP_CONCAT(
            CONCAT(p.name, ' (', si.quantity, ')')
            SEPARATOR ', '
        ) AS products_sold

    FROM sales s

    LEFT JOIN users u
        ON s.user_id = u.id

    LEFT JOIN sale_items si
        ON s.id = si.sale_id

    LEFT JOIN products p
        ON si.product_id = p.id

    GROUP BY
        s.id,
        s.user_id,
        s.total_amount,
        s.amount_paid,
        s.change_amount,
        s.created_at,
        u.name

    ORDER BY s.id DESC
";

$result = $conn->query($sql);


/*
|--------------------------------------------------------------------------
| SUMMARY STATISTICS
|--------------------------------------------------------------------------
*/

// Total number of sales
$total_sales = 0;

$count_sql = "
    SELECT COUNT(*) AS total_sales
    FROM sales
";

$count_result = $conn->query($count_sql);

if ($count_result) {

    $count_row = $count_result->fetch_assoc();

    $total_sales = $count_row['total_sales'];
}


// Total revenue
$total_revenue = 0;

$revenue_sql = "
    SELECT SUM(total_amount) AS total_revenue
    FROM sales
";

$revenue_result = $conn->query($revenue_sql);

if ($revenue_result) {

    $revenue_row = $revenue_result->fetch_assoc();

    $total_revenue = $revenue_row['total_revenue'] ?? 0;
}


// Today's revenue
$today_revenue = 0;

$today_sql = "
    SELECT SUM(total_amount) AS today_revenue
    FROM sales
    WHERE DATE(created_at) = CURDATE()
";

$today_result = $conn->query($today_sql);

if ($today_result) {

    $today_row = $today_result->fetch_assoc();

    $today_revenue = $today_row['today_revenue'] ?? 0;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Sales History - SimplePOS</title>

<style>

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        background: #f4f6f8;
        color: #1f2937;
    }


    /* SIDEBAR */

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


    /* MAIN */

    .main {
        margin-left: 240px;

        padding: 40px;
    }


    .page-header {
        margin-bottom: 30px;
    }

    .page-header h1 {
        margin: 0 0 8px;

        font-size: 30px;
    }

    .page-header p {
        margin: 0;

        color: #6b7280;
    }


    /* STATISTICS */

    .stats {
        display: grid;

        grid-template-columns:
            repeat(3, 1fr);

        gap: 20px;

        margin-bottom: 30px;
    }

    .stat-card {
        background: white;

        padding: 25px;

        border-radius: 12px;

        box-shadow:
            0 4px 15px rgba(0,0,0,0.06);
    }

    .stat-title {
        color: #6b7280;

        font-size: 14px;

        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 28px;

        font-weight: bold;

        color: #111827;
    }


    /* TABLE CARD */

    .table-card {
        background: white;

        border-radius: 12px;

        padding: 25px;

        box-shadow:
            0 4px 15px rgba(0,0,0,0.06);

        overflow-x: auto;
    }

    .table-card h2 {
        margin-top: 0;

        margin-bottom: 20px;

        font-size: 21px;
    }


    /* TABLE */

    table {
        width: 100%;

        border-collapse: collapse;

        min-width: 950px;
    }

    th {
        background: #f3f4f6;

        color: #374151;

        text-align: left;

        padding: 14px;

        font-size: 14px;

        border-bottom: 1px solid #e5e7eb;
    }

    td {
        padding: 15px 14px;

        border-bottom: 1px solid #e5e7eb;

        font-size: 14px;
    }

    tr:hover td {
        background: #f9fafb;
    }


    /* SALE ID */

    .sale-id {
        font-weight: bold;

        color: #2563eb;
    }


    /* CASHIER */

    .cashier {
        font-weight: 600;

        color: #374151;
    }

    .cashier-role {
        display: block;

        margin-top: 4px;

        font-size: 12px;

        color: #6b7280;
    }


    /* PRODUCTS */

    .products {
        max-width: 250px;

        line-height: 1.5;
    }


    /* MONEY */

    .money {
        font-weight: bold;

        white-space: nowrap;
    }


    /* VIEW BUTTON */

    .view-btn {
        display: inline-block;

        padding: 8px 13px;

        background: #2563eb;

        color: white;

        text-decoration: none;

        border-radius: 6px;

        font-size: 13px;

        font-weight: bold;
    }

    .view-btn:hover {
        background: #1d4ed8;
    }


    /* EMPTY */

    .empty {
        text-align: center;

        padding: 30px;

        color: #6b7280;
    }


    /* RESPONSIVE */

    @media (max-width: 1000px) {

        .stats {
            grid-template-columns: 1fr;
        }

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

<!-- MAIN CONTENT -->

<div class="main">

<!-- PAGE HEADER -->

<div class="page-header">

    <h1>
        Sales History
    </h1>

    <p>
        View and manage all completed sales transactions.
    </p>

</div>


<!-- STATISTICS -->

<div class="stats">


    <div class="stat-card">

        <div class="stat-title">
            Total Sales
        </div>

        <div class="stat-value">

            <?php
            echo number_format($total_sales);
            ?>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-title">
            Total Revenue
        </div>

        <div class="stat-value">

            <?php
            echo number_format($total_revenue);
            ?>
            XAF

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-title">
            Today's Revenue
        </div>

        <div class="stat-value">

            <?php
            echo number_format($today_revenue);
            ?>
            XAF

        </div>

    </div>


</div>


<!-- SALES TABLE -->

<div class="table-card">

    <h2>
        All Transactions
    </h2>


    <table>

        <thead>

            <tr>

                <th>
                    Sale ID
                </th>

                <th>
                    Date & Time
                </th>

                <th>
                    Cashier
                </th>

                <th>
                    Products Sold
                </th>

                <th>
                    Total
                </th>

                <th>
                    Amount Paid
                </th>

                <th>
                    Change
                </th>

                <th>
                    Action
                </th>

            </tr>

        </thead>


        <tbody>

            <?php if ($result && $result->num_rows > 0): ?>

                <?php while ($sale = $result->fetch_assoc()): ?>

                    <tr>


                        <!-- SALE ID -->

                        <td>

                            <span class="sale-id">

                                #<?php
                                echo $sale['id'];
                                ?>

                            </span>

                        </td>


                        <!-- DATE -->

                        <td>

                            <?php

                            echo date(
                                "d M Y, h:i A",
                                strtotime(
                                    $sale['created_at']
                                )
                            );

                            ?>

                        </td>


                        <!-- CASHIER -->

                        <td>

                            <span class="cashier">

                                <?php

                                echo htmlspecialchars(
                                    $sale['cashier_name']
                                    ?? "Unknown"
                                );

                                ?>

                            </span>

                        </td>


                        <!-- PRODUCTS -->

                        <td>

                            <div class="products">

                                <?php

                                echo htmlspecialchars(
                                    $sale['products_sold']
                                    ?? "No products"
                                );

                                ?>

                            </div>

                        </td>


                        <!-- TOTAL -->

                        <td>

                            <span class="money">

                                <?php

                                echo number_format(
                                    $sale['total_amount']
                                );

                                ?>
                                XAF

                            </span>

                        </td>


                        <!-- AMOUNT PAID -->

                        <td>

                            <?php

                            echo number_format(
                                $sale['amount_paid']
                            );

                            ?>
                            XAF

                        </td>


                        <!-- CHANGE -->

                        <td>

                            <?php

                            echo number_format(
                                $sale['change_amount']
                            );

                            ?>
                            XAF

                        </td>


                        <!-- ACTION -->

                        <td>

                            <a
                                href="sale_details.php?id=<?php echo $sale['id']; ?>"
                                class="view-btn"
                            >
                                View Details
                            </a>

                        </td>


                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="8"
                        class="empty"
                    >

                        No sales transactions found.

                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

</div>

</body>

</html>
