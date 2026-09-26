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
   QUICK ADD AUTHOR
========================= */

if (isset($_POST["quick_add_author"])) {

    header("Content-Type: application/json");

    $author_name =
        trim($_POST["author_name"] ?? "");


    if ($author_name === "") {

        echo json_encode([
            "success" => false,
            "message" => "Author name is required."
        ]);

        exit();

    }


    $author_name_safe =
        mysqli_real_escape_string(
            $conn,
            $author_name
        );


    $check_author = mysqli_query(
        $conn,
        "SELECT author_id, author_name
         FROM authors
         WHERE LOWER(author_name) = LOWER('$author_name_safe')
         LIMIT 1"
    );


    if (mysqli_num_rows($check_author) > 0) {

        $existing_author =
            mysqli_fetch_assoc($check_author);


        echo json_encode([
            "success" => true,
            "author_id" => $existing_author["author_id"],
            "author_name" => $existing_author["author_name"]
        ]);

        exit();

    }


    $insert_author = mysqli_query(
        $conn,
        "INSERT INTO authors
        (
            author_name
        )
        VALUES
        (
            '$author_name_safe'
        )"
    );


    if ($insert_author) {

        echo json_encode([
            "success" => true,
            "author_id" => mysqli_insert_id($conn),
            "author_name" => $author_name
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Failed to add author."
        ]);

    }

    exit();

}


/* =========================
   QUICK ADD CATEGORY
========================= */

if (isset($_POST["quick_add_category"])) {

    header("Content-Type: application/json");

    $category_name =
        trim($_POST["category_name"] ?? "");


    if ($category_name === "") {

        echo json_encode([
            "success" => false,
            "message" => "Category name is required."
        ]);

        exit();

    }


    $category_name_safe =
        mysqli_real_escape_string(
            $conn,
            $category_name
        );


    $check_category = mysqli_query(
        $conn,
        "SELECT cat_id, cat_name
         FROM category
         WHERE LOWER(cat_name) = LOWER('$category_name_safe')
         LIMIT 1"
    );


    if (mysqli_num_rows($check_category) > 0) {

        $existing_category =
            mysqli_fetch_assoc($check_category);


        echo json_encode([
            "success" => true,
            "cat_id" => $existing_category["cat_id"],
            "cat_name" => $existing_category["cat_name"]
        ]);

        exit();

    }


    $insert_category = mysqli_query(
        $conn,
        "INSERT INTO category
        (
            cat_name
        )
        VALUES
        (
            '$category_name_safe'
        )"
    );


    if ($insert_category) {

        echo json_encode([
            "success" => true,
            "cat_id" => mysqli_insert_id($conn),
            "cat_name" => $category_name
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Failed to add category."
        ]);

    }

    exit();

}


/* =========================
   ADMIN PROFILE
========================= */

$admin_id = $_SESSION["admin_id"];

$admin_query = mysqli_query(
    $conn,
    "SELECT name, email, profile_image
     FROM admins
     WHERE id = '$admin_id'"
);

$admin = mysqli_fetch_assoc($admin_query);

$admin_name = $admin["name"];
$admin_email = $admin["email"];
$profile_image = $admin["profile_image"];


/* =========================
   ADD BOOK
========================= */

