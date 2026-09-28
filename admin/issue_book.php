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

/* =========================
   ADMIN PROFILE
========================= */

$admin_id = $_SESSION["admin_id"];

$admin_query = mysqli_query(
    $conn,
    "SELECT name, email, profile_image FROM admins WHERE id = '$admin_id'"
);

$admin = mysqli_fetch_assoc($admin_query);

$admin_name = $admin["name"];
$admin_email = $admin["email"];
$profile_image = $admin["profile_image"];


/* =========================
   ISSUE BOOK
========================= */

if (isset($_POST["issue_book"])) {

    $book_id = intval($_POST["book_id"]);
    $student_id = intval($_POST["student_id"]);

    /* Get selected book */

    $book_query = mysqli_query(
        $conn,
        "SELECT 
            books.book_id,
            books.book_name,
            books.book_no,
            authors.author_name
         FROM books
         LEFT JOIN authors
         ON books.author_id = authors.author_id
         WHERE books.book_id = '$book_id'"
    );

    $book = mysqli_fetch_assoc($book_query);

    /* Get selected student */

    $student_query = mysqli_query(
        $conn,
        "SELECT id, name
         FROM users
         WHERE id = '$student_id'"
    );

    $student = mysqli_fetch_assoc($student_query);

    if ($book && $student) {

        $book_no = $book["book_no"];
        $book_name = $book["book_name"];
        $book_author = $book["author_name"];

        /* Check whether book is already issued */

        $check_query = mysqli_query(
            $conn,
            "SELECT s_no
             FROM issued_books
             WHERE book_no = '$book_no'
             AND status = 1"
        );

        if (mysqli_num_rows($check_query) > 0) {

            header("Location: issue_book.php?already=1");
            exit();

        } else {

            /* Issue book */

            $insert_query = mysqli_query(
                $conn,
                "INSERT INTO issued_books
                (book_no, book_name, book_author, student_id, status, issue_date)
                VALUES
                (
                    '$book_no',
                    '$book_name',
                    '$book_author',
                    '$student_id',
                    1,
                    NOW()
                )"
            );

            if ($insert_query) {

                header("Location: issued_books.php?issued=1");
                exit();

            } else {

                header("Location: issue_book.php?error=1");
                exit();
            }
        }
    }
}


/* =========================
   FETCH BOOKS
========================= */

$books_query = mysqli_query(
    $conn,
    "SELECT
        books.book_id,
        books.book_name,
        books.book_no,
        authors.author_name
     FROM books
     LEFT JOIN authors
     ON books.author_id = authors.author_id
     ORDER BY books.book_name ASC"
);


/* =========================
   FETCH STUDENTS
========================= */

