<?php
session_start();

require_once "config.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
| A user must be logged in before using the POS.
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET CURRENT LOGGED-IN USER
|--------------------------------------------------------------------------
*/

$user_id = intval($_SESSION['user_id']);

$user_name = isset($_SESSION['user_name'])
    ? $_SESSION['user_name']
    : "POS User";


/*
|--------------------------------------------------------------------------
| GET AVAILABLE PRODUCTS
|--------------------------------------------------------------------------
*/

$product_sql = "
    SELECT id, name, price, quantity
    FROM products
    WHERE quantity > 0
    ORDER BY name ASC
";

$product_result = $conn->query($product_sql);


/*
|--------------------------------------------------------------------------
| COMPLETE SALE
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['complete_sale'])) {

    /*
    |--------------------------------------------------------------------------
    | GET CART DATA
    |--------------------------------------------------------------------------
    */

    $cart_data = $_POST['cart_data'] ?? "";

    $amount_paid = floatval($_POST['amount_paid'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | CHECK CART
    |--------------------------------------------------------------------------
    */

    if (empty($cart_data)) {

        $message = "Your cart is empty.";
        $message_type = "error";

    } else {

        $cart = json_decode($cart_data, true);


        if (!is_array($cart) || count($cart) === 0) {

            $message = "Invalid cart data.";
            $message_type = "error";

        } else {

            /*
            |--------------------------------------------------------------------------
            | CALCULATE TOTAL
            |--------------------------------------------------------------------------
            */

            $total = 0;

            foreach ($cart as $item) {

                $product_id = intval($item['id']);
                $quantity = intval($item['quantity']);

                if ($product_id <= 0 || $quantity <= 0) {
                    continue;
                }

                $price = floatval($item['price']);

                $subtotal = $price * $quantity;

                $total += $subtotal;
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK PAYMENT
            |--------------------------------------------------------------------------
            */

            if ($total <= 0) {

                $message = "The sale total is invalid.";
                $message_type = "error";

            } elseif ($amount_paid < $total) {

                $message = "Amount paid is less than the total amount.";
                $message_type = "error";

            } else {

                /*
                |--------------------------------------------------------------------------
                | CALCULATE CHANGE
                |--------------------------------------------------------------------------
                */

                $change = $amount_paid - $total;


                /*
                |--------------------------------------------------------------------------
                | START DATABASE TRANSACTION
                |--------------------------------------------------------------------------
                */

                $conn->begin_transaction();


                try {

                    /*
                    |--------------------------------------------------------------------------
                    | INSERT SALE
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    | $user_id is now the ID of the logged-in user.
                    |--------------------------------------------------------------------------
                    */

                    $sale_sql = "
                        INSERT INTO sales
                        (
                            user_id,
                            total_amount,
                            amount_paid,
                            change_amount
                        )
                        VALUES (?, ?, ?, ?)
                    ";

                    $sale_stmt = $conn->prepare($sale_sql);

                    if (!$sale_stmt) {
                        throw new Exception("Unable to prepare sale query.");
                    }


                    $sale_stmt->bind_param(
                        "iddd",
                        $user_id,
                        $total,
                        $amount_paid,
                        $change
                    );


                    if (!$sale_stmt->execute()) {
                        throw new Exception("Unable to save sale.");
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | GET NEW SALE ID
                    |--------------------------------------------------------------------------
                    */

                    $sale_id = $conn->insert_id;


                    /*
                    |--------------------------------------------------------------------------
                    | PREPARE SALE ITEM QUERY
                    |--------------------------------------------------------------------------
                    */

                    $item_sql = "
                        INSERT INTO sale_items
                        (
                            sale_id,
                            product_id,
                            quantity,
                            price,
                            subtotal
                        )
                        VALUES (?, ?, ?, ?, ?)
                    ";

                    $item_stmt = $conn->prepare($item_sql);

                    if (!$item_stmt) {
                        throw new Exception("Unable to prepare sale item query.");
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PREPARE STOCK UPDATE
                    |--------------------------------------------------------------------------
                    */

                    $stock_sql = "
                        UPDATE products
                        SET quantity = quantity - ?
                        WHERE id = ?
                        AND quantity >= ?
                    ";

                    $stock_stmt = $conn->prepare($stock_sql);

                    if (!$stock_stmt) {
                        throw new Exception("Unable to prepare stock query.");
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PROCESS EVERY CART ITEM
                    |--------------------------------------------------------------------------
                    */

                    foreach ($cart as $item) {

                        $product_id = intval($item['id']);
                        $quantity = intval($item['quantity']);


                        if ($product_id <= 0 || $quantity <= 0) {
                            throw new Exception("Invalid product or quantity.");
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | GET CURRENT PRODUCT FROM DATABASE
                        |--------------------------------------------------------------------------
                        | We do NOT blindly trust the price sent by the browser.
                        |--------------------------------------------------------------------------
                        */

                        $product_sql = "
                            SELECT id, name, price, quantity
                            FROM products
                            WHERE id = ?
                            FOR UPDATE
                        ";

                        $product_stmt = $conn->prepare($product_sql);

                        if (!$product_stmt) {
                            throw new Exception("Unable to check product.");
                        }


                        $product_stmt->bind_param(
                            "i",
                            $product_id
                        );

                        $product_stmt->execute();

                        $product_check = $product_stmt->get_result();


                        if ($product_check->num_rows === 0) {

                            throw new Exception(
                                "Product ID " . $product_id . " was not found."
                            );
                        }


                        $product = $product_check->fetch_assoc();


                        /*
                        |--------------------------------------------------------------------------
                        | CHECK STOCK
                        |--------------------------------------------------------------------------
                        */

                        if ($product['quantity'] < $quantity) {

                            throw new Exception(
                                "Not enough stock for " . $product['name'] .
                                ". Available stock: " . $product['quantity']
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | USE DATABASE PRICE
                        |--------------------------------------------------------------------------
                        */

                        $price = floatval($product['price']);

                        $subtotal = $price * $quantity;


                        /*
                        |--------------------------------------------------------------------------
                        | INSERT SALE ITEM
                        |--------------------------------------------------------------------------
                        */

                        $item_stmt->bind_param(
                            "iiidd",
                            $sale_id,
                            $product_id,
                            $quantity,
                            $price,
                            $subtotal
                        );


                        if (!$item_stmt->execute()) {

                            throw new Exception(
                                "Unable to save sale item."
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | REDUCE PRODUCT STOCK
                        |--------------------------------------------------------------------------
                        */

                        $stock_stmt->bind_param(
                            "iii",
                            $quantity,
                            $product_id,
                            $quantity
                        );


                        if (!$stock_stmt->execute()) {

                            throw new Exception(
                                "Unable to update product stock."
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | MAKE SURE STOCK WAS ACTUALLY REDUCED
                        |--------------------------------------------------------------------------
                        */

                        if ($stock_stmt->affected_rows === 0) {

                            throw new Exception(
                                "Stock update failed for " .
                                $product['name']
                            );
                        }

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | EVERYTHING SUCCESSFUL
                    |--------------------------------------------------------------------------
                    */

                    $conn->commit();


                    /*
                    |--------------------------------------------------------------------------
                    | SUCCESS MESSAGE
                    |--------------------------------------------------------------------------
                    */

                    $message =
                        "Sale completed successfully! " .
                        "Sale ID: #" . $sale_id .
                        " | Cashier: " . htmlspecialchars($user_name) .
                        " | Total: " . number_format($total) . " XAF" .
                        " | Change: " . number_format($change) . " XAF";


                    $message_type = "success";


                } catch (Exception $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | ROLLBACK IF SOMETHING FAILS
                    |--------------------------------------------------------------------------
                    */

                    $conn->rollback();

                    $message = "Sale failed: " . $e->getMessage();

                    $message_type = "error";
                }

            }
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

    <title>Point of Sale - Simple POS</title>


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


        /* =========================================================
           SIDEBAR
        ========================================================= */

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


        /* =========================================================
           MAIN
        ========================================================= */

        .main {

            margin-left: 230px;

            padding: 35px;
        }


        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .page-header h1 {

            font-size: 30px;

            color: #111827;
        }


        .cashier {

            background: white;

            padding: 12px 18px;

            border-radius: 8px;

            box-shadow: 0 3px 10px rgba(0,0,0,0.06);

            color: #374151;
        }


        .cashier strong {

            color: #2563eb;
        }


        /* =========================================================
           ALERTS
        ========================================================= */

        .message {

            padding: 15px 18px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-weight: bold;
        }


        .message.success {

            background: #dcfce7;

            color: #166534;
        }


        .message.error {

            background: #fee2e2;

            color: #991b1b;
        }


        /* =========================================================
           POS GRID
        ========================================================= */

        .pos-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 25px;
        }


        .card {

            background: white;

            border-radius: 12px;

            padding: 25px;

            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }


        .card h2 {

            color: #111827;

            margin-bottom: 20px;

            font-size: 21px;
        }


        /* =========================================================
           FORM
        ========================================================= */

        label {

            display: block;

            margin-bottom: 7px;

            font-weight: bold;

            color: #374151;
        }


        select,
        input {

            width: 100%;

            padding: 12px;

            border: 1px solid #d1d5db;

            border-radius: 7px;

            font-size: 15px;

            margin-bottom: 17px;

            outline: none;
        }


        select:focus,
        input:focus {

            border-color: #2563eb;
        }


        .product-info {

            background: #f3f4f6;

            border-radius: 8px;

            padding: 15px;

            margin-bottom: 20px;

            display: none;
        }


        .product-info p {

            margin-bottom: 6px;

            color: #374151;
        }


        .product-info strong {

            color: #111827;
        }


        .add-btn {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 7px;

            background: #2563eb;

            color: white;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;
        }


        .add-btn:hover {

            background: #1d4ed8;
        }


        /* =========================================================
           CART
        ========================================================= */

        .cart-empty {

            text-align: center;

            color: #6b7280;

            padding: 35px 10px;
        }


        .cart-table {

            width: 100%;

            border-collapse: collapse;

            margin-bottom: 20px;
        }


        .cart-table th {

            background: #f3f4f6;

            text-align: left;

            padding: 10px;

            font-size: 13px;
        }


        .cart-table td {

            padding: 10px;

            border-bottom: 1px solid #e5e7eb;

            font-size: 14px;
        }


        .remove-btn {

            background: #dc2626;

            color: white;

            border: none;

            border-radius: 5px;

            padding: 5px 8px;

            cursor: pointer;
        }


        .remove-btn:hover {

            background: #b91c1c;
        }


        /* =========================================================
           TOTAL
        ========================================================= */

        .total-box {

            background: #111827;

            color: white;

            padding: 18px;

            border-radius: 8px;

            margin-bottom: 20px;

            display: flex;

            justify-content: space-between;

            font-size: 20px;

            font-weight: bold;
        }


        .change-box {

            background: #ecfdf5;

            color: #166534;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-weight: bold;
        }


        .complete-btn {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 7px;

            background: #16a34a;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;
        }


        .complete-btn:hover {

            background: #15803d;
        }


        .complete-btn:disabled {

            background: #9ca3af;

            cursor: not-allowed;
        }


        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 900px) {

            .pos-grid {

                grid-template-columns: 1fr;
            }

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


            .page-header {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

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


    <a href="pos.php" class="active">
        Point of Sale
    </a>


    <a href="sales.php">
        Sales History
    </a>


    <a href="logout.php">
        Logout
    </a>

</div>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="main">


    <div class="page-header">

        <h1>
            Point of Sale
        </h1>


        <div class="cashier">

            Cashier:

            <strong>
                <?php echo htmlspecialchars($user_name); ?>
            </strong>

        </div>

    </div>


    <!-- MESSAGE -->

    <?php if (!empty($message)): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php echo $message; ?>

        </div>

    <?php endif; ?>


    <div class="pos-grid">


        <!-- =====================================================
             ADD PRODUCT
        ====================================================== -->

        <div class="card">

            <h2>
                Add Product to Cart
            </h2>


            <label for="productSelect">
                Select Product
            </label>


            <select id="productSelect">

                <option value="">
                    -- Select Product --
                </option>


                <?php while ($product = $product_result->fetch_assoc()): ?>

                    <option
                        value="<?php echo $product['id']; ?>"
                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                        data-price="<?php echo $product['price']; ?>"
                        data-stock="<?php echo $product['quantity']; ?>"
                    >

                        <?php echo htmlspecialchars($product['name']); ?>

                        -
                        <?php echo number_format($product['price']); ?>

                        XAF

                        -

                        Stock:
                        <?php echo $product['quantity']; ?>

                    </option>

                <?php endwhile; ?>

            </select>


            <!-- PRODUCT INFORMATION -->

            <div
                class="product-info"
                id="productInfo"
            >

                <p>
                    Product:
                    <strong id="selectedName"></strong>
                </p>

                <p>
                    Price:
                    <strong id="selectedPrice"></strong>
                    XAF
                </p>

                <p>
                    Available Stock:
                    <strong id="selectedStock"></strong>
                </p>

            </div>


            <label for="quantity">

                Quantity

            </label>


            <input
                type="number"
                id="quantity"
                min="1"
                value="1"
            >


            <button
                type="button"
                class="add-btn"
                id="addCartButton"
            >
                Add to Cart
            </button>

        </div>


        <!-- =====================================================
             CART AND PAYMENT
        ====================================================== -->

        <div class="card">

            <h2>
                Shopping Cart
            </h2>


            <div id="cartContainer">

                <div class="cart-empty">

                    Your cart is empty.

                </div>

            </div>


            <!-- TOTAL -->

            <div class="total-box">

                <span>
                    Total
                </span>

                <span id="totalDisplay">
                    0 XAF
                </span>

            </div>


            <!-- PAYMENT -->

            <form
                method="POST"
                action="pos.php"
                id="saleForm"
            >


                <input
                    type="hidden"
                    name="cart_data"
                    id="cartData"
                >


                <label for="amountPaid">

                    Amount Paid

                </label>


                <input
                    type="number"
                    name="amount_paid"
                    id="amountPaid"
                    min="0"
                    step="0.01"
                    placeholder="Enter amount paid"
                    required
                >


                <!-- CHANGE -->

                <div
                    class="change-box"
                    id="changeBox"
                >

                    Change:

                    <span id="changeDisplay">
                        0 XAF
                    </span>

                </div>


                <button
                    type="submit"
                    name="complete_sale"
                    class="complete-btn"
                    id="completeSaleButton"
                    disabled
                >
                    Complete Sale
                </button>


            </form>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/

let cart = [];


/*
|--------------------------------------------------------------------------
| ELEMENTS
|--------------------------------------------------------------------------
*/

const productSelect =
    document.getElementById("productSelect");

const quantityInput =
    document.getElementById("quantity");

const addCartButton =
    document.getElementById("addCartButton");

const cartContainer =
    document.getElementById("cartContainer");

const totalDisplay =
    document.getElementById("totalDisplay");

const amountPaid =
    document.getElementById("amountPaid");

const changeDisplay =
    document.getElementById("changeDisplay");

const cartData =
    document.getElementById("cartData");

const completeSaleButton =
    document.getElementById("completeSaleButton");

const productInfo =
    document.getElementById("productInfo");

const selectedName =
    document.getElementById("selectedName");

const selectedPrice =
    document.getElementById("selectedPrice");

const selectedStock =
    document.getElementById("selectedStock");


/*
|--------------------------------------------------------------------------
| SHOW PRODUCT INFORMATION
|--------------------------------------------------------------------------
*/

productSelect.addEventListener("change", function () {

    const option =
        productSelect.options[
            productSelect.selectedIndex
        ];


    if (!productSelect.value) {

        productInfo.style.display = "none";

        return;
    }


    const name =
        option.dataset.name;

    const price =
        parseFloat(option.dataset.price);

    const stock =
        parseInt(option.dataset.stock);


    selectedName.textContent = name;

    selectedPrice.textContent =
        price.toLocaleString();

    selectedStock.textContent =
        stock;


    productInfo.style.display = "block";


    quantityInput.max = stock;

});


/*
|--------------------------------------------------------------------------
| ADD PRODUCT TO CART
|--------------------------------------------------------------------------
*/

addCartButton.addEventListener("click", function () {

    if (!productSelect.value) {

        alert("Please select a product.");

        return;
    }


    const option =
        productSelect.options[
            productSelect.selectedIndex
        ];


    const productId =
        parseInt(productSelect.value);

    const name =
        option.dataset.name;

    const price =
        parseFloat(option.dataset.price);

    const stock =
        parseInt(option.dataset.stock);

    const quantity =
        parseInt(quantityInput.value);


    /*
    |--------------------------------------------------------------------------
    | VALIDATE QUANTITY
    |--------------------------------------------------------------------------
    */

    if (quantity <= 0) {

        alert("Quantity must be at least 1.");

        return;
    }


    if (quantity > stock) {

        alert(
            "Only " +
            stock +
            " unit(s) available in stock."
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK IF PRODUCT ALREADY EXISTS
    |--------------------------------------------------------------------------
    */

    const existing =
        cart.find(
            item => item.id === productId
        );


    if (existing) {

        const newQuantity =
            existing.quantity + quantity;


        if (newQuantity > stock) {

            alert(
                "You cannot add more than " +
                stock +
                " unit(s) of " +
                name +
                "."
            );

            return;
        }


        existing.quantity =
            newQuantity;

    } else {

        cart.push({

            id: productId,

            name: name,

            price: price,

            quantity: quantity,

            stock: stock

        });

    }


    renderCart();


    /*
    |--------------------------------------------------------------------------
    | RESET PRODUCT SELECTION
    |--------------------------------------------------------------------------
    */

    productSelect.value = "";

    quantityInput.value = 1;

    productInfo.style.display = "none";

});


/*
|--------------------------------------------------------------------------
| DISPLAY CART
|--------------------------------------------------------------------------
*/

function renderCart() {

    if (cart.length === 0) {

        cartContainer.innerHTML = `
            <div class="cart-empty">
                Your cart is empty.
            </div>
        `;

        totalDisplay.textContent =
            "0 XAF";

        cartData.value = "";

        completeSaleButton.disabled = true;

        updateChange();

        return;
    }


    let html = `

        <table class="cart-table">

            <thead>

                <tr>

                    <th>Product</th>

                    <th>Qty</th>

                    <th>Price</th>

                    <th>Subtotal</th>

                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

    `;


    let total = 0;


    cart.forEach(function (item, index) {

        const subtotal =
            item.price * item.quantity;


        total += subtotal;


        html += `

            <tr>

                <td>
                    ${escapeHtml(item.name)}
                </td>

                <td>
                    ${item.quantity}
                </td>

                <td>
                    ${item.price.toLocaleString()} XAF
                </td>

                <td>
                    ${subtotal.toLocaleString()} XAF
                </td>

                <td>

                    <button
                        type="button"
                        class="remove-btn"
                        onclick="removeFromCart(${index})"
                    >
                        Remove
                    </button>

                </td>

            </tr>

        `;

    });


    html += `

            </tbody>

        </table>

    `;


    cartContainer.innerHTML =
        html;


    totalDisplay.textContent =
        total.toLocaleString() +
        " XAF";


    /*
    |--------------------------------------------------------------------------
    | STORE CART IN HIDDEN INPUT
    |--------------------------------------------------------------------------
    */

    cartData.value =
        JSON.stringify(cart);


    completeSaleButton.disabled =
        false;


    updateChange();

}


/*
|--------------------------------------------------------------------------
| REMOVE FROM CART
|--------------------------------------------------------------------------
*/

function removeFromCart(index) {

    cart.splice(index, 1);

    renderCart();

}


/*
|--------------------------------------------------------------------------
| CALCULATE CHANGE
|--------------------------------------------------------------------------
*/

amountPaid.addEventListener(
    "input",
    updateChange
);


function updateChange() {

    let total = 0;


    cart.forEach(function (item) {

        total +=
            item.price *
            item.quantity;

    });


    const paid =
        parseFloat(amountPaid.value) || 0;


    const change =
        paid - total;


    if (change >= 0) {

        changeDisplay.textContent =
            change.toLocaleString() +
            " XAF";

        changeDisplay.style.color =
            "#166534";

    } else {

        changeDisplay.textContent =
            "Insufficient payment";

        changeDisplay.style.color =
            "#dc2626";
    }

}


/*
|--------------------------------------------------------------------------
| BEFORE SUBMITTING SALE
|--------------------------------------------------------------------------
*/

document.getElementById("saleForm")
    .addEventListener("submit", function (event) {

        if (cart.length === 0) {

            event.preventDefault();

            alert("Your cart is empty.");

            return;
        }


        let total = 0;


        cart.forEach(function (item) {

            total +=
                item.price *
                item.quantity;

        });


        const paid =
            parseFloat(amountPaid.value) || 0;


        if (paid < total) {

            event.preventDefault();

            alert(
                "Amount paid is less than the total amount."
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | MAKE SURE CART DATA IS UPDATED
        |--------------------------------------------------------------------------
        */

        cartData.value =
            JSON.stringify(cart);

    });


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
| Prevent product names from being interpreted as HTML.
|--------------------------------------------------------------------------
*/

function escapeHtml(text) {

    const div =
        document.createElement("div");

    div.textContent = text;

    return div.innerHTML;
}


/*
|--------------------------------------------------------------------------
| INITIAL CART DISPLAY
|--------------------------------------------------------------------------
*/

renderCart();

</script>


</body>

</html>