if (isset($_POST["add_book"])) {

    $book_name = trim($_POST["book_name"]);
    $author_id = intval($_POST["author_id"]);
    $cat_id = intval($_POST["cat_id"]);
    $book_no = intval($_POST["book_no"]);
    $book_price = intval($_POST["book_price"]);
    $pdf_comment = trim($_POST["pdf_comment"] ?? "");

    $book_file = "";
    $book_cover = "";


    /* =========================
       PDF VALIDATION + UPLOAD
    ========================= */

    if (
        isset($_FILES["book_file"]) &&
        $_FILES["book_file"]["error"] === 0
    ) {

        $pdf_name = $_FILES["book_file"]["name"];
        $pdf_tmp = $_FILES["book_file"]["tmp_name"];
        $pdf_size = $_FILES["book_file"]["size"];

        $pdf_ext = strtolower(
            pathinfo($pdf_name, PATHINFO_EXTENSION)
        );


        /* Only PDF */

        if ($pdf_ext !== "pdf") {

            header("Location: add_book.php?filetype=1");
            exit();

        }


        /* Maximum 20 MB */

        if ($pdf_size > 20 * 1024 * 1024) {

            header("Location: add_book.php?filesize=1");
            exit();

        }


        /* Unique PDF file name */

        $book_file =
            time() . "_" .
            uniqid() . "_" .
            preg_replace(
                "/[^A-Za-z0-9._-]/",
                "_",
                basename($pdf_name)
            );


        $pdf_upload_path = "../books/" . $book_file;


        if (!move_uploaded_file($pdf_tmp, $pdf_upload_path)) {

            header("Location: add_book.php?upload=1");
            exit();

        }

    }


    /* =========================
       BOOK COVER UPLOAD
    ========================= */

    if (
        isset($_FILES["book_cover"]) &&
        $_FILES["book_cover"]["error"] === 0
    ) {

        $cover_name = $_FILES["book_cover"]["name"];
        $cover_tmp = $_FILES["book_cover"]["tmp_name"];
        $cover_size = $_FILES["book_cover"]["size"];

        $cover_ext = strtolower(
            pathinfo($cover_name, PATHINFO_EXTENSION)
        );


        /* Allowed image types */

        $allowed_extensions = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];


        if (!in_array($cover_ext, $allowed_extensions, true)) {

            if (
                !empty($book_file) &&
                file_exists("../books/" . $book_file)
            ) {
                unlink("../books/" . $book_file);
            }

            header("Location: add_book.php?covertype=1");
            exit();

        }


        /* Maximum 5 MB */

        if ($cover_size > 5 * 1024 * 1024) {

            if (
                !empty($book_file) &&
                file_exists("../books/" . $book_file)
            ) {
                unlink("../books/" . $book_file);
            }

            header("Location: add_book.php?coversize=1");
            exit();

        }


        /* Verify actual image */

        $image_info = @getimagesize($cover_tmp);

        if ($image_info === false) {

            if (
                !empty($book_file) &&
                file_exists("../books/" . $book_file)
            ) {
                unlink("../books/" . $book_file);
            }

            header("Location: add_book.php?coverinvalid=1");
            exit();

        }


        /* Unique cover file name */

        $book_cover =
            time() . "_" .
            uniqid() . "_cover." .
            $cover_ext;


        $cover_upload_path =
            "../images/books/" . $book_cover;


        if (!move_uploaded_file(
            $cover_tmp,
            $cover_upload_path
        )) {

            if (
                !empty($book_file) &&
                file_exists("../books/" . $book_file)
            ) {
                unlink("../books/" . $book_file);
            }

            header("Location: add_book.php?coverupload=1");
            exit();

        }

    }


    /* =========================
       INSERT BOOK
    ========================= */

    $book_name_safe =
        mysqli_real_escape_string(
            $conn,
            $book_name
        );

    $book_file_safe =
        !empty($book_file)
        ? mysqli_real_escape_string(
            $conn,
            $book_file
        )
        : "";

    $book_cover_safe =
        !empty($book_cover)
        ? mysqli_real_escape_string(
            $conn,
            $book_cover
        )
        : "";

    $pdf_comment_safe =
        !empty($pdf_comment)
        ? mysqli_real_escape_string(
            $conn,
            $pdf_comment
        )
        : "";


    $book_file_value =
        !empty($book_file)
        ? "'$book_file_safe'"
        : "NULL";


    $book_cover_value =
        !empty($book_cover)
        ? "'$book_cover_safe'"
        : "NULL";


    $pdf_comment_value =
        !empty($pdf_comment)
        ? "'$pdf_comment_safe'"
        : "NULL";


    $insert_query = mysqli_query(
        $conn,
        "INSERT INTO books
        (
            book_name,
            author_id,
            cat_id,
            book_no,
            book_price,
            book_file,
            pdf_comment,
            book_cover
        )
        VALUES
        (
            '$book_name_safe',
            '$author_id',
            '$cat_id',
            '$book_no',
            '$book_price',
            $book_file_value,
            $pdf_comment_value,
            $book_cover_value
        )"
    );


    if ($insert_query) {

        header("Location: manage_books.php?added=1");
        exit();

    } else {

        /* Delete uploaded PDF */

        if (
            !empty($book_file) &&
            file_exists("../books/" . $book_file)
        ) {

            unlink("../books/" . $book_file);

        }


        /* Delete uploaded cover */

        if (
            !empty($book_cover) &&
            file_exists("../images/books/" . $book_cover)
        ) {

            unlink("../images/books/" . $book_cover);

        }


        header("Location: add_book.php?error=1");
        exit();

    }
}


