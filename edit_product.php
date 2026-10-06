<?php

session_start();

require_once "config.php";


// =========================================================
// CHECK PRODUCT ID
// =========================================================

if (!isset($_GET['id'])) {

    header("Location: products.php");

    exit;
}


$product_id = intval($_GET['id']);


// =========================================================
// GET PRODUCT
// =========================================================

$stmt = $conn->prepare("
    SELECT id, name, price, quantity
    FROM products
    WHERE id = ?
");

$stmt->bind_param(
    "i",
    $product_id
);

$stmt->execute();

$result = $stmt->get_result();

$product = $result->fetch_assoc();

$stmt->close();


if (!$product) {

    $_SESSION['message'] =
        "Product not found.";

    $_SESSION['message_type'] =
        "error";

    header("Location: products.php");

    exit;
}


// =========================================================
// UPDATE PRODUCT
// =========================================================

if (isset($_POST['update_product'])) {

    $name =
        trim($_POST['name']);

    $price =
        floatval($_POST['price']);

    $quantity =
        intval($_POST['quantity']);


    // -----------------------------------------------------
    // VALIDATION
    // -----------------------------------------------------

    if ($name === "") {

        $error =
            "Product name is required.";

    } elseif ($price < 0) {

        $error =
            "Price cannot be negative.";

    } elseif ($quantity < 0) {

        $error =
            "Quantity cannot be negative.";

    } else {


        // -------------------------------------------------
        // UPDATE DATABASE
        // -------------------------------------------------

        $update = $conn->prepare("
            UPDATE products
            SET
                name = ?,
                price = ?,
                quantity = ?
            WHERE id = ?
        ");


        $update->bind_param(
            "sdii",
            $name,
            $price,
            $quantity,
            $product_id
        );


        if ($update->execute()) {

            $_SESSION['message'] =
                "Product updated successfully.";

            $_SESSION['message_type'] =
                "success";

            $update->close();

            header("Location: products.php");

            exit;

        } else {

            $error =
                "Unable to update product.";

        }


        $update->close();

    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Product - Simple POS</title>


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

    padding: 25px 15px;

}


.logo {

    text-align: center;

    font-size: 24px;

    font-weight: bold;

    color: white;

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
   MAIN
======================================================== */

.main {

    margin-left: 240px;

    padding: 40px;

}


/* ========================================================
   FORM CARD
======================================================== */

.card {

    max-width: 650px;

    margin: 30px auto;

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 2px 12px rgba(0,0,0,0.06);

}


.card h1 {

    margin-bottom: 8px;

}


.subtitle {

    color: #6b7280;

    margin-bottom: 30px;

}


/* ========================================================
   PRODUCT ID
======================================================== */

.product-id {

    background: #f3f4f6;

    padding: 10px;

    border-radius: 7px;

    margin-bottom: 25px;

}


.product-id strong {

    color: #374151;

}


/* ========================================================
   FORM
======================================================== */

.form-group {

    margin-bottom: 20px;

}


label {

    display: block;

    font-weight: bold;

    margin-bottom: 8px;

}


input {

    width: 100%;

    padding: 13px;

    border: 1px solid #d1d5db;

    border-radius: 7px;

    font-size: 15px;

}


input:focus {

    outline: none;

    border-color: #22c55e;

}


.buttons {

    display: flex;

    gap: 12px;

    margin-top: 25px;

}


.update-button {

    flex: 1;

    padding: 13px;

    border: none;

    border-radius: 7px;

    background: #22c55e;

    color: white;

    font-weight: bold;

    cursor: pointer;

}


.update-button:hover {

    background: #16a34a;

}


.cancel-button {

    flex: 1;

    padding: 13px;

    border-radius: 7px;

    background: #e5e7eb;

    color: #374151;

    text-align: center;

    text-decoration: none;

    font-weight: bold;

}


.cancel-button:hover {

    background: #d1d5db;

}


/* ========================================================
   ERROR
======================================================== */

.error {

    background: #fee2e2;

    color: #991b1b;

    padding: 14px;

    border-radius: 7px;

    margin-bottom: 20px;

}


</style>

</head>


<body>


<!-- ======================================================
     SIDEBAR
====================================================== -->

<aside class="sidebar">


<div class="logo">

    SIMPLE<span>POS</span>

</div>


<ul class="menu">


<li>

<a href="dashboard.php">

    📊 Dashboard

</a>

</li>


<li class="active">

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


<div class="card">


<h1>
    Edit Product
</h1>


<p class="subtitle">
    Update the product information below.
</p>


<!-- PRODUCT ID -->

<div class="product-id">

<strong>
    Product ID:
</strong>

#<?php echo $product['id']; ?>

</div>


<?php if (isset($error)): ?>


<div class="error">

<?php

echo htmlspecialchars(
    $error
);

?>

</div>


<?php endif; ?>



<!-- =====================================================
     EDIT FORM
===================================================== -->

<form method="POST">


<!-- PRODUCT NAME -->

<div class="form-group">

<label for="name">

Product Name

</label>


<input
    type="text"
    id="name"
    name="name"
    value="<?php echo htmlspecialchars(
        $product['name']
    ); ?>"
    required
>

</div>



<!-- PRICE -->

<div class="form-group">

<label for="price">

Price (XAF)

</label>


<input
    type="number"
    id="price"
    name="price"
    value="<?php echo $product['price']; ?>"
    min="0"
    step="0.01"
    required
>

</div>



<!-- QUANTITY -->

<div class="form-group">

<label for="quantity">

Quantity / Stock

</label>


<input
    type="number"
    id="quantity"
    name="quantity"
    value="<?php echo $product['quantity']; ?>"
    min="0"
    required
>

</div>



<!-- BUTTONS -->

<div class="buttons">


<button
    type="submit"
    name="update_product"
    class="update-button"
>

    Save Changes

</button>


<a
    href="products.php"
    class="cancel-button"
>

    Cancel

</a>


</div>


</form>


</div>


</main>


</body>

</html>
