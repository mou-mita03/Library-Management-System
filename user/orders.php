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

$query = "
    SELECT
        orders.*,
        books.book_name,
        books.book_no,
        authors.author_name
    FROM orders
    LEFT JOIN books ON orders.book_id = books.book_id
    LEFT JOIN authors ON books.author_id = authors.author_id
    WHERE orders.user_id = $user_id
    ORDER BY orders.order_id DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders | Library Management System</title>

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
            color: #222;
        }

        /* Sidebar */

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
            letter-spacing: 1px;
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

        .sidebar a:hover,
        .sidebar a.active {
            background: #2b2b2b;
            color: white;
        }

        .logout {
            margin-top: 25px;
            color: #ff7777 !important;
        }

        /* Main */

        .main {
            margin-left: 250px;
            padding: 35px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .page-header p {
            margin-top: 7px;
            color: #777;
        }

        /* Order table */

        .orders-box {
            background: white;
            border: 1px solid #e3e3e3;
            border-radius: 10px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table thead th {
            background: #f7f7f7;
            border-bottom: 1px solid #ddd;
            padding: 16px;
            font-size: 13px;
            color: #555;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 16px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .book-name {
            font-weight: 600;
        }

        .author {
            color: #777;
            font-size: 13px;
            margin-top: 4px;
        }

        .amount {
            font-weight: 700;
            white-space: nowrap;
        }

        /* Status */

        .badge-status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .confirmed {
            background: #cff4fc;
            color: #055160;
        }

        .processing {
            background: #e2e3ff;
            color: #3d3f91;
        }

        .delivered {
            background: #d1e7dd;
            color: #0f5132;
        }

        .cancelled {
            background: #f8d7da;
            color: #842029;
        }

        /* Payment */

        .payment {
            font-size: 13px;
            color: #555;
        }

        .payment i {
            margin-right: 5px;
        }

        /* Empty */

        .empty {
            text-align: center;
            padding: 70px 20px;
        }

        .empty i {
            font-size: 45px;
            color: #aaa;
            margin-bottom: 18px;
        }

        .empty h4 {
            font-weight: 700;
        }

        .empty p {
            color: #777;
        }

        .browse-btn {
            display: inline-block;
            margin-top: 10px;
            background: #222;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 7px;
        }

        .browse-btn:hover {
            background: #000;
            color: white;
        }

        @media (max-width: 1100px) {

            .orders-box {
                overflow-x: auto;
            }

            .table {
                min-width: 950px;
            }
        }

        @media (max-width: 900px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                padding: 25px;
            }
        }

    </style>

</head>

<body>

<!-- Sidebar -->

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

    <a href="orders.php" class="active">
        <i class="fa-solid fa-cart-shopping"></i>
        My Orders
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


<!-- Main -->

<div class="main">

    <div class="page-header">

        <h2>My Orders</h2>

        <p>
            View and track all your book orders.
        </p>

    </div>


    <div class="orders-box">

        <?php if (mysqli_num_rows($result) > 0) { ?>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>

                            <th>Order ID</th>

                            <th>Book</th>

                            <th>Quantity</th>

                            <th>Total</th>

                            <th>Payment</th>

                            <th>Payment Status</th>

                            <th>Order Status</th>

                            <th>Order Date</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while ($order = mysqli_fetch_assoc($result)) { ?>

                        <?php
                        $status = strtolower($order["order_status"]);

                        $status_class = "pending";

                        if ($status === "confirmed") {
                            $status_class = "confirmed";
                        } elseif ($status === "processing") {
                            $status_class = "processing";
                        } elseif ($status === "delivered") {
                            $status_class = "delivered";
                        } elseif ($status === "cancelled") {
                            $status_class = "cancelled";
                        }

                        $payment_status = strtolower($order["payment_status"]);

                        $payment_class = "pending";

                        if ($payment_status === "paid") {
                            $payment_class = "delivered";
                        }
                        ?>

                        <tr>

                            <td>
                                <strong>
                                    #<?php echo $order["order_id"]; ?>
                                </strong>
                            </td>


                            <td>

                                <div class="book-name">
                                    <?php echo htmlspecialchars($order["book_name"]); ?>
                                </div>

                                <div class="author">
                                    <?php echo htmlspecialchars($order["author_name"] ?? "Unknown Author"); ?>
                                </div>

                            </td>


                            <td>
                                <?php echo $order["quantity"]; ?>
                            </td>


                            <td>

                                <span class="amount">
                                    ৳ <?php echo number_format($order["total_amount"]); ?>
                                </span>

                            </td>


                            <td>

                                <span class="payment">

                                    <?php if ($order["payment_method"] === "Cash on Delivery") { ?>

                                        <i class="fa-solid fa-money-bill-wave"></i>

                                    <?php } else { ?>

                                        <i class="fa-solid fa-credit-card"></i>

                                    <?php } ?>

                                    <?php echo htmlspecialchars($order["payment_method"]); ?>

                                </span>

                            </td>


                            <td>

                                <span class="badge-status <?php echo $payment_class; ?>">

                                    <?php echo htmlspecialchars($order["payment_status"]); ?>

                                </span>

                            </td>


                            <td>

                                <span class="badge-status <?php echo $status_class; ?>">

                                    <?php echo htmlspecialchars($order["order_status"]); ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    "d M Y, h:i A",
                                    strtotime($order["order_date"])
                                );
                                ?>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        <?php } else { ?>

            <div class="empty">

                <i class="fa-solid fa-cart-shopping"></i>

                <h4>No Orders Yet</h4>

                <p>
                    You haven't placed any book orders yet.
                </p>

                <a href="books.php" class="browse-btn">
                    <i class="fa-solid fa-book me-2"></i>
                    Browse Books
                </a>

            </div>

        <?php } ?>

    </div>

</div>

</body>

</html>