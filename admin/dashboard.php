```php
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


/* =========================
   ADMIN INFO
========================= */

$admin_query = "
    SELECT
        name,
        email,
        profile_image
    FROM admins
    WHERE id = $admin_id
    LIMIT 1
";

$admin_result = mysqli_query($conn, $admin_query);

if (!$admin_result || mysqli_num_rows($admin_result) == 0) {
    session_destroy();
    header("Location: indexad.php");
    exit();
}

$admin = mysqli_fetch_assoc($admin_result);


/* =========================
   TOTAL USERS
========================= */

$user_query = "
    SELECT COUNT(*) AS total
    FROM users
";

$user_result = mysqli_query($conn, $user_query);
$total_users = mysqli_fetch_assoc($user_result)["total"];


/* =========================
   TOTAL BOOKS
========================= */

$book_query = "
    SELECT COUNT(*) AS total
    FROM books
";

$book_result = mysqli_query($conn, $book_query);
$total_books = mysqli_fetch_assoc($book_result)["total"];


/* =========================
   TOTAL AUTHORS
========================= */

$author_query = "
    SELECT COUNT(*) AS total
    FROM authors
";

$author_result = mysqli_query($conn, $author_query);
$total_authors = mysqli_fetch_assoc($author_result)["total"];


/* =========================
   TOTAL CATEGORIES
========================= */

$category_query = "
    SELECT COUNT(*) AS total
    FROM category
";

$category_result = mysqli_query($conn, $category_query);
$total_categories = mysqli_fetch_assoc($category_result)["total"];


/* =========================
   ACTIVE ISSUED BOOKS
========================= */

$issued_query = "
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE status = 1
";

$issued_result = mysqli_query($conn, $issued_query);
$active_issued = mysqli_fetch_assoc($issued_result)["total"];


/* =========================
   PENDING BOOK APPROVALS
========================= */

$pending_query = "
    SELECT COUNT(*) AS total
    FROM books
    WHERE added_by_type = 'Staff'
    AND approval_status = 'Pending'
";

$pending_result = mysqli_query($conn, $pending_query);
$pending_approvals = mysqli_fetch_assoc($pending_result)["total"];


/* =========================
   TOTAL STAFF
========================= */

$staff_query = "
    SELECT COUNT(*) AS total
    FROM staffs
";

$staff_result = mysqli_query($conn, $staff_query);
$total_staff = mysqli_fetch_assoc($staff_result)["total"];


/* =========================
   TOTAL ORDERS
========================= */

$order_query = "
    SELECT COUNT(*) AS total
    FROM orders
";

$order_result = mysqli_query($conn, $order_query);
$total_orders = mysqli_fetch_assoc($order_result)["total"];


/* =========================
   PENDING ORDERS
========================= */

$pending_order_query = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status = 'Pending'
";

$pending_order_result = mysqli_query($conn, $pending_order_query);
$pending_orders = mysqli_fetch_assoc($pending_order_result)["total"];


/* =========================
   RECENT BOOKS
========================= */

$recent_books_query = "
    SELECT
        books.book_id,
        books.book_name,
        books.book_no,
        books.book_price,
        books.book_file,
        books.approval_status,
        authors.author_name,
        category.cat_name

    FROM books

    LEFT JOIN authors
        ON books.author_id = authors.author_id

    LEFT JOIN category
        ON books.cat_id = category.cat_id

    ORDER BY books.book_id DESC

    LIMIT 8
";

$recent_books_result = mysqli_query($conn, $recent_books_query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Admin Dashboard - Library Management System
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
            font-family: Arial, sans-serif;
            background: #f4f6f9;
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

            border-bottom: 1px solid #374151;

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


        .nav-link .badge {
            float: right;

            font-size: 10px;

            margin-top: 2px;
        }


        .pending-badge {
            float: right;

            background: #f59e0b;

            color: white;

            font-size: 10px;

            padding: 3px 7px;

            border-radius: 10px;
        }


        .logout {
            margin-top: 18px;

            padding-top: 12px;

            border-top: 1px solid #374151;
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

            color: #4b5563;

            font-weight: 600;
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
           WELCOME
        ========================= */

        .welcome-card {
            background: white;

            border-radius: 12px;

            padding: 25px;

            margin-bottom: 25px;

            border-left: 4px solid #2563eb;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);
        }


        .welcome-card h4 {
            margin: 0 0 7px;

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
            text-decoration: none;

            color: #1d4ed8;
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


        .status {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 700;
        }


        .status-approved {
            background: #ecfdf5;

            color: #15803d;
        }


        .status-pending {
            background: #fff7ed;

            color: #c2410c;
        }


        .status-rejected {
            background: #fef2f2;

            color: #dc2626;
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


            .main {
                margin-left: 0;

                padding: 20px;
            }


            .topbar {
                align-items: flex-start;

                gap: 15px;
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

        Library Admin

    </div>


    <div class="menu-title">

        Main Menu

    </div>


    <!-- Dashboard -->

    <a
        href="dashboard.php"
        class="nav-link active"
    >

        <i class="fa-solid fa-chart-line"></i>

        Dashboard

    </a>


    <!-- Books -->

    <a
        href="manage_books.php"
        class="nav-link"
    >

        <i class="fa-solid fa-book"></i>

        Manage Books

    </a>


    <!-- Book Approvals -->

    <a
        href="book_approvals.php"
        class="nav-link"
    >

        <i class="fa-solid fa-circle-check"></i>

        Book Approvals


        <?php if ($pending_approvals > 0): ?>

            <span class="badge badge-warning">

                <?php
                echo $pending_approvals;
                ?>

            </span>

        <?php endif; ?>


    </a>


    <!-- Users -->

    <a
        href="manage_users.php"
        class="nav-link"
    >

        <i class="fa-solid fa-users"></i>

        Manage Users

    </a>


    <!-- Staff -->

    <a
        href="staffs.php"
        class="nav-link"
    >

        <i class="fa-solid fa-user-tie"></i>

        Staff Management

    </a>


    <!-- Orders -->

    <a
        href="orders.php"
        class="nav-link"
    >

        <i class="fa-solid fa-cart-shopping"></i>

        Orders


        <?php if ($pending_orders > 0): ?>

            <span class="pending-badge">

                <?php
                echo $pending_orders;
                ?>

            </span>

        <?php endif; ?>


    </a>


    <!-- Authors -->

    <a
        href="authors.php"
        class="nav-link"
    >

        <i class="fa-solid fa-pen-nib"></i>

        Authors

    </a>


    <!-- Categories -->

    <a
        href="categories.php"
        class="nav-link"
    >

        <i class="fa-solid fa-layer-group"></i>

        Categories

    </a>


    <!-- Issued Books -->

    <a
        href="issued_books.php"
        class="nav-link"
    >

        <i class="fa-solid fa-book-open-reader"></i>

        Issued Books

    </a>


    <!-- Profile -->

    <a
        href="profile.php"
        class="nav-link"
    >

        <i class="fa-solid fa-user"></i>

        Profile

    </a>


    <!-- Logout -->

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
     MAIN CONTENT
========================= -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">


        <div class="page-title">

            <h2>
                Admin Dashboard
            </h2>

            <p>
                Manage your library system from one place.
            </p>

        </div>


        <div class="admin-info">


            <!-- BACK TO HOME -->

            <a
                href="../index.php"
                class="back-home"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Home

            </a>


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
                        alt="Admin Profile"
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
                $admin["name"]
            );
            ?>!

        </h4>


        <p>

            Here is an overview of your
            Library Management System.

        </p>


    </div>


    <!-- =========================
         STAT CARDS
    ========================= -->

    <div class="row">


        <!-- USERS -->

        <div class="col-lg-3 col-md-6">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-users"></i>

                </div>


                <h3>

                    <?php
                    echo $total_users;
                    ?>

                </h3>


                <p>
                    Total Users
                </p>


            </div>

        </div>


        <!-- BOOKS -->

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
                    Total Books
                </p>


            </div>

        </div>


        <!-- STAFF -->

        <div class="col-lg-3 col-md-6">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-user-tie"></i>

                </div>


                <h3>

                    <?php
                    echo $total_staff;
                    ?>

                </h3>


                <p>
                    Total Staff
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
                    Total Orders
                </p>


            </div>

        </div>


        <!-- AUTHORS -->

        <div class="col-lg-3 col-md-6">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-pen-nib"></i>

                </div>


                <h3>

                    <?php
                    echo $total_authors;
                    ?>

                </h3>


                <p>
                    Total Authors
                </p>


            </div>

        </div>


        <!-- CATEGORIES -->

        <div class="col-lg-3 col-md-6">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-layer-group"></i>

                </div>


                <h3>

                    <?php
                    echo $total_categories;
                    ?>

                </h3>


                <p>
                    Categories
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


        <!-- PENDING APPROVALS -->

        <div class="col-lg-3 col-md-6">

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <h3>

                    <?php
                    echo $pending_approvals;
                    ?>

                </h3>


                <p>
                    Pending Approvals
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
                href="add_book.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-plus"></i>

                Add New Book

            </a>


            <a
                href="book_approvals.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-circle-check"></i>

                Review Book Approvals

            </a>


            <a
                href="staffs.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-user-tie"></i>

                Staff Management

            </a>


            <a
                href="orders.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-cart-shopping"></i>

                Customer Orders

            </a>


            <a
                href="manage_users.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-users"></i>

                Manage Users

            </a>


            <a
                href="authors.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-pen-nib"></i>

                Manage Authors

            </a>


            <a
                href="categories.php"
                class="quick-btn"
            >

                <i class="fa-solid fa-layer-group"></i>

                Manage Categories

            </a>


            <a
                href="issued_books.php"
                class="quick-btn"
            >

                <i
                    class="fa-solid fa-book-open-reader"
                ></i>

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

                Recent Books

            </h5>


            <a
                href="manage_books.php"
                class="view-all"
            >

                View All

                <i class="fa-solid fa-arrow-right"></i>

            </a>


        </div>


        <?php if (
            $recent_books_result &&
            mysqli_num_rows(
                $recent_books_result
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
                                PDF
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $book =
                        mysqli_fetch_assoc(
                            $recent_books_result
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

                                <strong>

                                    ৳<?php
                                    echo number_format(
                                        $book["book_price"]
                                    );
                                    ?>

                                </strong>

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


                            <td>


                                <?php if (
                                    $book["approval_status"]
                                    == "Pending"
                                ): ?>


                                    <span
                                        class="status status-pending"
                                    >

                                        Pending

                                    </span>


                                <?php elseif (
                                    $book["approval_status"]
                                    == "Rejected"
                                ): ?>


                                    <span
                                        class="status status-rejected"
                                    >

                                        Rejected

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="status status-approved"
                                    >

                                        Approved

                                    </span>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div
                class="text-center text-muted py-5"
            >

                <i
                    class="fa-solid fa-book-open fa-2x mb-3"
                ></i>


                <p>
                    No books found.
                </p>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
```
