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
        orders.order_date,
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment Successful - Library</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            margin: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .success-wrapper {
            max-width: 650px;
            margin: 60px auto;
            padding: 20px;
        }

        .success-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #e8f7ee;
            color: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
        }

        h2 {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }

        .order-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            text-align: left;
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            color: #6b7280;
        }

        .value {
            font-weight: 600;
            color: #111827;
            text-align: right;
        }

        .paid {
            color: #16a34a;
        }

        .confirmed {
            color: #2563eb;
        }

        .total {
            font-size: 20px;
            color: #2563eb;
        }

        .btn-row {
            display: flex;
            gap: 10px;
        }

        .btn-main {
            flex: 1;
            padding: 13px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            background: #2563eb;
            color: white;
        }

        .btn-main:hover {
            background: #1d4ed8;
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            flex: 1;
            padding: 13px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            background: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #d1d5db;
            color: #111827;
            text-decoration: none;
        }

        @media (max-width: 576px) {
            .success-card {
                padding: 25px 18px;
            }

            .btn-row {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="success-wrapper">

    <div class="success-card">

        <div class="success-icon">
            <i class="fa-solid fa-check"></i>
        </div>

        <h2>Payment Successful!</h2>

        <p class="subtitle">
            Your payment has been completed successfully.
        </p>

        <div class="order-box">

            <div class="info-row">
                <span class="label">Order ID</span>
                <span class="value">
                    #<?php echo $order["order_id"]; ?>
                </span>
            </div>

            <div class="info-row">
                <span class="label">Book</span>
                <span class="value">
                    <?php echo htmlspecialchars($order["book_name"]); ?>
                </span>
            </div>

            <div class="info-row">
                <span class="label">Quantity</span>
                <span class="value">
                    <?php echo $order["quantity"]; ?>
                </span>
            </div>

            <div class="info-row">
                <span class="label">Payment Method</span>
                <span class="value">
                    <?php echo htmlspecialchars($order["payment_method"]); ?>
                </span>
            </div>

            <div class="info-row">
                <span class="label">Payment Status</span>
                <span class="value paid">
                    <?php echo htmlspecialchars($order["payment_status"]); ?>
                </span>
            </div>

            <div class="info-row">
                <span class="label">Order Status</span>
                <span class="value confirmed">
                    <?php echo htmlspecialchars($order["order_status"]); ?>
                </span>
            </div>

            <div class="info-row">
                <span class="label">Total Amount</span>
                <span class="value total">
                    ৳<?php echo number_format($order["total_amount"]); ?>
                </span>
            </div>

        </div>

        <div class="btn-row">

            <a href="orders.php" class="btn-main">
                <i class="fa-solid fa-list"></i>
                My Orders
            </a>

            <a href="books.php" class="btn-secondary">
                <i class="fa-solid fa-book"></i>
                Browse Books
            </a>

        </div>

    </div>

</div>

</body>
</html>