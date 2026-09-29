<?php
session_start();

if (!isset($_SESSION["staff_id"])) {
    header("Location: index.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "lms");

if (!$conn) {
    die("Database connection failed.");
}

$message = "";
$error = "";


/* =========================
   UPDATE ORDER
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_order"])) {

    $order_id = (int) $_POST["order_id"];
    $order_status = trim($_POST["order_status"]);
    $payment_status = trim($_POST["payment_status"]);

    $allowed_order_status = [
        "Pending",
        "Confirmed",
        "Processing",
        "Delivered",
        "Cancelled"
    ];

    $allowed_payment_status = [
        "Pending",
        "Paid"
    ];

    if (
        !in_array($order_status, $allowed_order_status) ||
        !in_array($payment_status, $allowed_payment_status)
    ) {

        $error = "Invalid order information.";

    } else {

        $order_status = mysqli_real_escape_string($conn, $order_status);
        $payment_status = mysqli_real_escape_string($conn, $payment_status);

        $update_query = "
            UPDATE orders
            SET order_status = '$order_status',
                payment_status = '$payment_status'
            WHERE order_id = $order_id
        ";

        if (mysqli_query($conn, $update_query)) {

            header("Location: orders.php?updated=1");
            exit();

        } else {

            $error = "Failed to update order.";
        }
    }
}


/* =========================
   SUCCESS MESSAGE
========================= */

if (isset($_GET["updated"])) {
    $message = "Order updated successfully.";
}


/* =========================
   GET ALL ORDERS
========================= */

$query = "
    SELECT
        orders.order_id,
        orders.quantity,
        orders.total_amount,
        orders.payment_method,
        orders.payment_status,
        orders.order_status,
        orders.delivery_address,
        orders.mobile,
        orders.order_date,

        users.name AS customer_name,
        users.email AS customer_email,

        books.book_name

    FROM orders

    INNER JOIN users
        ON orders.user_id = users.id

    INNER JOIN books
        ON orders.book_id = books.book_id

    ORDER BY orders.order_id DESC
";

$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Orders - Staff Panel</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            color: #1f2937;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 245px;
            height: 100vh;
            background: #111827;
            padding: 25px 15px;
        }

        .brand {
            color: white;
            font-size: 21px;
            font-weight: 700;
            padding: 0 12px 25px;
            border-bottom: 1px solid #374151;
            margin-bottom: 20px;
        }

        .brand i {
            color: #60a5fa;
            margin-right: 8px;
        }

        .nav-link {
            color: #d1d5db !important;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .nav-link i {
            width: 25px;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #2563eb;
            color: white !important;
        }

        .logout {
            position: absolute;
            bottom: 20px;
            left: 15px;
            right: 15px;
        }

        .main {
            margin-left: 245px;
            padding: 30px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h2 {
            margin: 0 0 5px;
            font-size: 25px;
            font-weight: 700;
        }

        .page-title p {
            margin: 0;
            color: #6b7280;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
            overflow: hidden;
        }

        .table th {
            border-top: none;
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
            font-size: 13px;
        }

        .customer-name {
            font-weight: 700;
            color: #111827;
        }

        .customer-email {
            color: #9ca3af;
            font-size: 11px;
        }

        .mobile {
            font-weight: 600;
            color: #2563eb;
        }

        .address {
            max-width: 180px;
            color: #4b5563;
        }

        .order-id {
            font-weight: 700;
        }

        .amount {
            font-weight: 700;
            color: #2563eb;
        }

        .payment-badge {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: #f3f4f6;
            color: #374151;
        }

        .status-pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .status-paid {
            background: #ecfdf5;
            color: #15803d;
        }

        .status-processing {
            background: #eff6ff;
            color: #2563eb;
        }

        .status-delivered {
            background: #ecfdf5;
            color: #15803d;
        }

        .status-cancelled {
            background: #fef2f2;
            color: #dc2626;
        }

        .status-confirmed {
            background: #eef2ff;
            color: #4338ca;
        }

        .update-box {
            min-width: 190px;
        }

        .update-box select {
            width: 100%;
            height: 36px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0 8px;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .update-box select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .btn-update {
            width: 100%;
            border: none;
            background: #2563eb;
            color: white;
            padding: 7px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-update:hover {
            background: #1d4ed8;
        }

        .empty {
            text-align: center;
            padding: 70px 20px;
            color: #9ca3af;
        }

        .empty i {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .alert {
            border-radius: 8px;
        }

        @media(max-width: 768px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .logout {
                position: static;
                margin-top: 15px;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

        }

    </style>

</head>

<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <div class="brand">
        <i class="fa-solid fa-book-open"></i>
        Library Staff
    </div>

    <a href="dashboard.php" class="nav-link">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
    </a>

    <a href="add_book.php" class="nav-link">
        <i class="fa-solid fa-plus"></i>
        Add Book
    </a>

    <a href="my_books.php" class="nav-link">
        <i class="fa-solid fa-book"></i>
        My Books
    </a>

    <a href="orders.php" class="nav-link active">
        <i class="fa-solid fa-cart-shopping"></i>
        Orders
    </a>

    <a href="profile.php" class="nav-link">
        <i class="fa-solid fa-user"></i>
        Profile
    </a>

    <div class="logout">

        <a href="logout.php" class="nav-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</div>


<!-- MAIN -->

<div class="main">

    <div class="page-title">

        <h2>Customer Orders</h2>

        <p>
            Manage book orders and delivery status.
        </p>

    </div>


    <?php if ($message != ""): ?>

        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <?php if ($error != ""): ?>

        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="order-card">

        <?php if ($result && mysqli_num_rows($result) > 0): ?>

            <div class="table-responsive">

                <table class="table mb-0">

                    <thead>

                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Book</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Address</th>
                            <th>Date</th>
                            <th>Update</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php while ($order = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <!-- ORDER -->

                            <td>
                                <div class="order-id">
                                    #<?php echo $order["order_id"]; ?>
                                </div>

                                <?php
                                $order_status_class =
                                    "status-" .
                                    strtolower(
                                        $order["order_status"]
                                    );
                                ?>

                                <span class="payment-badge <?php echo $order_status_class; ?>">
                                    <?php echo htmlspecialchars($order["order_status"]); ?>
                                </span>
                            </td>


                            <!-- CUSTOMER -->

                            <td>

                                <div class="customer-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $order["customer_name"]
                                    );
                                    ?>

                                </div>

                                <div class="customer-email">

                                    <?php
                                    echo htmlspecialchars(
                                        $order["customer_email"]
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- MOBILE -->

                            <td>

                                <span class="mobile">

                                    <i class="fa-solid fa-phone"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $order["mobile"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- BOOK -->

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $order["book_name"]
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- QUANTITY -->

                            <td>
                                <?php echo $order["quantity"]; ?>
                            </td>


                            <!-- TOTAL -->

                            <td>

                                <span class="amount">

                                    ৳<?php
                                    echo number_format(
                                        $order["total_amount"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- PAYMENT -->

                            <td>

                                <div class="payment-badge">

                                    <?php
                                    echo htmlspecialchars(
                                        $order["payment_method"]
                                    );
                                    ?>

                                </div>

                                <br>

                                <?php
                                $payment_class =
                                    $order["payment_status"] == "Paid"
                                    ? "status-paid"
                                    : "status-pending";
                                ?>

                                <span class="payment-badge <?php echo $payment_class; ?>">

                                    <?php
                                    echo htmlspecialchars(
                                        $order["payment_status"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- ADDRESS -->

                            <td>

                                <div class="address">

                                    <?php
                                    echo htmlspecialchars(
                                        $order["delivery_address"]
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $order["order_date"]
                                    )
                                );
                                ?>

                                <br>

                                <small class="text-muted">

                                    <?php
                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $order["order_date"]
                                        )
                                    );
                                    ?>

                                </small>

                            </td>


                            <!-- UPDATE -->

                            <td>

                                <form
                                    method="POST"
                                    class="update-box"
                                >

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?php echo $order["order_id"]; ?>"
                                    >


                                    <select name="order_status">

                                        <option
                                            value="Pending"
                                            <?php
                                            if (
                                                $order["order_status"] == "Pending"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Pending
                                        </option>

                                        <option
                                            value="Confirmed"
                                            <?php
                                            if (
                                                $order["order_status"] == "Confirmed"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Confirmed
                                        </option>

                                        <option
                                            value="Processing"
                                            <?php
                                            if (
                                                $order["order_status"] == "Processing"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Processing
                                        </option>

                                        <option
                                            value="Delivered"
                                            <?php
                                            if (
                                                $order["order_status"] == "Delivered"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Delivered
                                        </option>

                                        <option
                                            value="Cancelled"
                                            <?php
                                            if (
                                                $order["order_status"] == "Cancelled"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Cancelled
                                        </option>

                                    </select>


                                    <select name="payment_status">

                                        <option
                                            value="Pending"
                                            <?php
                                            if (
                                                $order["payment_status"] == "Pending"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Payment Pending
                                        </option>

                                        <option
                                            value="Paid"
                                            <?php
                                            if (
                                                $order["payment_status"] == "Paid"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Payment Paid
                                        </option>

                                    </select>


                                    <button
                                        type="submit"
                                        name="update_order"
                                        class="btn-update"
                                    >
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Update Order
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty">

                <i class="fa-solid fa-cart-shopping"></i>

                <h5>No Orders Yet</h5>

                <p>
                    There are currently no customer orders.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>