/* =========================
   FETCH AUTHORS
========================= */

$authors_query = mysqli_query(
    $conn,
    "SELECT author_id, author_name
     FROM authors
     ORDER BY author_name ASC"
);


/* =========================
   FETCH CATEGORIES
========================= */

$categories_query = mysqli_query(
    $conn,
    "SELECT cat_id, cat_name
     FROM category
     ORDER BY cat_name ASC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Book | Library Management System</title>


    <!-- Bootstrap 5.3.1 -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome 6.5.2 -->

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
            max-width: 950px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
        }


        .form-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
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


        .form-control,
        .form-select {
            height: 48px;
            border-radius: 9px;
            border: 1px solid #d1d5db;
        }


        .form-control:focus,
        .form-select:focus {
            border-color: #111827;
            box-shadow: 0 0 0 3px rgba(17,24,39,0.08);
        }


        /* =========================
           QUICK ADD
        ========================= */

        .quick-add-btn {
            height: 48px;
            border-radius: 9px;
            font-weight: 600;
            white-space: nowrap;
        }


        /* =========================
           COVER UPLOAD
        ========================= */

        .cover-box {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            background: #fafafa;
            transition: 0.2s;
        }


        .cover-box:hover {
            border-color: #111827;
            background: #f9fafb;
        }


        .cover-icon {
            font-size: 38px;
            color: #4f46e5;
            margin-bottom: 10px;
        }


        .cover-box h6 {
            font-weight: 700;
            margin-bottom: 5px;
        }


        .cover-box p {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 15px;
        }


        .cover-box input {
            max-width: 500px;
            margin: auto;
        }


        .image-preview {
            display: none;
            margin: 18px auto 0;
            width: 130px;
            height: 165px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }


        /* =========================
           PDF
        ========================= */

        .pdf-box {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            background: #fafafa;
            transition: 0.2s;
        }


        .pdf-box:hover {
            border-color: #111827;
            background: #f9fafb;
        }


        .pdf-icon {
            font-size: 38px;
            color: #dc2626;
            margin-bottom: 10px;
        }


        .pdf-box h6 {
            font-weight: 700;
            margin-bottom: 5px;
        }


        .pdf-box p {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 15px;
        }


        .pdf-box input {
            max-width: 500px;
            margin: auto;
        }


        /* =========================
           BUTTONS
        ========================= */

        .add-btn {
            background: #111827;
            border: none;
            color: white;
            height: 48px;
            padding: 0 26px;
            border-radius: 9px;
            font-weight: 600;
        }


        .add-btn:hover {
            background: #000000;
        }


        .cancel-btn {
            height: 48px;
            padding: 0 22px;
            border-radius: 9px;
            font-weight: 600;
            border: 1px solid #d1d5db;
            color: #374151;
            background: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }


        .cancel-btn:hover {
            background: #f3f4f6;
            color: #111827;
        }


        /* =========================
           ALERT
        ========================= */

        .custom-alert {
            border-radius: 10px;
            padding: 13px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            max-width: 950px;
        }


        .info-box {
            margin-top: 25px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 17px;
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

            <span>
                Management System
            </span>

        </div>

    </div>


    <div class="menu-title">
        Main Menu
    </div>


    <a href="dashboard.php">

        <i class="fa-solid fa-chart-pie"></i>

        Dashboard

    </a>


    <a href="manage_books.php" class="active">

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


    <a href="issued_books.php">

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


    <a
        href="admin_logout.php"
        class="logout-link"
    >

        <i class="fa-solid fa-right-from-bracket"></i>

        Logout

    </a>


    <div class="admin-box">

        <?php if (
            !empty($profile_image) &&
            file_exists("uploads/" . $profile_image)
        ): ?>

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

                <?php
                echo htmlspecialchars($admin_name);
                ?>

            </strong>


            <span>

                <?php
                echo htmlspecialchars($admin_email);
                ?>

            </span>

        </div>

    </div>

</div>



<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h5>
                Add New Book
            </h5>

            <small>
                Add book information and upload its cover and PDF
            </small>

        </div>


        <div class="top-profile">

            <span class="d-none d-md-block">

                <?php
                echo htmlspecialchars($admin_name);
                ?>

            </span>


            <?php if (
                !empty($profile_image) &&
                file_exists("uploads/" . $profile_image)
            ): ?>

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

                <h2>
                    Add New Book
                </h2>

                <p>
                    Enter the book details and upload the cover and digital PDF.
                </p>

            </div>


            <a
                href="manage_books.php"
                class="back-btn"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Books

            </a>

        </div>



        <!-- ALERTS -->

        <?php if (isset($_GET["filetype"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-file-circle-xmark"></i>

                Only PDF files are allowed.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["filesize"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-file-circle-exclamation"></i>

                PDF file size must be 20 MB or less.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["upload"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-cloud-arrow-up"></i>

                Failed to upload the PDF file.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["covertype"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-image"></i>

                Only JPG, JPEG, PNG and WEBP image files are allowed for the book cover.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["coversize"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-image"></i>

                Book cover image size must be 5 MB or less.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["coverinvalid"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-triangle-exclamation"></i>

                The uploaded book cover is not a valid image.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["coverupload"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-cloud-arrow-up"></i>

                Failed to upload the book cover image.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["error"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-circle-exclamation"></i>

                Failed to add the book. Please try again.

            </div>

        <?php endif; ?>



        <!-- FORM -->

        <div class="form-card">


            <div class="form-title">

                <div class="form-title-icon">

                    <i class="fa-solid fa-book-medical"></i>

                </div>


                <div>

                    <h4>
                        Book Information
                    </h4>

                    <p>
                        Complete all required information below.
                    </p>

                </div>

            </div>



            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <div class="row">


                    <!-- BOOK NAME -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Book Name
                        </label>

                        <input
                            type="text"
                            name="book_name"
                            class="form-control"
                            placeholder="Enter book name"
                            required
                        >

                    </div>


                    <!-- BOOK NUMBER -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Book Number
                        </label>

                        <input
                            type="number"
                            name="book_no"
                            class="form-control"
                            placeholder="Enter book number"
                            required
                        >

                    </div>


                    <!-- AUTHOR -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Author
                        </label>


                        <div class="d-flex gap-2">

                            <select
                                name="author_id"
                                id="authorSelect"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    -- Select Author --
                                </option>


                                <?php while (
                                    $author =
                                    mysqli_fetch_assoc($authors_query)
                                ): ?>

                                    <option
                                        value="<?php echo $author["author_id"]; ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $author["author_name"]
                                        );
                                        ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>


                            <button
                                type="button"
                                class="btn btn-dark quick-add-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#authorModal"
                            >

                                <i class="fa-solid fa-plus"></i>

                                New Author

                            </button>

                        </div>

                    </div>


                    <!-- CATEGORY -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Category
                        </label>


                        <div class="d-flex gap-2">

                            <select
                                name="cat_id"
                                id="categorySelect"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    -- Select Category --
                                </option>


                                <?php while (
                                    $category =
                                    mysqli_fetch_assoc($categories_query)
                                ): ?>

                                    <option
                                        value="<?php echo $category["cat_id"]; ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $category["cat_name"]
                                        );
                                        ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>


                            <button
                                type="button"
                                class="btn btn-dark quick-add-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#categoryModal"
                            >

                                <i class="fa-solid fa-plus"></i>

                                New Category

                            </button>

                        </div>

                    </div>


                    <!-- PRICE -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Book Price
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                ৳
                            </span>

                            <input
                                type="number"
                                name="book_price"
                                class="form-control"
                                placeholder="Enter price"
                                min="0"
                                required
                            >

                        </div>

                    </div>


                </div>



                <!-- BOOK COVER -->

                <div class="mb-4">

                    <label class="form-label">
                        Book Cover Image
                    </label>


                    <div class="cover-box">

                        <div class="cover-icon">

                            <i class="fa-solid fa-image"></i>

                        </div>


                        <h6>
                            Upload Book Cover
                        </h6>


                        <p>
                            JPG, JPEG, PNG or WEBP • Maximum 5 MB
                        </p>


                        <input
                            type="file"
                            name="book_cover"
                            id="bookCover"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >


                        <img
                            id="coverPreview"
                            class="image-preview"
                            alt="Book Cover Preview"
                        >

                    </div>

                </div>



                <!-- PDF -->

                <div class="mb-4">

                    <label class="form-label">
                        Book PDF
                    </label>


                    <div class="pdf-box">

                        <div class="pdf-icon">

                            <i class="fa-solid fa-file-pdf"></i>

                        </div>


                        <h6>
                            Upload Digital Book
                        </h6>


                        <p>
                            PDF only • Maximum file size 20 MB • Optional
                        </p>


                        <input
                            type="file"
                            name="book_file"
                            id="bookFile"
                            class="form-control"
                            accept=".pdf,application/pdf"
                        >

                    </div>

                </div>



                <!-- PDF COMMENT -->

                <div class="mb-4">

                    <label class="form-label">
                        PDF Comment / Availability Note
                    </label>


                    <textarea
                        name="pdf_comment"
                        class="form-control"
                        rows="4"
                        placeholder="Example: Physical book is available in the library, but PDF is not available."
                    ></textarea>


                    <small class="text-muted">
                        Add a note if the PDF is not available or if you want to provide any availability information.
                    </small>

                </div>



                <!-- BUTTONS -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        name="add_book"
                        class="add-btn"
                    >

                        <i class="fa-solid fa-plus"></i>

                        Add Book

                    </button>


                    <a
                        href="manage_books.php"
                        class="cancel-btn"
                    >

                        Cancel

                    </a>

                </div>


            </form>



            <div class="info-box">

                <i class="fa-solid fa-circle-info"></i>

                The uploaded book cover will be stored in the
                <strong>images/books</strong> folder, while the PDF
                will be stored in the <strong>books</strong> folder.
                PDF upload is optional.

            </div>


        </div>


    </div>

</div>



<!-- =========================
     NEW AUTHOR MODAL
========================= -->

<div
    class="modal fade"
    id="authorModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="fa-solid fa-pen-nib"></i>

                    Add New Author

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                <label class="form-label">
                    Author Name
                </label>


                <input
                    type="text"
                    id="newAuthorName"
                    class="form-control"
                    placeholder="Enter author name"
                >


                <div
                    id="authorMessage"
                    class="mt-3"
                ></div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    Cancel

                </button>


                <button
                    type="button"
                    class="btn btn-dark"
                    id="saveAuthorBtn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Add Author

                </button>

            </div>

        </div>

    </div>

</div>



<!-- =========================
     NEW CATEGORY MODAL
========================= -->

<div
    class="modal fade"
    id="categoryModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="fa-solid fa-layer-group"></i>

                    Add New Category

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                <label class="form-label">
                    Category Name
                </label>


                <input
                    type="text"
                    id="newCategoryName"
                    class="form-control"
                    placeholder="Enter category name"
                >


                <div
                    id="categoryMessage"
                    class="mt-3"
                ></div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    Cancel

                </button>


                <button
                    type="button"
                    class="btn btn-dark"
                    id="saveCategoryBtn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Add Category

                </button>

            </div>

        </div>

    </div>

</div>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js">
</script>


<script>

/* =========================
   BOOK COVER PREVIEW
========================= */

document
    .getElementById("bookCover")
    .addEventListener("change", function () {

        const file = this.files[0];

        const preview =
            document.getElementById("coverPreview");


        if (!file) {

            preview.style.display = "none";
            preview.removeAttribute("src");

            return;
        }


        const reader =
            new FileReader();


        reader.onload = function (event) {

            preview.src =
                event.target.result;

            preview.style.display =
                "block";

        };


        reader.readAsDataURL(file);

    });



/* =========================
   QUICK ADD AUTHOR
========================= */

document
    .getElementById("saveAuthorBtn")
    .addEventListener("click", function () {

        const authorName =
            document.getElementById("newAuthorName").value.trim();

        const message =
            document.getElementById("authorMessage");

        const button =
            this;


        if (!authorName) {

            message.innerHTML =
                '<div class="alert alert-danger py-2 mb-0">Please enter an author name.</div>';

            return;

        }


        button.disabled = true;

        button.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';


        const formData =
            new FormData();

        formData.append(
            "quick_add_author",
            "1"
        );

        formData.append(
            "author_name",
            authorName
        );


        fetch("add_book.php", {

            method: "POST",

            body: formData

        })

        .then(response => response.json())

        .then(data => {

            if (data.success) {

                const select =
                    document.getElementById("authorSelect");


                let existingOption =
                    select.querySelector(
                        'option[value="' +
                        data.author_id +
                        '"]'
                    );


                if (!existingOption) {

                    const option =
                        document.createElement("option");

                    option.value =
                        data.author_id;

                    option.textContent =
                        data.author_name;

                    select.appendChild(option);

                }


                select.value =
                    data.author_id;


                const modal =
                    bootstrap.Modal.getInstance(
                        document.getElementById("authorModal")
                    );


                modal.hide();


                document.getElementById(
                    "newAuthorName"
                ).value = "";


                message.innerHTML = "";

            } else {

                message.innerHTML =
                    '<div class="alert alert-danger py-2 mb-0">' +
                    data.message +
                    '</div>';

            }

        })

        .catch(error => {

            message.innerHTML =
                '<div class="alert alert-danger py-2 mb-0">Something went wrong. Please try again.</div>';

        })

        .finally(() => {

            button.disabled = false;

            button.innerHTML =
                '<i class="fa-solid fa-plus"></i> Add Author';

        });

    });



/* =========================
   QUICK ADD CATEGORY
========================= */

document
    .getElementById("saveCategoryBtn")
    .addEventListener("click", function () {

        const categoryName =
            document.getElementById("newCategoryName").value.trim();

        const message =
            document.getElementById("categoryMessage");

        const button =
            this;


        if (!categoryName) {

            message.innerHTML =
                '<div class="alert alert-danger py-2 mb-0">Please enter a category name.</div>';

            return;

        }


        button.disabled = true;

        button.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';


        const formData =
            new FormData();

        formData.append(
            "quick_add_category",
            "1"
        );

        formData.append(
            "category_name",
            categoryName
        );


        fetch("add_book.php", {

            method: "POST",

            body: formData

        })

        .then(response => response.json())

        .then(data => {

            if (data.success) {

                const select =
                    document.getElementById("categorySelect");


                let existingOption =
                    select.querySelector(
                        'option[value="' +
                        data.cat_id +
                        '"]'
                    );


                if (!existingOption) {

                    const option =
                        document.createElement("option");

                    option.value =
                        data.cat_id;

                    option.textContent =
                        data.cat_name;

                    select.appendChild(option);

                }


                select.value =
                    data.cat_id;


                const modal =
                    bootstrap.Modal.getInstance(
                        document.getElementById("categoryModal")
                    );


                modal.hide();


                document.getElementById(
                    "newCategoryName"
                ).value = "";


                message.innerHTML = "";

            } else {

                message.innerHTML =
                    '<div class="alert alert-danger py-2 mb-0">' +
                    data.message +
                    '</div>';

            }

        })

        .catch(error => {

            message.innerHTML =
                '<div class="alert alert-danger py-2 mb-0">Something went wrong. Please try again.</div>';

        })

        .finally(() => {

            button.disabled = false;

            button.innerHTML =
                '<i class="fa-solid fa-plus"></i> Add Category';

        });

    });



/* =========================
   CLEAR MODAL MESSAGES
========================= */

document
    .getElementById("authorModal")
    .addEventListener("hidden.bs.modal", function () {

        document.getElementById(
            "authorMessage"
        ).innerHTML = "";

    });


document
    .getElementById("categoryModal")
    .addEventListener("hidden.bs.modal", function () {

        document.getElementById(
            "categoryMessage"
        ).innerHTML = "";

    });

</script>


</body>

</html>