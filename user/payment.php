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

if (!isset($_GET["order_id"])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int) $_GET["order_id"];

$query = "
    SELECT 
        orders.order_id,
        orders.quantity,
        orders.total_amount,
        orders.payment_method,
        orders.payment_status,
        orders.order_status,
        books.book_name
    FROM orders
    INNER JOIN books ON orders.book_id = books.book_id
    WHERE orders.order_id = $order_id
    AND orders.user_id = $user_id
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Order not found.");
}

$order = mysqli_fetch_assoc($result);

if ($order["payment_method"] !== "Online Payment") {
    header("Location: order_success.php?order_id=" . $order_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Online Payment - Library</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .payment-wrapper {
            max-width: 650px;
            margin: 70px auto;
            padding: 20px;
        }

        .payment-card {
            background: #fff;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .payment-icon {
            width: 70px;
            height: 70px;
            margin: auto;
            border-radius: 50%;
            background: #eef4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 30px;
        }

        .payment-title {
            text-align: center;
            margin-top: 20px;
            font-weight: 700;
            color: #1f2937;
        }

        .payment-subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .order-info {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6b7280;
        }

        .info-value {
            font-weight: 600;
            color: #111827;
        }

        .total {
            font-size: 22px;
            color: #2563eb;
        }

        .pay-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .pay-btn:hover {
            background: #1d4ed8;
        }

        .back-btn {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #6b7280;
            text-decoration: none;
        }

        .back-btn:hover {
            text-decoration: none;
            color: #2563eb;
        }
    </style>
</head>

<body>

<div class="payment-wrapper">

    <div class="payment-card">

        <div class="payment-icon">
            <i class="fa-solid fa-credit-card"></i>
        </div>

        <h2 class="payment-title">
            Online Payment
        </h2>

        <p class="payment-subtitle">
            Complete your payment securely
        </p>

        <div class="order-info">

            <div class="info-row">
                <span class="info-label">Order ID</span>
                <span class="info-value">
                    #<?php echo $order["order_id"]; ?>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Book</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($order["book_name"]); ?>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Quantity</span>
                <span class="info-value">
                    <?php echo $order["quantity"]; ?>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Payment Method</span>
                <span class="info-value">
                    Online Payment
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Total Amount</span>
                <span class="info-value total">
                    ৳<?php echo number_format($order["total_amount"]); ?>
                </span>
            </div>

        </div>

        <!-- Temporary payment button -->
        <form action="payment_process.php" method="POST">

            <input type="hidden"
                   name="order_id"
                   value="<?php echo $order["order_id"]; ?>">

            <button type="submit" class="pay-btn">
                <i class="fa-solid fa-lock"></i>
                Proceed to Payment
            </button>

        </form>

        <a href="orders.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back to My Orders
        </a>

    </div>

</div>

</body>
</html>