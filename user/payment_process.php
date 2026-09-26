<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "lms");

if (!$conn) {
    die("Database connection failed.");
}

$user_id = $_SESSION["user_id"];

if (!isset($_POST["order_id"])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int) $_POST["order_id"];

/* Get order */
$query = "
    SELECT
        orders.order_id,
        orders.book_id,
        orders.quantity,
        orders.total_amount,
        orders.payment_method,
        orders.payment_status,
        orders.order_status,
        orders.delivery_address,
        orders.mobile,
        users.name,
        users.email,
        books.book_name
    FROM orders
    INNER JOIN users ON orders.user_id = users.id
    INNER JOIN books ON orders.book_id = books.book_id
    WHERE orders.order_id = $order_id
    AND orders.user_id = $user_id
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Order not found.");
}

$order = mysqli_fetch_assoc($result);

/*
|--------------------------------------------------------------------------
| Simple Online Payment
|--------------------------------------------------------------------------
| This is a simple demo payment system.
| No real money will be charged.
|--------------------------------------------------------------------------
*/

if ($order["payment_method"] !== "Online Payment") {
    header("Location: order_success.php?order_id=" . $order_id);
    exit();
}

if ($order["payment_status"] === "Paid") {
    header("Location: order_success.php?order_id=" . $order_id);
    exit();
}

/* Mark payment as paid */
$update = "
    UPDATE orders
    SET payment_status = 'Paid',
        order_status = 'Confirmed'
    WHERE order_id = $order_id
    AND user_id = $user_id
";

if (mysqli_query($conn, $update)) {

    header("Location: payment_success.php?order_id=" . $order_id);
    exit();

} else {

    die("Payment processing failed.");
}
?>