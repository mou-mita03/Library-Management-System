<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: indexad.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "lms");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


/* ================= RETURN BOOK ================= */

if (isset($_GET["return"])) {

    $s_no = (int) $_GET["return"];

    mysqli_query(
        $conn,
        "UPDATE issued_books
         SET status = 0
         WHERE s_no = $s_no"
    );

    header("Location: issued_books.php?returned=1");
    exit();
}


/* ================= ADMIN PROFILE ================= */

$admin_id = (int) $_SESSION["admin_id"];

$admin_query = mysqli_query(
    $conn,
    "SELECT name, email, profile_image
     FROM admins
     WHERE id = $admin_id"
);

$admin = mysqli_fetch_assoc($admin_query);


/* ================= ISSUED BOOKS ================= */

$issued_query = mysqli_query(
    $conn,
    "SELECT
        issued_books.s_no,
        issued_books.book_no,
        issued_books.book_name,
        issued_books.book_author,
        issued_books.student_id,
        issued_books.status,
        issued_books.issue_date,
        users.name AS student_name,
        users.email AS student_email
     FROM issued_books
     LEFT JOIN users
        ON issued_books.student_id = users.id
     ORDER BY issued_books.s_no DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Issued Books - Library Management System</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }


        /* ================= SIDEBAR ================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #111827;
            color: white;
            padding: 25px 15px;
            overflow-y: auto;
        }

        .brand {
            font-size: 23px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
        }

        .brand i {
            margin-right: 8px;
        }


        /* Admin Profile */

        .admin-profile {
            text-align: center;
            padding: 15px 10px 20px;
            border-bottom: 1px solid #374151;
            margin-bottom: 20px;
        }

        .admin-profile img,
        .default-profile {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #374151;
        }

        .default-profile {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #374151;
            font-size: 30px;
            color: #d1d5db;
        }

        .admin-profile h6 {
            margin-top: 10px;
            margin-bottom: 3px;
            font-size: 16px;
        }

        .admin-profile small {
            color: #9ca3af;
            word-break: break-word;
        }


        /* Sidebar Menu */

        .menu-title {
            color: #9ca3af;
            font-size: 12px;
            text-transform: uppercase;
            margin: 20px 10px 8px;
            font-weight: bold;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #d1d5db;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563eb;
            color: white;
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
        }


        /* ================= MAIN ================= */

        .main {
            margin-left: 250px;
            min-height: 100vh;
            padding: 25px;
        }


        /* Topbar */

        .topbar {
            background: white;
            padding: 16px 22px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }

        .page-title h2 {
            margin: 0;
            font-size: 25px;
            font-weight: 700;
        }

        .page-title p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 14px;
        }


        /* Top Admin */

        .top-admin {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-admin img,
        .top-default-profile {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }

        .top-default-profile {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e5e7eb;
            color: #4b5563;
        }

        .top-admin strong {
            font-size: 14px;
        }


        /* ================= CONTENT ================= */

        .content-card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }


        .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header-custom h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }


        /* Issue Button */

        .issue-btn {
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 10px 17px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .issue-btn:hover {
            background: #1d4ed8;
            color: white;
        }


        /* Table */

        .table-responsive {
            border-radius: 10px;
        }

        table {
            vertical-align: middle !important;
        }

        thead th {
            background: #f3f4f6 !important;
            color: #374151;
            font-size: 13px;
            white-space: nowrap;
        }

        tbody td {
            font-size: 14px;
        }

        .book-name {
            font-weight: 600;
            color: #111827;
        }

        .student-name {
            font-weight: 600;
        }

        .student-email {
            color: #6b7280;
            font-size: 13px;
        }


        /* Status */

        .status-badge {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .issued {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .returned {
            background: #dcfce7;
            color: #166534;
        }


        /* Return Button */

        .return-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #166534;
            padding: 7px 11px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .return-btn:hover {
            background: #bbf7d0;
            color: #14532d;
        }


        .returned-label {
            color: #6b7280;
            font-size: 12px;
        }


        /* Empty State */

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #9ca3af;
        }


        /* Mobile */

        @media (max-width: 900px) {

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
            }

        }


        @media (max-width: 700px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .card-header-custom {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

        }

    </style>

</head>


<body>


<!-- ================= SIDEBAR ================= -->

<div class="sidebar">


    <div class="brand">

        <i class="fa-solid fa-book-open"></i>

        Library Admin

    </div>


    <!-- Admin Profile -->

    <div class="admin-profile">

        <?php if (!empty($admin["profile_image"])): ?>

            <img
                src="uploads/<?php echo htmlspecialchars($admin["profile_image"]); ?>"
                alt="Admin Profile">

        <?php else: ?>

            <div class="default-profile">

                <i class="fa-solid fa-user"></i>

            </div>

        <?php endif; ?>


        <h6>

            <?php
            echo htmlspecialchars($admin["name"]);
            ?>

        </h6>


        <small>

            <?php
            echo htmlspecialchars($admin["email"]);
            ?>

        </small>

    </div>


    <!-- Menu -->

    <div class="menu-title">
        Main Menu
    </div>


    <a href="dashboard.php">

        <i class="fa-solid fa-gauge"></i>

        Dashboard

    </a>


    <a href="manage_books.php">

        <i class="fa-solid fa-book"></i>

        Manage Books

    </a>


    <a href="manage_users.php">

        <i class="fa-solid fa-users"></i>

        Manage Users

    </a>


    <a href="authors.php">

        <i class="fa-solid fa-user-pen"></i>

        Authors

    </a>


    <a href="categories.php">

        <i class="fa-solid fa-layer-group"></i>

        Categories

    </a>


    <a href="issued_books.php" class="active">

        <i class="fa-solid fa-book-open-reader"></i>

        Issued Books

    </a>


    <div class="menu-title">
        Account
    </div>


    <a href="profile.php">

        <i class="fa-solid fa-user-gear"></i>

        My Profile

    </a>


    <a href="admin_logout.php">

        <i class="fa-solid fa-right-from-bracket"></i>

        Logout

    </a>

</div>


<!-- ================= MAIN ================= -->

<div class="main">


    <!-- Topbar -->

    <div class="topbar">


        <div class="page-title">

            <h2>
                Issued Books
            </h2>

            <p>
                Track issued and returned library books
            </p>

        </div>


        <div class="top-admin">


            <?php if (!empty($admin["profile_image"])): ?>

                <img
                    src="uploads/<?php echo htmlspecialchars($admin["profile_image"]); ?>"
                    alt="Admin">

            <?php else: ?>

                <div class="top-default-profile">

                    <i class="fa-solid fa-user"></i>

                </div>

            <?php endif; ?>


            <strong>

                <?php
                echo htmlspecialchars($admin["name"]);
                ?>

            </strong>

        </div>

    </div>


    <!-- ================= ALERTS ================= -->

    <?php if (isset($_GET["issued"])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="fa-solid fa-circle-check"></i>

            Book issued successfully.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET["returned"])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="fa-solid fa-circle-check"></i>

            Book returned successfully.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- ================= ISSUED BOOKS ================= -->

    <div class="content-card">


        <div class="card-header-custom">


            <h4>

                <i class="fa-solid fa-book-open-reader me-2"></i>

                Book Issue Records

            </h4>


            <a
                href="issue_book.php"
                class="issue-btn">

                <i class="fa-solid fa-plus"></i>

                Issue New Book

            </a>

        </div>


        <?php if ($issued_query && mysqli_num_rows($issued_query) > 0): ?>


            <div class="table-responsive">


                <table class="table table-hover">


                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Book</th>

                            <th>Book No.</th>

                            <th>Student</th>

                            <th>Issue Date</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    $serial = 1;

                    while ($issue = mysqli_fetch_assoc($issued_query)):

                    ?>


                        <tr>


                            <td>

                                <?php
                                echo $serial++;
                                ?>

                            </td>


                            <td>

                                <div class="book-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $issue["book_name"]
                                    );
                                    ?>

                                </div>


                                <small class="text-muted">

                                    <?php
                                    echo htmlspecialchars(
                                        $issue["book_author"]
                                    );
                                    ?>

                                </small>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $issue["book_no"]
                                );
                                ?>

                            </td>


                            <td>

                                <div class="student-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $issue["student_name"] ?? "Unknown"
                                    );
                                    ?>

                                </div>


                                <div class="student-email">

                                    <?php
                                    echo htmlspecialchars(
                                        $issue["student_email"] ?? ""
                                    );
                                    ?>

                                </div>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime($issue["issue_date"])
                                );

                                ?>

                            </td>


                            <td>


                                <?php if ($issue["status"] == 1): ?>

                                    <span class="status-badge issued">

                                        <i class="fa-solid fa-book"></i>

                                        Issued

                                    </span>


                                <?php else: ?>

                                    <span class="status-badge returned">

                                        <i class="fa-solid fa-circle-check"></i>

                                        Returned

                                    </span>

                                <?php endif; ?>


                            </td>


                            <td>


                                <?php if ($issue["status"] == 1): ?>


                                    <a
                                        href="issued_books.php?return=<?php echo $issue["s_no"]; ?>"
                                        class="return-btn"
                                        onclick="return confirm('Mark this book as returned?');">

                                        <i class="fa-solid fa-rotate-left"></i>

                                        Return

                                    </a>


                                <?php else: ?>


                                    <span class="returned-label">

                                        Already Returned

                                    </span>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-state">


                <i class="fa-solid fa-book-open-reader"></i>


                <h5>
                    No Issue Records Found
                </h5>


                <p>
                    No books have been issued yet.
                </p>


                <a
                    href="issue_book.php"
                    class="issue-btn">

                    <i class="fa-solid fa-plus"></i>

                    Issue First Book

                </a>


            </div>


        <?php endif; ?>


    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>