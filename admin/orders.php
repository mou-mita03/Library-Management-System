<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: indexad.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "lms");

if (!$conn) {
    die("Database connection failed.");
}

$admin_id = (int) $_SESSION["admin_id"];

$message = "";
$error = "";


/* =========================
   ADMIN INFO
========================= */

$admin_query = "
    SELECT name, email, profile_image
    FROM admins
    WHERE id = $admin_id
    LIMIT 1
";

$admin_result = mysqli_query($conn, $admin_query);
$admin = mysqli_fetch_assoc($admin_result);


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

        $order_status = mysqli_real_escape_string(
            $conn,
            $order_status
        );

        $payment_status = mysqli_real_escape_string(
            $conn,
            $payment_status
        );

        $update_query = "
            UPDATE orders
            SET
                order_status = '$order_status',
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
   ORDER COUNT
========================= */

$count_query = "
    SELECT COUNT(*) AS total
    FROM orders
";

$count_result = mysqli_query($conn, $count_query);
$total_orders = mysqli_fetch_assoc($count_result)["total"];


/* =========================
   PENDING ORDER COUNT
========================= */

$pending_query = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status = 'Pending'
";

$pending_result = mysqli_query($conn, $pending_query);
$pending_orders = mysqli_fetch_assoc($pending_result)["total"];


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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Orders - Admin Panel
    </title>


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


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


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;

            left: 0;
            top: 0;

            width: 245px;
            height: 100vh;

            background: #111827;

            color: white;

            padding: 25px 15px;

            overflow-y: auto;
        }


        .brand {
            font-size: 21px;

            font-weight: 700;

            padding: 0 12px 25px;

            border-bottom:
                1px solid #374151;

            margin-bottom: 20px;
        }


        .brand i {
            color: #60a5fa;
            margin-right: 8px;
        }


        .menu-title {
            color: #9ca3af;

            font-size: 11px;

            text-transform: uppercase;

            padding: 0 12px;

            margin-bottom: 10px;

            letter-spacing: 1px;
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
            margin-top: 18px;

            padding-top: 12px;

            border-top:
                1px solid #374151;
        }


        .pending-badge {
            float: right;

            background: #f59e0b;

            color: white;

            font-size: 10px;

            padding: 3px 7px;

            border-radius: 10px;
        }


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 245px;

            padding: 30px;
        }


        /* =========================
           TOPBAR
        ========================= */

        .topbar {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .page-title h2 {
            margin: 0 0 5px;

            font-size: 26px;

            font-weight: 700;
        }


        .page-title p {
            margin: 0;

            color: #6b7280;
        }


        .admin-info {
            display: flex;

            align-items: center;

            gap: 10px;

            font-weight: 600;

            color: #4b5563;
        }


        .admin-avatar {
            width: 44px;
            height: 44px;

            border-radius: 50%;

            overflow: hidden;

            background: #2563eb;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .admin-avatar img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        /* =========================
           SUMMARY
        ========================= */

        .summary-row {
            display: flex;

            gap: 15px;

            margin-bottom: 25px;
        }


        .summary-card {
            background: white;

            border-radius: 10px;

            padding: 18px 22px;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);

            min-width: 180px;
        }


        .summary-card strong {
            display: block;

            font-size: 24px;

            margin-bottom: 3px;
        }


        .summary-card span {
            color: #6b7280;

            font-size: 13px;
        }


        /* =========================
           ORDER TABLE
        ========================= */

        .order-card {
            background: white;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);
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


        .order-id {
            font-weight: 700;

            color: #111827;
        }


        .customer-name {
            font-weight: 700;
        }


        .customer-email {
            font-size: 11px;

            color: #9ca3af;
        }


        .mobile {
            color: #2563eb;

            font-weight: 600;

            white-space: nowrap;
        }


        .book-name {
            font-weight: 600;

            max-width: 170px;
        }


        .amount {
            color: #2563eb;

            font-weight: 700;
        }


        .address {
            max-width: 180px;

            color: #4b5563;
        }


        /* =========================
           BADGES
        ========================= */

        .badge-custom {
            display: inline-block;

            padding: 5px 9px;

            border-radius: 6px;

            font-size: 10px;

            font-weight: 700;
        }


        .payment-cod {
            background: #f3f4f6;

            color: #374151;
        }


        .payment-online {
            background: #eff6ff;

            color: #2563eb;
        }


        .status-pending {
            background: #fff7ed;

            color: #c2410c;
        }


        .status-confirmed {
            background: #eef2ff;

            color: #4338ca;
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


        .payment-paid {
            background: #ecfdf5;

            color: #15803d;
        }


        /* =========================
           UPDATE FORM
        ========================= */

        .update-box {
            min-width: 180px;
        }


        .update-box select {
            width: 100%;

            height: 35px;

            border: 1px solid #d1d5db;

            border-radius: 6px;

            padding: 0 8px;

            font-size: 11px;

            margin-bottom: 6px;
        }


        .btn-update {
            width: 100%;

            border: none;

            background: #2563eb;

            color: white;

            padding: 7px;

            border-radius: 6px;

            font-size: 11px;

            font-weight: 600;
        }


        .btn-update:hover {
            background: #1d4ed8;
        }


        .empty {
            padding: 70px 20px;

            text-align: center;

            color: #9ca3af;
        }


        .empty i {
            font-size: 45px;

            margin-bottom: 15px;
        }


        /* =========================
           MOBILE
        ========================= */

        @media(max-width: 768px) {

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;
            }


            .main {
                margin-left: 0;

                padding: 20px;
            }


            .topbar {
                align-items: flex-start;

                gap: 15px;
            }


            .summary-row {
                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">


    <div class="brand">

        <i class="fa-solid fa-book-open"></i>

        Library Admin

    </div>


    <div class="menu-title">

        Main Menu

    </div>


    <a
        href="dashboard.php"
        class="nav-link"
    >

        <i class="fa-solid fa-chart-line"></i>

        Dashboard

    </a>


    <a
        href="manage_books.php"
        class="nav-link"
    >

        <i class="fa-solid fa-book"></i>

        Manage Books

    </a>


    <a
        href="book_approvals.php"
        class="nav-link"
    >

        <i class="fa-solid fa-circle-check"></i>

        Book Approvals

    </a>


    <a
        href="manage_users.php"
        class="nav-link"
    >

        <i class="fa-solid fa-users"></i>

        Manage Users

    </a>


    <a
        href="staffs.php"
        class="nav-link"
    >

        <i class="fa-solid fa-user-tie"></i>

        Staff Management

    </a>


    <a
        href="orders.php"
        class="nav-link active"
    >

        <i class="fa-solid fa-cart-shopping"></i>

        Orders

        <?php if ($pending_orders > 0): ?>

            <span class="pending-badge">

                <?php echo $pending_orders; ?>

            </span>

        <?php endif; ?>

    </a>


    <a
        href="authors.php"
        class="nav-link"
    >

        <i class="fa-solid fa-pen-nib"></i>

        Authors

    </a>


    <a
        href="categories.php"
        class="nav-link"
    >

        <i class="fa-solid fa-layer-group"></i>

        Categories

    </a>


    <a
        href="issued_books.php"
        class="nav-link"
    >

        <i class="fa-solid fa-book-open-reader"></i>

        Issued Books

    </a>


    <a
        href="profile.php"
        class="nav-link"
    >

        <i class="fa-solid fa-user"></i>

        Profile

    </a>


    <div class="logout">

        <a
            href="admin_logout.php"
            class="nav-link"
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            Logout

        </a>

    </div>


</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">


        <div class="page-title">

            <h2>
                Customer Orders
            </h2>

            <p>
                Monitor and manage all customer orders.
            </p>

        </div>


        <div class="admin-info">


            <span>

                <?php
                echo htmlspecialchars(
                    $admin["name"]
                );
                ?>

            </span>


            <div class="admin-avatar">


                <?php if (
                    !empty(
                        $admin["profile_image"]
                    )
                ): ?>


                    <img
                        src="uploads/<?php
                        echo htmlspecialchars(
                            $admin["profile_image"]
                        );
                        ?>"
                        alt="Admin"
                    >


                <?php else: ?>


                    <i class="fa-solid fa-user"></i>


                <?php endif; ?>


            </div>


        </div>


    </div>


    <!-- MESSAGES -->

    <?php if ($message != ""): ?>

        <div class="alert alert-success">

            <i class="fa-solid fa-circle-check"></i>

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error != ""): ?>

        <div class="alert alert-danger">

            <i class="fa-solid fa-circle-exclamation"></i>

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- SUMMARY -->

    <div class="summary-row">


        <div class="summary-card">

            <strong>
                <?php echo $total_orders; ?>
            </strong>

            <span>
                Total Orders
            </span>

        </div>


        <div class="summary-card">

            <strong>
                <?php echo $pending_orders; ?>
            </strong>

            <span>
                Pending Orders
            </span>

        </div>


    </div>


    <!-- ORDERS -->

    <div class="order-card">


        <?php if (
            $result &&
            mysqli_num_rows($result) > 0
        ): ?>


            <div class="table-responsive">


                <table class="table mb-0">


                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Mobile
                            </th>

                            <th>
                                Book
                            </th>

                            <th>
                                Qty
                            </th>

                            <th>
                                Total
                            </th>

                            <th>
                                Payment
                            </th>

                            <th>
                                Address
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Update
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $order =
                        mysqli_fetch_assoc(
                            $result
                        )
                    ): ?>


                        <tr>


                            <!-- ORDER -->

                            <td>

                                <div class="order-id">

                                    #
                                    <?php
                                    echo $order["order_id"];
                                    ?>

                                </div>


                                <?php
                                $order_class =
                                    "status-" .
                                    strtolower(
                                        $order["order_status"]
                                    );
                                ?>


                                <span
                                    class="badge-custom <?php
                                    echo $order_class;
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $order["order_status"]
                                    );
                                    ?>

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

                                    <i
                                        class="fa-solid fa-phone"
                                    ></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $order["mobile"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- BOOK -->

                            <td>

                                <div class="book-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $order["book_name"]
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- QTY -->

                            <td>

                                <?php
                                echo $order["quantity"];
                                ?>

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


                                <?php if (
                                    $order["payment_method"]
                                    == "Online Payment"
                                ): ?>


                                    <span
                                        class="badge-custom payment-online"
                                    >

                                        Online Payment

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="badge-custom payment-cod"
                                    >

                                        Cash on Delivery

                                    </span>


                                <?php endif; ?>


                                <br>


                                <?php
                                $payment_class =
                                    $order["payment_status"]
                                    == "Paid"
                                    ? "payment-paid"
                                    : "status-pending";
                                ?>


                                <span
                                    class="badge-custom
                                    <?php
                                    echo $payment_class;
                                    ?>"
                                >

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

                                <small
                                    class="text-muted"
                                >

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
                                        value="<?php
                                        echo $order["order_id"];
                                        ?>"
                                    >


                                    <select
                                        name="order_status"
                                    >


                                        <option
                                            value="Pending"
                                            <?php
                                            if (
                                                $order["order_status"]
                                                == "Pending"
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
                                                $order["order_status"]
                                                == "Confirmed"
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
                                                $order["order_status"]
                                                == "Processing"
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
                                                $order["order_status"]
                                                == "Delivered"
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
                                                $order["order_status"]
                                                == "Cancelled"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >

                                            Cancelled

                                        </option>


                                    </select>


                                    <select
                                        name="payment_status"
                                    >


                                        <option
                                            value="Pending"
                                            <?php
                                            if (
                                                $order["payment_status"]
                                                == "Pending"
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
                                                $order["payment_status"]
                                                == "Paid"
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

                                        <i
                                            class="fa-solid fa-floppy-disk"
                                        ></i>

                                        Update

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

                <i
                    class="fa-solid fa-cart-shopping"
                ></i>

                <h5>
                    No Orders Yet
                </h5>

                <p>
                    There are currently no customer orders.
                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>