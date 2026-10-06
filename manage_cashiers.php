
<?php

session_start();

require_once "config.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit("Access denied. Only administrators can manage cashiers.");
}


/*
|--------------------------------------------------------------------------
| FETCH CASHIERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id, name, email, created_at
    FROM users
    WHERE role = 'cashier'
    ORDER BY id DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to retrieve cashiers: " . $conn->error);
}

$total_cashiers = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Cashiers - SimplePOS</title>

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

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100vh;
            padding: 25px 15px;
            background: #111827;
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
            padding: 14px 18px;
            margin-bottom: 8px;
            border-radius: 8px;
            color: #d1d5db;
            text-decoration: none;
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
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

        .add-btn {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 7px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            font-weight: bold;
            white-space: nowrap;
        }

        .add-btn:hover {
            background: #1d4ed8;
        }

        .summary-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .summary-title {
            color: #6b7280;
            margin-bottom: 10px;
        }

        .summary-number {
            font-size: 30px;
            font-weight: bold;
        }

        .table-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            overflow-x: auto;
        }

        .table-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        th {
            padding: 14px;
            text-align: left;
            background: #f3f4f6;
            color: #374151;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 15px 14px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: #dcfce7;
            color: #166534;
            font-size: 12px;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                padding: 20px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">

        <div class="logo">SIMPLEPOS</div>

        <a href="dashboard.php">Dashboard</a>

        <a href="products.php">Products</a>

        <a href="pos.php">Point of Sale</a>

        <a href="sales.php">Sales History</a>

        <a href="manage_cashiers.php" class="active">
            Manage Cashiers
        </a>

        <a href="logout.php">Logout</a>

    </div>


    <!-- MAIN CONTENT -->
    <div class="main">

        <div class="page-header">

            <div>
                <h1>Manage Cashiers</h1>

                <p>
                    View the cashier accounts registered in your POS.
                </p>
            </div>

            <a href="register_cashier.php" class="add-btn">
                + Create Cashier
            </a>

        </div>


        <!-- SUMMARY -->
        <div class="summary-card">

            <div class="summary-title">
                Total Cashiers
            </div>

            <div class="summary-number">
                <?php echo number_format($total_cashiers); ?>
            </div>

        </div>


        <!-- CASHIER TABLE -->
        <div class="table-card">

            <h2>Registered Cashiers</h2>

            <table>

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Cashier ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Date Created</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($total_cashiers > 0): ?>

                        <?php
                        $number = 1;

                        while ($cashier = $result->fetch_assoc()):
                        ?>

                            <tr>

                                <td>
                                    <?php echo $number++; ?>
                                </td>

                                <td>
                                    #<?php echo (int) $cashier['id']; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $cashier['name']
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $cashier['email']
                                    );
                                    ?>
                                </td>

                                <td>
                                    <span class="badge">
                                        Cashier
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        "d M Y, h:i A",
                                        strtotime($cashier['created_at'])
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty">
                                No cashier accounts have been created yet.
                                Click "Create Cashier" to add one.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</body>
</html>