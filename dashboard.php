<?php

session_start();

require_once "config.php";


// =========================================================
// DASHBOARD STATISTICS
// =========================================================

// Today's sales
$today_sales = 0;

$sql = "
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM sales
    WHERE DATE(created_at) = CURDATE()
";

$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $today_sales = $row['total'];
}


// Number of sales today
$sales_today = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM sales
    WHERE DATE(created_at) = CURDATE()
";

$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $sales_today = $row['total'];
}


// Total products
$total_products = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM products
";

$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $total_products = $row['total'];
}


// Total stock
$total_stock = 0;

$sql = "
    SELECT COALESCE(SUM(quantity), 0) AS total
    FROM products
";

$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $total_stock = $row['total'];
}


// Low stock products
$low_stock = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM products
    WHERE quantity <= 5
";

$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $low_stock = $row['total'];
}


// =========================================================
// RECENT SALES
// =========================================================

$recent_sales = $conn->query("
    SELECT
        id,
        total_amount,
        amount_paid,
        change_amount,
        created_at
    FROM sales
    ORDER BY id DESC
    LIMIT 5
");

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Dashboard - Simple POS</title>


<style>

/* ========================================================
   GENERAL
======================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f5f7fb;

    color: #1f2937;

}


/* ========================================================
   SIDEBAR
======================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 240px;

    height: 100vh;

    background: #111827;

    color: white;

    padding: 25px 15px;

}


.logo {

    text-align: center;

    font-size: 24px;

    font-weight: bold;

    margin-bottom: 35px;

}


.logo span {

    color: #22c55e;

}


.menu {

    list-style: none;

}


.menu li {

    margin-bottom: 8px;

}


.menu a {

    display: block;

    padding: 14px 16px;

    color: #d1d5db;

    text-decoration: none;

    border-radius: 8px;

    transition: 0.3s;

}


.menu a:hover {

    background: #1f2937;

    color: white;

}


.menu .active a {

    background: #22c55e;

    color: white;

}


.logout {

    margin-top: 30px;

}


.logout a {

    color: #f87171;

}


/* ========================================================
   MAIN CONTENT
======================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


/* ========================================================
   TOP BAR
======================================================== */

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 30px;

}


.topbar h1 {

    font-size: 28px;

}


.welcome {

    color: #6b7280;

    margin-top: 6px;

}


.admin {

    background: white;

    padding: 12px 18px;

    border-radius: 10px;

    box-shadow:
        0 2px 10px rgba(0,0,0,0.06);

    font-weight: bold;

}


/* ========================================================
   STATISTICS
======================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;

    margin-bottom: 30px;

}


.stat-card {

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 2px 12px rgba(0,0,0,0.06);

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.stat-info p {

    color: #6b7280;

    font-size: 14px;

    margin-bottom: 8px;

}


.stat-info h2 {

    font-size: 27px;

}


.icon {

    width: 52px;

    height: 52px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 24px;

}


.green {

    background: #dcfce7;

    color: #16a34a;

}


.blue {

    background: #dbeafe;

    color: #2563eb;

}


.orange {

    background: #ffedd5;

    color: #ea580c;

}


.red {

    background: #fee2e2;

    color: #dc2626;

}


/* ========================================================
   CONTENT GRID
======================================================== */

.content-grid {

    display: grid;

    grid-template-columns: 2fr 1fr;

    gap: 25px;

}


/* ========================================================
   CARD
======================================================== */

.card {

    background: white;

    border-radius: 12px;

    padding: 25px;

    box-shadow:
        0 2px 12px rgba(0,0,0,0.06);

}


.card-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;

}


.card-header h2 {

    font-size: 20px;

}


.card-header a {

    color: #16a34a;

    text-decoration: none;

    font-size: 14px;

    font-weight: bold;

}


/* ========================================================
   TABLE
======================================================== */

table {

    width: 100%;

    border-collapse: collapse;

}


th {

    background: #f9fafb;

    color: #6b7280;

    text-align: left;

    padding: 13px;

    font-size: 13px;

}


td {

    padding: 15px 13px;

    border-bottom: 1px solid #eee;

    font-size: 14px;

}


.amount {

    font-weight: bold;

    color: #16a34a;

}


/* ========================================================
   QUICK ACTIONS
======================================================== */

.quick-actions {

    display: grid;

    gap: 12px;

}


.action {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 16px;

    border: 1px solid #eee;

    border-radius: 9px;

    text-decoration: none;

    color: #1f2937;

    transition: 0.3s;

}


.action:hover {

    border-color: #22c55e;

    background: #f0fdf4;

}


.action-icon {

    width: 42px;

    height: 42px;

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #dcfce7;

    color: #16a34a;

    font-size: 20px;

}


.action strong {

    display: block;

    margin-bottom: 4px;

}


.action small {

    color: #6b7280;

}


/* ========================================================
   STOCK ALERT
======================================================== */

.stock-alert {

    margin-top: 25px;

    padding: 18px;

    background: #fff7ed;

    border: 1px solid #fed7aa;

    border-radius: 10px;

}


.stock-alert strong {

    color: #c2410c;

}


