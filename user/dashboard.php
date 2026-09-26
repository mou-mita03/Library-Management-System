```php
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

$user_id = (int) $_SESSION["user_id"];


/* =========================
   USER INFORMATION
========================= */

$user_query = "
    SELECT
        id,
        name,
        email,
        mobile,
        address,
        profile_image
    FROM users
    WHERE id = $user_id
    LIMIT 1
";

$user_result = mysqli_query($conn, $user_query);

if (!$user_result || mysqli_num_rows($user_result) == 0) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$user = mysqli_fetch_assoc($user_result);


/* =========================
   TOTAL APPROVED BOOKS
========================= */

$book_query = "
    SELECT COUNT(*) AS total
    FROM books
    WHERE approval_status = 'Approved'
";

$book_result = mysqli_query($conn, $book_query);
$total_books = mysqli_fetch_assoc($book_result)["total"];


/* =========================
   ACTIVE ISSUED BOOKS
========================= */

$issued_query = "
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE student_id = $user_id
    AND status = 1
";

$issued_result = mysqli_query($conn, $issued_query);
$active_issued = mysqli_fetch_assoc($issued_result)["total"];


/* =========================
   RETURNED BOOKS
========================= */

$returned_query = "
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE student_id = $user_id
    AND status = 0
";

$returned_result = mysqli_query($conn, $returned_query);
$returned_books = mysqli_fetch_assoc($returned_result)["total"];


/* =========================
   DIGITAL BOOKS
========================= */

$pdf_query = "
    SELECT COUNT(*) AS total
    FROM books
    WHERE approval_status = 'Approved'
    AND book_file IS NOT NULL
    AND book_file != ''
";

$pdf_result = mysqli_query($conn, $pdf_query);
$total_pdf_books = mysqli_fetch_assoc($pdf_result)["total"];


/* =========================
   USER ORDERS
========================= */

$order_query = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE user_id = $user_id
";

$order_result = mysqli_query($conn, $order_query);
$total_orders = mysqli_fetch_assoc($order_result)["total"];


/* =========================
   RECENT APPROVED BOOKS
========================= */

$recent_query = "
    SELECT
        books.book_id,
        books.book_name,
        books.book_no,
        books.book_price,
        books.book_file,
        authors.author_name,
        category.cat_name
    FROM books

    LEFT JOIN authors
        ON books.author_id = authors.author_id

    LEFT JOIN category
        ON books.cat_id = category.cat_id

    WHERE books.approval_status = 'Approved'

    ORDER BY books.book_id DESC

    LIMIT 6
";

$recent_result = mysqli_query($conn, $recent_query);

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
        User Dashboard - Library Management System
    </title>


    <!-- Bootstrap -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
    >


    <!-- Font Awesome -->

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
            position: absolute;

            bottom: 20px;

            left: 15px;

            right: 15px;
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


        /* =========================
           USER INFO
        ========================= */

        .user-info {
            display: flex;

            align-items: center;

            gap: 10px;

            color: #4b5563;

            font-weight: 600;
        }


        .user-name {
            text-align: right;

            line-height: 1.3;
        }


        .user-email {
            font-size: 11px;

            color: #9ca3af;

            font-weight: 400;
        }


        .user-avatar {
            width: 45px;
            height: 45px;

            border-radius: 50%;

            overflow: hidden;

            background: #2563eb;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;
        }


        .user-avatar img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        /* =========================
           BACK TO HOME
        ========================= */

        .back-home {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            background: white;

            color: #2563eb;

            border: 1px solid #dbeafe;

            padding: 9px 14px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            margin-right: 15px;

            transition: 0.2s;
        }


        .back-home:hover {
            background: #eff6ff;

            color: #1d4ed8;

            text-decoration: none;

            border-color: #bfdbfe;
        }


        /* =========================
           WELCOME
        ========================= */

        .welcome-card {
            background: white;

            border-radius: 12px;

            padding: 25px;

            margin-bottom: 25px;

            border-left:
                4px solid #2563eb;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);
        }


        .welcome-card h4 {
            margin: 0 0 6px;

            font-weight: 700;
        }


        .welcome-card p {
            margin: 0;

            color: #6b7280;
        }


        /* =========================
           STAT CARDS
        ========================= */

        .stat-card {
            background: white;

            border-radius: 12px;

            padding: 22px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);
        }


        .stat-icon {
            width: 45px;
            height: 45px;

            border-radius: 9px;

            background: #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            margin-bottom: 15px;
        }


        .stat-card h3 {
            margin: 0;

            font-size: 27px;

            font-weight: 700;
        }


        .stat-card p {
            margin: 4px 0 0;

            color: #6b7280;

            font-size: 14px;
        }


        /* =========================
           QUICK ACTIONS
        ========================= */

        .quick-card {
            background: white;

            border-radius: 12px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);
        }


        .section-title {
            font-size: 17px;

            font-weight: 700;

            margin-bottom: 18px;
        }


        .quick-actions {
            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(170px, 1fr));

            gap: 12px;
        }


        .quick-btn {
            display: flex;

            align-items: center;

            gap: 10px;

            background: #f8fafc;

            border: 1px solid #e5e7eb;

            color: #374151;

            padding: 13px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;
        }


        .quick-btn:hover {
            background: #eff6ff;

            color: #2563eb;

            border-color: #bfdbfe;

            text-decoration: none;
        }


        .quick-btn i {
            color: #2563eb;
        }


        /* =========================
           RECENT BOOKS
        ========================= */

        .recent-card {
            background: white;

            border-radius: 12px;

            padding: 25px;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);
        }


        .recent-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .recent-header h5 {
            margin: 0;

            font-size: 17px;

            font-weight: 700;
        }


        .view-all {
            color: #2563eb;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;
        }


        .view-all:hover {
            color: #1d4ed8;

            text-decoration: none;
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


        .book-name {
            font-weight: 700;

            color: #111827;
        }


        .pdf-yes {
            color: #15803d;

            font-weight: 600;
        }


        .pdf-no {
            color: #9ca3af;
        }


        .price {
            color: #2563eb;

            font-weight: 700;
        }


        /* =========================
           EMPTY STATE
        ========================= */

        .empty {
            text-align: center;

            padding: 50px 20px;

            color: #9ca3af;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 768px) {

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


            .topbar {
                align-items: flex-start;

                gap: 15px;
            }


            .user-name {
                display: none;
            }


            .back-home {
                margin-right: 0;

                padding: 8px 11px;

                font-size: 12px;
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

        Library User

    </div>


    <div class="menu-title">

        Main Menu

    </div>


    <a
        href="dashboard.php"
        class="nav-link active"
    >

        <i class="fa-solid fa-chart-line"></i>

        Dashboard

    </a>


    <a
        href="books.php"
        class="nav-link"
    >

        <i class="fa-solid fa-book"></i>

        Browse Books

    </a>


    <a
        href="orders.php"
        class="nav-link"
    >

        <i class="fa-solid fa-cart-shopping"></i>

        My Orders

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

        My Profile

    </a>


    <div class="logout">

        <a
            href="../logout.php"
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
                User Dashboard
            </h2>

            <p>
                Welcome to your Library Management System.
            </p>

        </div>


        <div class="user-info">


            <!-- BACK TO HOME -->

            <a
                href="../index.php"
                class="back-home"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Home

            </a>


            <div class="user-name">

                <div>
                    <?php
                    echo htmlspecialchars(
                        $user["name"]
                    );
                    ?>
                </div>

                <div class="user-email">

                    <?php
                    echo htmlspecialchars(
                        $user["email"]
                    );
                    ?>

                </div>

            </div>


            <div class="user-avatar">


                <?php if (
                    !empty(
                        $user["profile_image"]
                    )
                ): ?>


                    <img
                        src="uploads/<?php
                        echo htmlspecialchars(
                            $user["profile_image"]
                        );
                        ?>"
                        alt="Profile"
                    >


                <?php else: ?>


                    <i class="fa-solid fa-user"></i>


                <?php endif; ?>


            </div>


        </div>


    </div>


    <!-- WELCOME -->

    <div class="welcome-card">


        <h4>

            Welcome,
            <?php
            echo htmlspecialchars(
                $user["name"]
            );
            ?>!

        </h4>


        <p>

            Browse approved books,
            manage your orders and check
            your issued books.

        </p>


    </div>


    <!-- =========================
         STAT CARDS
    ========================= -->

    <div class="row">


        <!-- TOTAL BOOKS -->

        <div class="col-lg-3 col-md-6">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-book"></i>

                </div>


                <h3>

                    <?php
                    echo $total_books;
                    ?>

                </h3>


                <p>
                    Available Books
                </p>


            </div>

        </div>


        <!-- ISSUED -->

        <div class="col-lg-3 col-md-6">

            <div class="stat-card">


                <div class="stat-icon">

                    <i
                        class="fa-solid fa-book-open-reader"
                    ></i>

                </div>


                <h3>

                    <?php
                    echo $active_issued;
                    ?>

                </h3>


                <p>
                    Active Issued Books
                </p>


            </div>

        </div>


        <!-- RETURNED -->

        <div class="col-lg-3 col-md-6">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-rotate-left"></i>

                </div>


                <h3>

                    <?php
                    echo $returned_books;
                    ?>

                </h3>


                <p>
                    Returned Books
                </p>


            </div>

        </div>


        <!-- ORDERS -->

        <div class="col-lg-3 col-md-6">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-cart-shopping"></i>

                </div>


                <h3>

                    <?php
                    echo $total_orders;
                    ?>

                </h3>


                <p>
                    My Orders
                </p>


            </div>

        </div>


    </div>


    <!-- =========================
         QUICK ACTIONS
    ========================= -->

    <div class="quick-card">


        <div class="section-title">

            Quick Actions

        </div>


        <div class="quick-actions">


            <a
                href="books.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-book"></i>

                Browse Books

            </a>


            <a
                href="orders.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-cart-shopping"></i>

                My Orders

            </a>


            <a
                href="issued_books.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-book-open-reader"></i>

                Issued Books

            </a>


            <a
                href="profile.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-user"></i>

                My Profile

            </a>


        </div>


    </div>


    <!-- =========================
         RECENT BOOKS
    ========================= -->

    <div class="recent-card">


        <div class="recent-header">


            <h5>
                Recently Added Books
            </h5>


            <a
                href="books.php"
                class="view-all"
            >

                View All

                <i
                    class="fa-solid fa-arrow-right"
                ></i>

            </a>


        </div>


        <?php if (
            $recent_result &&
            mysqli_num_rows(
                $recent_result
            ) > 0
        ): ?>


            <div class="table-responsive">


                <table class="table">


                    <thead>

                        <tr>

                            <th>
                                Book
                            </th>

                            <th>
                                Author
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Digital PDF
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $book =
                        mysqli_fetch_assoc(
                            $recent_result
                        )
                    ): ?>


                        <tr>


                            <td>

                                <div class="book-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $book["book_name"]
                                    );
                                    ?>

                                </div>


                                <small class="text-muted">

                                    Book No:
                                    <?php
                                    echo $book["book_no"];
                                    ?>

                                </small>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $book["author_name"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $book["cat_name"]
                                );
                                ?>

                            </td>


                            <td>

                                <span class="price">

                                    ৳<?php
                                    echo number_format(
                                        $book["book_price"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>


                                <?php if (
                                    !empty(
                                        $book["book_file"]
                                    )
                                ): ?>


                                    <span class="pdf-yes">

                                        <i
                                            class="fa-solid fa-file-pdf"
                                        ></i>

                                        Available

                                    </span>


                                <?php else: ?>


                                    <span class="pdf-no">

                                        Not Available

                                    </span>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div class="empty">


                <i
                    class="fa-solid fa-book-open fa-2x mb-3"
                ></i>


                <p>
                    No approved books available yet.
                </p>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
```
