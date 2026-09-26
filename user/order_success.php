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

$user_id = (int)$_SESSION["user_id"];
$order_id = isset($_GET["order_id"]) ? (int)$_GET["order_id"] : 0;

if ($order_id <= 0) {
    header("Location: books.php");
    exit();
}

$query = "
    SELECT
        orders.*,
        books.book_name
    FROM orders
    LEFT JOIN books ON orders.book_id = books.book_id
    WHERE orders.order_id = $order_id
    AND orders.user_id = $user_id
";

$result = mysqli_query($conn, $query);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header("Location: books.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Confirmed | Library Management System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        body {
            margin: 0;
            background: #f5f6f8;
            font-family: Arial, Helvetica, sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #171717;
            color: white;
            padding: 25px 15px;
        }

        .logo {
            font-size: 22px;
            font-weight: 700;
            padding: 0 15px 30px;
        }

        .logo i {
            margin-right: 10px;
        }

        .menu-title {
            font-size: 12px;
            color: #999;
            padding: 0 15px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .sidebar a {
            display: block;
            color: #cfcfcf;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 7px;
            font-size: 15px;
        }

        .sidebar a i {
            width: 25px;
        }

        .sidebar a:hover {
            background: #2b2b2b;
            color: white;
        }

        .logout {
            margin-top: 25px;
            color: #ff7777 !important;
        }

        .main {
            margin-left: 250px;
            min-height: 100vh;
            padding: 50px 35px;
        }

        .success-box {
            max-width: 750px;
            margin: 40px auto;
            background: white;
            border: 1px solid #e2e2e2;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
        }

        .success-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #e9f7ef;
            color: #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
        }

        .success-box h2 {
            font-weight: 700;
            margin-bottom: 10px;
        }

        .success-box > p {
            color: #777;
            margin-bottom: 30px;
        }

        .order-details {
            text-align: left;
            border: 1px solid #e4e4e4;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #777;
        }

        .detail-value {
            font-weight: 600;
            text-align: right;
        }

        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            background: #fff3cd;
            color: #856404;
            font-size: 13px;
        }

        .buttons {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-dark-custom {
            background: #222;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 7px;
            text-decoration: none;
        }

        .btn-dark-custom:hover {
            background: #000;
            color: white;
        }

        .btn-light-custom {
            background: white;
            color: #222;
            border: 1px solid #ccc;
            padding: 11px 20px;
            border-radius: 7px;
            text-decoration: none;
        }

        .btn-light-custom:hover {
            background: #f2f2f2;
            color: #222;
        }

        @media (max-width: 900px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                padding: 30px 20px;
            }
        }

    </style>

</head>

<body>

<div class="sidebar">

    <div class="logo">
        <i class="fa-solid fa-book-open"></i>
        Library
    </div>

    <div class="menu-title">Main Menu</div>

    <a href="dashboard.php">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
    </a>

    <a href="books.php">
        <i class="fa-solid fa-book"></i>
        Browse Books
    </a>

    <a href="issued_books.php">
        <i class="fa-solid fa-book-bookmark"></i>
        Issued Books
    </a>

    <a href="profile.php">
        <i class="fa-solid fa-user"></i>
        My Profile
    </a>

    <a href="../logout.php" class="logout">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>

</div>


<div class="main">

    <div class="success-box">

        <div class="success-icon">
            <i class="fa-solid fa-check"></i>
        </div>

        <h2>Order Placed Successfully</h2>

        <p>
            Your order has been received successfully.
            Our staff will review and process your order.
        </p>


        <div class="order-details">

            <div class="detail-row">

                <span class="detail-label">
                    Order ID
                </span>

                <span class="detail-value">
                    #<?php echo $order["order_id"]; ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Book
                </span>

                <span class="detail-value">
                    <?php echo htmlspecialchars($order["book_name"]); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Quantity
                </span>

                <span class="detail-value">
                    <?php echo $order["quantity"]; ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Mobile
                </span>

                <span class="detail-value">
                    <?php echo htmlspecialchars($order["mobile"]); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Payment Method
                </span>

                <span class="detail-value">
                    <?php echo htmlspecialchars($order["payment_method"]); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Total Amount
                </span>

                <span class="detail-value">
                    ৳ <?php echo number_format($order["total_amount"]); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Order Status
                </span>

                <span class="detail-value">

                    <span class="status">
                        <?php echo htmlspecialchars($order["order_status"]); ?>
                    </span>

                </span>

            </div>

        </div>


        <div class="buttons">

            <a href="books.php" class="btn-light-custom">
                <i class="fa-solid fa-book me-2"></i>
                Browse More Books
            </a>

            <a href="orders.php" class="btn-dark-custom">
                <i class="fa-solid fa-receipt me-2"></i>
                My Orders
            </a>

        </div>

    </div>

</div>

</body>
</html>