.stock-alert p {

    color: #9a3412;

    margin-top: 5px;

    font-size: 14px;

}


/* ========================================================
   EMPTY SALES
======================================================== */

.empty {

    text-align: center;

    padding: 30px;

    color: #9ca3af;

}


/* ========================================================
   RESPONSIVE
======================================================== */

@media (max-width: 1000px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }

    .content-grid {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 700px) {

    .sidebar {

        width: 200px;

    }

    .main {

        margin-left: 200px;

        padding: 20px;

    }

    .stats {

        grid-template-columns: 1fr;

    }

}


</style>

</head>


<body>


<!-- ======================================================
     SIDEBAR
====================================================== -->

<aside class="sidebar">


<div class="logo">

    SIMPLE <span> POS</span>

</div>


<ul class="menu">


<li class="active">

<a href="dashboard.php">

    📊 Dashboard

</a>

</li>


<li>

<a href="products.php">

    📦 Products

</a>

</li>


<li>

<a href="pos.php">

    🛒 Point of Sale

</a>

</li>


<li>

<a href="sales.php">

    🧾 Sales History

</a>

</li>


<li class="logout">

<a href="logout.php">

    🚪 Logout

</a>

</li>


</ul>


</aside>



<!-- ======================================================
     MAIN
====================================================== -->

<main class="main">


<!-- TOP BAR -->

<div class="topbar">


<div>

<h1>Dashboard</h1>

<p class="welcome">
    Welcome back, POS Administrator
</p>

</div>


<div class="admin">

    👤 Administrator

</div>


</div>



<!-- ======================================================
     STATISTICS
====================================================== -->

<div class="stats">


<!-- TODAY SALES -->

<div class="stat-card">

<div class="stat-info">

<p>Today's Sales</p>

<h2>
    <?php echo number_format($today_sales); ?>
    XAF
</h2>

</div>


<div class="icon green">

💰

</div>

</div>



<!-- SALES TODAY -->

<div class="stat-card">

<div class="stat-info">

<p>Sales Today</p>

<h2>
    <?php echo $sales_today; ?>
</h2>

</div>


<div class="icon blue">

🧾

</div>

</div>



<!-- TOTAL PRODUCTS -->

<div class="stat-card">

<div class="stat-info">

<p>Total Products</p>

<h2>
    <?php echo $total_products; ?>
</h2>

</div>


<div class="icon orange">

📦

</div>

</div>



<!-- TOTAL STOCK -->

<div class="stat-card">

<div class="stat-info">

<p>Total Stock</p>

<h2>
    <?php echo $total_stock; ?>
</h2>

</div>


<div class="icon red">

📊

</div>

</div>


</div>



<!-- ======================================================
     CONTENT
====================================================== -->

<div class="content-grid">


<!-- =====================================================
     RECENT SALES
===================================================== -->

<div class="card">


<div class="card-header">

<h2>Recent Sales</h2>

<a href="sales.php">
    View All →
</a>

</div>


<?php if ($recent_sales && $recent_sales->num_rows > 0): ?>


<table>

<thead>

<tr>

<th>Sale ID</th>

<th>Total</th>

<th>Paid</th>

<th>Change</th>

<th>Date</th>

</tr>

</thead>


<tbody>


<?php while ($sale = $recent_sales->fetch_assoc()): ?>


<tr>

<td>

#<?php echo $sale['id']; ?>

</td>


<td class="amount">

<?php echo number_format(
    $sale['total_amount']
); ?>

XAF

</td>


<td>

<?php echo number_format(
    $sale['amount_paid']
); ?>

XAF

</td>


<td>

<?php echo number_format(
    $sale['change_amount']
); ?>

XAF

</td>


<td>

<?php echo date(
    'd M Y, H:i',
    strtotime($sale['created_at'])
); ?>

</td>

</tr>


<?php endwhile; ?>


</tbody>

</table>


<?php else: ?>


<div class="empty">

    <p>No sales have been recorded yet.</p>

</div>


<?php endif; ?>


</div>



<!-- =====================================================
     QUICK ACTIONS
===================================================== -->

<div class="card">


<div class="card-header">

<h2>Quick Actions</h2>

</div>


<div class="quick-actions">


<a href="pos.php"
   class="action">


<div class="action-icon">

🛒

</div>


<div>

<strong>New Sale</strong>

<small>
    Start a new customer transaction
</small>

</div>


</a>



<a href="products.php"
   class="action">


<div class="action-icon">

📦

</div>


<div>

<strong>Manage Products</strong>

<small>
    Add or manage your products
</small>

</div>


</a>



<a href="sales.php"
   class="action">


<div class="action-icon">

🧾

</div>


<div>

<strong>Sales History</strong>

<small>
    View previous transactions
</small>

</div>


</a>


</div>


<!-- LOW STOCK -->

<?php if ($low_stock > 0): ?>


<div class="stock-alert">

<strong>
    ⚠ Low Stock Alert
</strong>

<p>

<?php echo $low_stock; ?>

product(s) have 5 or fewer items remaining.

</p>

</div>


<?php endif; ?>


</div>


</div>


</main>


</body>

</html>