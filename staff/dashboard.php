```php
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

$staff_id = (int) $_SESSION["staff_id"];


/* =========================
   STAFF INFO
========================= */

$staff_query = "
    SELECT name, email, profile_image
    FROM staffs
    WHERE id = $staff_id
    LIMIT 1
";

$staff_result = mysqli_query($conn, $staff_query);

if (!$staff_result || mysqli_num_rows($staff_result) == 0) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$staff = mysqli_fetch_assoc($staff_result);

$staff_name = $staff["name"];


/* =========================
   TOTAL BOOKS
========================= */

$book_query = "
    SELECT COUNT(*) AS total
    FROM books
    WHERE added_by = $staff_id
    AND added_by_type = 'Staff'
";

$book_result = mysqli_query($conn, $book_query);
$total_books = mysqli_fetch_assoc($book_result)["total"];


/* =========================
   PENDING BOOKS
========================= */

$pending_query = "
    SELECT COUNT(*) AS total
    FROM books
    WHERE added_by = $staff_id
    AND added_by_type = 'Staff'
    AND approval_status = 'Pending'
";

$pending_result = mysqli_query($conn, $pending_query);
$pending_books = mysqli_fetch_assoc($pending_result)["total"];


/* =========================
   APPROVED BOOKS
========================= */

$approved_query = "
    SELECT COUNT(*) AS total
    FROM books
    WHERE added_by = $staff_id
    AND added_by_type = 'Staff'
    AND approval_status = 'Approved'
";

$approved_result = mysqli_query($conn, $approved_query);
$approved_books = mysqli_fetch_assoc($approved_result)["total"];


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
   RECENT BOOKS
========================= */

$recent_query = "
    SELECT
        books.book_name,
        books.book_price,
        books.approval_status,
        books.book_file,
        authors.author_name,
        category.cat_name

    FROM books

    LEFT JOIN authors
        ON books.author_id = authors.author_id

    LEFT JOIN category
        ON books.cat_id = category.cat_id

    WHERE books.added_by = $staff_id
    AND books.added_by_type = 'Staff'

    ORDER BY books.book_id DESC

    LIMIT 5
";

$recent_result = mysqli_query($conn, $recent_query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Dashboard - Library Management System</title>

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

        .topbar h2 {
            margin: 0;
            font-size: 25px;
            font-weight: 700;
        }

        .staff-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4b5563;
            font-weight: 600;
        }

        .staff-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .staff-avatar img {
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

        .welcome {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #2563eb;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
        }

        .welcome h4 {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .welcome p {
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
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
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
            font-size: 27px;
            font-weight: 700;
            margin: 0;
        }

        .stat-card p {
            color: #6b7280;
            margin: 4px 0 0;
            font-size: 14px;
        }


        /* =========================
           SECTION
        ========================= */

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h5 {
            font-weight: 700;
            margin: 0;
        }

        .btn-add {
            background: #2563eb;
            color: white;
            border-radius: 7px;
            padding: 9px 15px;
            font-size: 13px;
            text-decoration: none;
        }

        .btn-add:hover {
            background: #1d4ed8;
            color: white;
            text-decoration: none;
        }


        /* =========================
           TABLE
        ========================= */

        .table th {
            border-top: none;
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .table td {
            vertical-align: middle;
            font-size: 14px;
        }


        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .approved {
            background: #ecfdf5;
            color: #15803d;
        }

        .rejected {
            background: #fef2f2;
            color: #dc2626;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {
            text-align: center;
            padding: 35px;
            color: #9ca3af;
        }


        /* =========================
           MOBILE & RESPONSIVE DRAWER
        ========================= */

        .sidebar-toggle-btn {
            display: none;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.2s;
        }

        .sidebar-toggle-btn:hover {
            background: #1d4ed8;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(3px);
            z-index: 1040;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        @media (max-width: 991px) {

            .sidebar-toggle-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: -260px;
                width: 255px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
                box-shadow: 5px 0 25px rgba(0,0,0,0.25);
            }

            .sidebar.show {
                left: 0;
            }

            .main {
                margin-left: 0;
                padding: 20px 15px;
            }

            .topbar {
                flex-wrap: wrap;
                gap: 12px;
            }

            .topbar h2 {
                font-size: 22px;
            }

            .staff-info {
                width: 100%;
                justify-content: space-between;
            }

            .back-home {
                padding: 8px 12px;
                font-size: 12px;
                margin-right: 0;
            }

        }

        @media (max-width: 576px) {

            .stat-card {
                padding: 16px;
            }

            .stat-card h3 {
                font-size: 22px;
            }

            .welcome {
                padding: 18px;
            }

            .section-card {
                padding: 18px 12px;
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

    <div class="menu-title">
        Main Menu
    </div>


    <a href="dashboard.php" class="nav-link active">
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


    <a href="orders.php" class="nav-link">
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


<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" onclick="document.querySelector('.sidebar').classList.remove('show'); this.classList.remove('show');"></div>

<!-- MAIN -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="d-flex align-items-center">

            <button type="button" class="sidebar-toggle-btn mr-3" onclick="document.querySelector('.sidebar').classList.toggle('show'); document.querySelector('.sidebar-overlay').classList.toggle('show');">
                <i class="fa-solid fa-bars"></i>
            </button>

            <h2 class="mb-0">
                Staff Dashboard
            </h2>

        </div>


        <div class="staff-info">


            <!-- BACK TO HOME -->

            <a href="../index.php" class="back-home">

                <i class="fa-solid fa-arrow-left"></i>

                Back to Home

            </a>


            <span>
                <?php echo htmlspecialchars($staff_name); ?>
            </span>


            <div class="staff-avatar">

                <?php if (!empty($staff["profile_image"])): ?>

                    <img
                        src="uploads/<?php echo htmlspecialchars($staff["profile_image"]); ?>"
                        alt="Staff Profile"
                    >

                <?php else: ?>

                    <i class="fa-solid fa-user"></i>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- WELCOME -->

    <div class="welcome">

        <h4>
            Welcome, <?php echo htmlspecialchars($staff_name); ?>!
        </h4>

        <p>
            Manage books and customer orders from your staff panel.
        </p>

    </div>


    <!-- STAT CARDS -->

    <div class="row">


        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-book"></i>
                </div>

                <h3>
                    <?php echo $total_books; ?>
                </h3>

                <p>
                    My Added Books
                </p>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <h3>
                    <?php echo $pending_books; ?>
                </h3>

                <p>
                    Pending Approval
                </p>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <h3>
                    <?php echo $approved_books; ?>
                </h3>

                <p>
                    Approved Books
                </p>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>

                <h3>
                    <?php echo $total_orders; ?>
                </h3>

                <p>
                    Total Orders
                </p>

            </div>

        </div>

    </div>


    <!-- RECENT BOOKS -->

    <div class="section-card">

        <div class="section-header">

            <h5>
                Recently Added Books
            </h5>


            <a href="add_book.php" class="btn-add">

                <i class="fa-solid fa-plus"></i>
                Add New Book

            </a>

        </div>


        <?php if ($recent_result && mysqli_num_rows($recent_result) > 0): ?>


            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>

                            <th>Book</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>PDF</th>
                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php while ($book = mysqli_fetch_assoc($recent_result)): ?>

                        <tr>


                            <td>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $book["book_name"]
                                    );
                                    ?>
                                </strong>

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

                                ৳<?php
                                echo number_format(
                                    $book["book_price"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php if (!empty($book["book_file"])): ?>

                                    <span class="text-success">

                                        <i class="fa-solid fa-file-pdf"></i>
                                        Available

                                    </span>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Not Added
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php

                                $status =
                                    $book["approval_status"];

                                ?>


                                <?php if ($status == "Pending"): ?>

                                    <span class="status pending">
                                        Pending
                                    </span>


                                <?php elseif ($status == "Approved"): ?>

                                    <span class="status approved">
                                        Approved
                                    </span>


                                <?php else: ?>

                                    <span class="status rejected">
                                        Rejected
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

                <i class="fa-solid fa-book-open fa-2x mb-3"></i>

                <p>
                    You have not added any books yet.
                </p>

                <a href="add_book.php" class="btn-add">
                    Add Your First Book
                </a>

            </div>


        <?php endif; ?>

    </div>

</div>

</body>

</html>
```