$students_query = mysqli_query(
    $conn,
    "SELECT id, name, email
     FROM users
     ORDER BY name ASC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Issue Book | Library Management System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css"
        rel="stylesheet"
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
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: #111827;
            color: white;
            padding: 22px 15px;
            overflow-y: auto;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 10px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 18px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: #ffffff;
            color: #111827;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .brand h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .brand span {
            display: block;
            font-size: 11px;
            color: #9ca3af;
            margin-top: 2px;
        }

        .menu-title {
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 12px 12px 8px;
            letter-spacing: 1px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #d1d5db;
            padding: 11px 13px;
            margin: 4px 0;
            border-radius: 9px;
            font-size: 14px;
            transition: 0.2s;
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
        }

        .sidebar a:hover {
            background: #1f2937;
            color: white;
        }

        .sidebar a.active {
            background: white;
            color: #111827;
            font-weight: 600;
        }

        .logout-link {
            color: #fca5a5 !important;
        }

        .logout-link:hover {
            background: #3f1d1d !important;
            color: #fecaca !important;
        }

        /* =========================
           ADMIN PROFILE
        ========================= */

        .admin-box {
            margin-top: 20px;
            padding: 15px 10px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-photo {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .admin-default {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #374151;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d1d5db;
        }

        .admin-info {
            min-width: 0;
        }

        .admin-info strong {
            display: block;
            font-size: 13px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-info span {
            display: block;
            font-size: 11px;
            color: #9ca3af;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 260px;
            min-height: 100vh;
        }

        .topbar {
            height: 75px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
        }

        .topbar h5 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .topbar small {
            color: #6b7280;
        }

        .top-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-profile img,
        .top-default {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }

        .top-default {
            background: #111827;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            padding: 32px;
        }

        /* =========================
           PAGE HEADER
        ========================= */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 27px;
            font-weight: 700;
            color: #111827;
        }

        .page-header p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .back-btn {
            background: #111827;
            color: white;
            text-decoration: none;
            padding: 11px 18px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
        }

        .back-btn:hover {
            background: #000000;
            color: white;
        }

        /* =========================
           FORM CARD
        ========================= */

        .form-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 30px;
            max-width: 850px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
        }

        .form-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .form-title-icon {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #111827;
            font-size: 20px;
        }

        .form-title h4 {
            margin: 0;
            font-size: 19px;
            font-weight: 700;
        }

        .form-title p {
            margin: 3px 0 0;
            color: #6b7280;
            font-size: 13px;
        }

        .form-label {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            color: #374151;
        }

        .form-select {
            height: 48px;
            border-radius: 9px;
            border: 1px solid #d1d5db;
            padding-left: 14px;
        }

        .form-select:focus {
            border-color: #111827;
            box-shadow: 0 0 0 3px rgba(17,24,39,0.08);
        }

        .issue-btn {
            background: #111827;
            border: none;
            color: white;
            height: 48px;
            padding: 0 25px;
            border-radius: 9px;
            font-weight: 600;
        }

        .issue-btn:hover {
            background: #000000;
        }

        /* =========================
           ALERT
        ========================= */

        .custom-alert {
            border-radius: 10px;
            padding: 13px 16px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* =========================
           INFO BOX
        ========================= */

        .info-box {
            margin-top: 25px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 18px;
            color: #6b7280;
            font-size: 13px;
        }

        .info-box i {
            color: #111827;
            margin-right: 7px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
            }

            .content {
                padding: 22px;
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
            }

            .topbar {
                padding: 0 18px;
            }

            .content {
                padding: 18px;
            }

            .page-header {
                align-items: flex-start;
                gap: 15px;
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

        <div class="brand-icon">
            <i class="fa-solid fa-book-open"></i>
        </div>

        <div>
            <h4>Library Admin</h4>
            <span>Management System</span>
        </div>

    </div>


    <div class="menu-title">
        Main Menu
    </div>


    <a href="dashboard.php">
        <i class="fa-solid fa-chart-pie"></i>
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
        <i class="fa-solid fa-pen-nib"></i>
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
        <i class="fa-solid fa-user"></i>
        My Profile
    </a>


    <a href="admin_logout.php" class="logout-link">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>


    <div class="admin-box">

        <?php if (!empty($profile_image) && file_exists("uploads/" . $profile_image)): ?>

            <img
                src="uploads/<?php echo htmlspecialchars($profile_image); ?>"
                class="admin-photo"
                alt="Admin"
            >

        <?php else: ?>

            <div class="admin-default">
                <i class="fa-solid fa-user"></i>
            </div>

        <?php endif; ?>


        <div class="admin-info">

            <strong>
                <?php echo htmlspecialchars($admin_name); ?>
            </strong>

            <span>
                <?php echo htmlspecialchars($admin_email); ?>
            </span>

        </div>

    </div>

</div>


<!-- =========================
     MAIN CONTENT
========================= -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h5>Issue Book</h5>

            <small>
                Issue a library book to a student
            </small>

        </div>


        <div class="top-profile">

            <span class="d-none d-md-block">
                <?php echo htmlspecialchars($admin_name); ?>
            </span>


            <?php if (!empty($profile_image) && file_exists("uploads/" . $profile_image)): ?>

                <img
                    src="uploads/<?php echo htmlspecialchars($profile_image); ?>"
                    alt="Admin"
                >

            <?php else: ?>

                <div class="top-default">
                    <i class="fa-solid fa-user"></i>
                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <h2>Issue New Book</h2>

                <p>
                    Select a book and assign it to a registered student.
                </p>

            </div>


            <a href="issued_books.php" class="back-btn">

                <i class="fa-solid fa-arrow-left"></i>

                Back to Issued Books

            </a>

        </div>


        <!-- ALERTS -->

        <?php if (isset($_GET["already"])): ?>

            <div class="alert alert-warning custom-alert">

                <i class="fa-solid fa-triangle-exclamation"></i>

                This book is already issued to another student.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["error"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-circle-exclamation"></i>

                Something went wrong while issuing the book.

            </div>

        <?php endif; ?>


        <!-- FORM CARD -->

        <div class="form-card">


            <div class="form-title">

                <div class="form-title-icon">

                    <i class="fa-solid fa-book-circle-plus"></i>

                </div>


                <div>

                    <h4>Book Issue Information</h4>

                    <p>
                        Choose the book and student carefully.
                    </p>

                </div>

            </div>


            <form method="POST">


                <!-- BOOK -->

                <div class="mb-4">

                    <label class="form-label">

                        Select Book

                    </label>


                    <select
                        name="book_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Select a Book --
                        </option>


                        <?php while ($book = mysqli_fetch_assoc($books_query)): ?>

                            <option
                                value="<?php echo $book["book_id"]; ?>"
                            >

                                <?php echo htmlspecialchars($book["book_name"]); ?>

                                -
                                Book No:
                                <?php echo htmlspecialchars($book["book_no"]); ?>

                                -
                                <?php echo htmlspecialchars($book["author_name"]); ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- STUDENT -->

                <div class="mb-4">

                    <label class="form-label">

                        Select Student

                    </label>


                    <select
                        name="student_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Select a Student --
                        </option>


                        <?php while ($student = mysqli_fetch_assoc($students_query)): ?>

                            <option
                                value="<?php echo $student["id"]; ?>"
                            >

                                <?php echo htmlspecialchars($student["name"]); ?>

                                -
                                <?php echo htmlspecialchars($student["email"]); ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    name="issue_book"
                    class="issue-btn"
                >

                    <i class="fa-solid fa-book-open-reader"></i>

                    Issue Book

                </button>


            </form>


            <div class="info-box">

                <i class="fa-solid fa-circle-info"></i>

                A book that is currently issued cannot be issued to another
                student until it is returned.

            </div>


        </div>


    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>