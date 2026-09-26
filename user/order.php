<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "lms");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$user_id = (int)$_SESSION["user_id"];
$book_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($book_id <= 0) {
    header("Location: books.php");
    exit();
}

/* Get user information */
$user_query = "SELECT name, email, mobile, address FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    header("Location: ../logout.php");
    exit();
}

/* Get book information */
$book_query = "
    SELECT
        books.book_id,
        books.book_name,
        books.book_no,
        books.book_price,
        authors.author_name,
        category.cat_name
    FROM books
    LEFT JOIN authors ON books.author_id = authors.author_id
    LEFT JOIN category ON books.cat_id = category.cat_id
    WHERE books.book_id = $book_id
";

$book_result = mysqli_query($conn, $book_query);
$book = mysqli_fetch_assoc($book_result);

if (!$book) {
    header("Location: books.php");
    exit();
}

$error = "";

/* Place Order */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $quantity = isset($_POST["quantity"]) ? (int)$_POST["quantity"] : 1;
    $mobile = trim($_POST["mobile"] ?? "");
    $payment_method = $_POST["payment_method"] ?? "";
    $delivery_address = trim($_POST["delivery_address"] ?? "");

    if ($quantity < 1) {

        $error = "Please enter a valid quantity.";

    } elseif ($mobile === "") {

        $error = "Mobile number is required.";

    } elseif (!preg_match('/^[0-9+\-\s]{10,15}$/', $mobile)) {

        $error = "Please enter a valid mobile number.";

    } elseif (!in_array($payment_method, ["Cash on Delivery", "Online Payment"])) {

        $error = "Please select a valid payment method.";

    } elseif ($delivery_address === "") {

        $error = "Please enter your delivery address.";

    } else {

        $price = (int)$book["book_price"];
        $total_amount = $price * $quantity;

        $payment_status = "Pending";
        $order_status = "Pending";

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO orders
            (
                user_id,
                book_id,
                quantity,
                total_amount,
                payment_method,
                payment_status,
                order_status,
                delivery_address,
                mobile
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "iiiisssss",
            $user_id,
            $book_id,
            $quantity,
            $total_amount,
            $payment_method,
            $payment_status,
            $order_status,
            $delivery_address,
            $mobile
        );

        if (mysqli_stmt_execute($stmt)) {

            $order_id = mysqli_insert_id($conn);

            /*
             * Online Payment will be connected later.
             */
            if ($payment_method === "Online Payment") {

                header("Location: payment.php?order_id=" . $order_id);
                exit();

            }

            header("Location: order_success.php?order_id=" . $order_id);
            exit();

        } else {

            $error = "Something went wrong. Please try again.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Place Order | Library Management System</title>

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

        .page-title {
            margin-bottom: 30px;
        }

        .page-title h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .page-title p {
            margin-top: 7px;
            color: #777;
        }

        .back-btn {
            color: #333;
            text-decoration: none;
            font-size: 14px;
        }

        .back-btn:hover {
            text-decoration: underline;
        }

        /* Order Layout */

        .order-box {
            background: white;
            border: 1px solid #e3e3e3;
            border-radius: 10px;
            padding: 28px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        /* Book */

        .book-summary {
            border: 1px solid #e5e5e5;
            border-radius: 9px;
            padding: 20px;
            background: #fafafa;
        }

        .book-icon {
            width: 55px;
            height: 55px;
            border-radius: 8px;
            background: #e9eaec;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            color: #333;
            margin-bottom: 15px;
        }

        .book-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .book-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 7px;
        }

        .book-info i {
            width: 20px;
        }

        .price {
            font-size: 20px;
            font-weight: 700;
            margin-top: 15px;
        }

        /* Form */

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            min-height: 45px;
            border-radius: 7px;
        }

        textarea.form-control {
            min-height: 110px;
        }

        /* Payment */

        .payment-option {
            display: block;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: 0.2s;
        }

        .payment-option:hover {
            border-color: #999;
            background: #fafafa;
        }

        .payment-option input {
            margin-right: 10px;
        }

        .payment-title {
            font-weight: 600;
        }

        .payment-description {
            display: block;
            color: #777;
            font-size: 13px;
            margin-left: 25px;
            margin-top: 3px;
        }

        /* Total */

        .total-box {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 18px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-price {
            font-size: 24px;
            font-weight: 700;
        }

        .btn-place {
            width: 100%;
            min-height: 48px;
            background: #222;
            color: white;
            border: none;
            border-radius: 7px;
            font-weight: 600;
            margin-top: 20px;
        }

        .btn-place:hover {
            background: #000;
            color: white;
        }

        .required {
            color: red;
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


<!-- Main Content -->

<div class="main">

    <div class="page-title">

        <a href="books.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Books
        </a>

        <h2 class="mt-3">Place Your Order</h2>

        <p>
            Review your book and complete the order information.
        </p>

    </div>


    <?php if ($error !== "") { ?>

        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>


    <form method="POST">

        <div class="row g-4">


            <!-- Book Details -->

            <div class="col-lg-5">

                <div class="order-box">

                    <div class="section-title">
                        <i class="fa-solid fa-book me-2"></i>
                        Book Details
                    </div>

                    <div class="book-summary">

                        <div class="book-icon">
                            <i class="fa-solid fa-book"></i>
                        </div>

                        <div class="book-name">
                            <?php echo htmlspecialchars($book["book_name"]); ?>
                        </div>

                        <div class="book-info">
                            <i class="fa-solid fa-user-pen"></i>
                            <?php echo htmlspecialchars($book["author_name"] ?? "Unknown Author"); ?>
                        </div>

                        <div class="book-info">
                            <i class="fa-solid fa-layer-group"></i>
                            <?php echo htmlspecialchars($book["cat_name"] ?? "Uncategorized"); ?>
                        </div>

                        <div class="book-info">
                            <i class="fa-solid fa-hashtag"></i>
                            Book No: <?php echo htmlspecialchars($book["book_no"]); ?>
                        </div>

                        <div class="price">
                            ৳
                            <span id="bookPrice">
                                <?php echo number_format($book["book_price"]); ?>
                            </span>
                            / book
                        </div>

                    </div>

                </div>

            </div>


            <!-- Order Information -->

            <div class="col-lg-7">

                <div class="order-box">

                    <div class="section-title">
                        <i class="fa-solid fa-cart-shopping me-2"></i>
                        Order Information
                    </div>


                    <!-- Quantity -->

                    <div class="mb-4">

                        <label class="form-label">
                            Quantity <span class="required">*</span>
                        </label>

                        <input
                            type="number"
                            name="quantity"
                            id="quantity"
                            class="form-control"
                            value="1"
                            min="1"
                            required>

                    </div>


                    <!-- Mobile Number -->

                    <div class="mb-4">

                        <label class="form-label">
                            Mobile Number <span class="required">*</span>
                        </label>

                        <input
                            type="tel"
                            name="mobile"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user["mobile"]); ?>"
                            placeholder="Enter your mobile number"
                            required>

                        <small class="text-muted">
                            We may contact you on this number regarding your order.
                        </small>

                    </div>


                    <!-- Delivery Address -->

                    <div class="mb-4">

                        <label class="form-label">
                            Delivery Address <span class="required">*</span>
                        </label>

                        <textarea
                            name="delivery_address"
                            class="form-control"
                            placeholder="Enter your complete delivery address"
                            required><?php echo htmlspecialchars($user["address"]); ?></textarea>

                    </div>


                    <!-- Payment Method -->

                    <div class="mb-3">

                        <label class="form-label">
                            Payment Method <span class="required">*</span>
                        </label>


                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="Cash on Delivery"
                                required>

                            <span class="payment-title">

                                <i class="fa-solid fa-money-bill-wave me-2"></i>

                                Cash on Delivery

                            </span>

                            <span class="payment-description">

                                Pay when your book is delivered.

                            </span>

                        </label>


                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="Online Payment"
                                required>

                            <span class="payment-title">

                                <i class="fa-solid fa-credit-card me-2"></i>

                                Online Payment

                            </span>

                            <span class="payment-description">

                                Pay online using the available payment gateway.

                            </span>

                        </label>

                    </div>


                    <!-- Total -->

                    <div class="total-box">

                        <div class="total-row">

                            <span>
                                Total Amount
                            </span>

                            <span class="total-price">

                                ৳
                                <span id="totalAmount">
                                    <?php echo number_format($book["book_price"]); ?>
                                </span>

                            </span>

                        </div>

                    </div>


                    <button type="submit" class="btn-place">

                        <i class="fa-solid fa-check me-2"></i>

                        Continue to Order

                    </button>

                </div>

            </div>

        </div>

    </form>

</div>


<script>

    const quantityInput = document.getElementById("quantity");
    const totalAmount = document.getElementById("totalAmount");

    const price = <?php echo (int)$book["book_price"]; ?>;

    function updateTotal() {

        let quantity = parseInt(quantityInput.value) || 1;

        if (quantity < 1) {

            quantity = 1;
            quantityInput.value = 1;

        }

        const total = price * quantity;

        totalAmount.innerText = total.toLocaleString("en-BD");

    }

    quantityInput.addEventListener("input", updateTotal);

</script>

</body>

</html>