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
   QUICK ADD AUTHOR / CATEGORY
========================= */

if (isset($_POST["quick_add_author"])) {

    header("Content-Type: application/json");

    $author_name = trim($_POST["author_name"] ?? "");

    if ($author_name === "") {

        echo json_encode([
            "success" => false,
            "message" => "Author name is required."
        ]);

        exit();

    }



    $author_name_safe = mysqli_real_escape_string(
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

        $existing_author = mysqli_fetch_assoc($check_author);

        echo json_encode([
            "success" => true,
            "author_id" => $existing_author["author_id"],
            "author_name" => $existing_author["author_name"]
        ]);

        exit();

    }



    $insert_author = mysqli_query(
        $conn,
        "INSERT INTO authors (author_name)
         VALUES ('$author_name_safe')"
    );



    if ($insert_author) {

        echo json_encode([
            "success" => true,
            "author_id" => mysqli_insert_id($conn),
            "author_name" => $author_name
        ]);

        exit();

    }



    echo json_encode([
        "success" => false,
        "message" => "Failed to add author."
    ]);

    exit();

}



if (isset($_POST["quick_add_category"])) {

    header("Content-Type: application/json");

    $category_name = trim($_POST["category_name"] ?? "");

    if ($category_name === "") {

        echo json_encode([
            "success" => false,
            "message" => "Category name is required."
        ]);

        exit();

    }



    $category_name_safe = mysqli_real_escape_string(
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

        $existing_category = mysqli_fetch_assoc($check_category);

        echo json_encode([
            "success" => true,
            "cat_id" => $existing_category["cat_id"],
            "cat_name" => $existing_category["cat_name"]
        ]);

        exit();

    }



    $insert_category = mysqli_query(
        $conn,
        "INSERT INTO category (cat_name)
         VALUES ('$category_name_safe')"
    );



    if ($insert_category) {

        echo json_encode([
            "success" => true,
            "cat_id" => mysqli_insert_id($conn),
            "cat_name" => $category_name
        ]);

        exit();

    }



    echo json_encode([
        "success" => false,
        "message" => "Failed to add category."
    ]);

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
   BOOK ID
========================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: manage_books.php");
    exit();
}

$book_id = intval($_GET["id"]);



/* =========================
   FETCH BOOK
========================= */

$book_query = mysqli_query(
    $conn,
    "SELECT *
     FROM books
     WHERE book_id = '$book_id'"
);

if (mysqli_num_rows($book_query) == 0) {
    header("Location: manage_books.php");
    exit();
}

$book = mysqli_fetch_assoc($book_query);



/* =========================
   UPDATE BOOK
========================= */

if (isset($_POST["update_book"])) {

    $book_name = trim($_POST["book_name"]);
    $author_id = intval($_POST["author_id"]);
    $cat_id = intval($_POST["cat_id"]);
    $book_no = intval($_POST["book_no"]);
    $book_price = intval($_POST["book_price"]);
    $pdf_comment = trim($_POST["pdf_comment"] ?? "");

    $book_name_safe = mysqli_real_escape_string($conn, $book_name);

    $pdf_comment_safe = mysqli_real_escape_string(
        $conn,
        $pdf_comment
    );

    $old_file = $book["book_file"];
    $new_file = $old_file;

    $old_cover = $book["book_cover"];
    $new_cover = $old_cover;



    /* =========================
       NEW PDF UPLOAD
    ========================= */

    if (
        isset($_FILES["book_file"]) &&
        $_FILES["book_file"]["error"] === 0
    ) {

        $file_name = $_FILES["book_file"]["name"];
        $file_tmp = $_FILES["book_file"]["tmp_name"];
        $file_size = $_FILES["book_file"]["size"];

        $file_ext = strtolower(
            pathinfo($file_name, PATHINFO_EXTENSION)
        );



        /* PDF only */

        if ($file_ext !== "pdf") {

            header("Location: edit_book.php?id=$book_id&filetype=1");
            exit();

        }



        /* Maximum 20 MB */

        if ($file_size > 20 * 1024 * 1024) {

            header("Location: edit_book.php?id=$book_id&filesize=1");
            exit();

        }



        /* Create unique filename */

        $new_file =
            time() . "_" .
            uniqid() . "_" .
            preg_replace(
                "/[^A-Za-z0-9._-]/",
                "_",
                basename($file_name)
            );



        $upload_path = "../books/" . $new_file;



        if (!move_uploaded_file($file_tmp, $upload_path)) {

            header("Location: edit_book.php?id=$book_id&upload=1");
            exit();

        }

    }



    /* =========================
       NEW BOOK COVER UPLOAD
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

        $allowed_cover_extensions = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];



        /* Check extension */

        if (!in_array($cover_ext, $allowed_cover_extensions, true)) {

            if (
                $new_file !== $old_file &&
                !empty($new_file) &&
                file_exists("../books/" . $new_file)
            ) {

                unlink("../books/" . $new_file);

            }

            header("Location: edit_book.php?id=$book_id&coverfiletype=1");
            exit();

        }



        /* Maximum 5 MB */

        if ($cover_size > 5 * 1024 * 1024) {

            if (
                $new_file !== $old_file &&
                !empty($new_file) &&
                file_exists("../books/" . $new_file)
            ) {

                unlink("../books/" . $new_file);

            }

            header("Location: edit_book.php?id=$book_id&coversize=1");
            exit();

        }



        /* Verify actual image */

        $image_info = @getimagesize($cover_tmp);

        if ($image_info === false) {

            if (
                $new_file !== $old_file &&
                !empty($new_file) &&
                file_exists("../books/" . $new_file)
            ) {

                unlink("../books/" . $new_file);

            }

            header("Location: edit_book.php?id=$book_id&coverinvalid=1");
            exit();

        }



        /* Create unique cover filename */

        $new_cover =
            time() . "_" .
            uniqid() . "_cover." .
            $cover_ext;



        $cover_upload_path = "../images/books/" . $new_cover;



        if (!move_uploaded_file($cover_tmp, $cover_upload_path)) {

            if (
                $new_file !== $old_file &&
                !empty($new_file) &&
                file_exists("../books/" . $new_file)
            ) {

                unlink("../books/" . $new_file);

            }

            header("Location: edit_book.php?id=$book_id&coverupload=1");
            exit();

        }

    }



    $new_file_safe = mysqli_real_escape_string(
        $conn,
        $new_file
    );

    $new_cover_safe = mysqli_real_escape_string(
        $conn,
        $new_cover
    );



    /* =========================
       PDF COMMENT
    ========================= */

    if ($pdf_comment === "") {

        $pdf_comment_sql = "NULL";

    } else {

        $pdf_comment_sql = "'$pdf_comment_safe'";

    }



    /* =========================
       UPDATE DATABASE
    ========================= */

    $update_query = mysqli_query(
        $conn,
        "UPDATE books
         SET
            book_name = '$book_name_safe',
            author_id = '$author_id',
            cat_id = '$cat_id',
            book_no = '$book_no',
            book_price = '$book_price',
            book_file = '$new_file_safe',
            pdf_comment = $pdf_comment_sql,
            book_cover = '$new_cover_safe'
         WHERE book_id = '$book_id'"
    );



    if ($update_query) {

        /* Delete old PDF after successful update */

        if (
            !empty($old_file) &&
            $new_file !== $old_file &&
            file_exists("../books/" . $old_file)
        ) {

            unlink("../books/" . $old_file);
        }



        /* Delete old cover after successful update */

        if (
            !empty($old_cover) &&
            $new_cover !== $old_cover &&
            file_exists("../images/books/" . $old_cover)
        ) {

            unlink("../images/books/" . $old_cover);
        }



        header("Location: manage_books.php?updated=1");
        exit();

    } else {

        /* Delete newly uploaded PDF if database update fails */

        if (
            $new_file !== $old_file &&
            !empty($new_file) &&
            file_exists("../books/" . $new_file)
        ) {

            unlink("../books/" . $new_file);
        }



        /* Delete newly uploaded cover if database update fails */

        if (
            $new_cover !== $old_cover &&
            !empty($new_cover) &&
            file_exists("../images/books/" . $new_cover)
        ) {

            unlink("../images/books/" . $new_cover);
        }



        header("Location: edit_book.php?id=$book_id&error=1");
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

    <title>Edit Book | Library Management System</title>



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
           QUICK ADD BUTTON
        ========================= */

        .quick-add-btn {
            height: 48px;
            white-space: nowrap;
            border-radius: 9px;
            border: 1px solid #111827;
            background: white;
            color: #111827;
            font-size: 13px;
            font-weight: 600;
            padding: 0 14px;
        }



        .quick-add-btn:hover {
            background: #111827;
            color: white;
        }



        /* =========================
           CURRENT PDF
        ========================= */

        .current-pdf {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 14px 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 15px;
        }



        .pdf-info {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }



        .pdf-info i {
            font-size: 28px;
            color: #dc2626;
        }



        .pdf-info strong {
            display: block;
            font-size: 13px;
            max-width: 500px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }



        .pdf-info span {
            color: #6b7280;
            font-size: 12px;
        }



        .read-btn {
            background: #111827;
            color: white;
            text-decoration: none;
            padding: 8px 13px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }



        .read-btn:hover {
            background: #000000;
            color: white;
        }



        .pdf-upload {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 22px;
            text-align: center;
            background: #fafafa;
        }



        .pdf-upload i {
            font-size: 34px;
            color: #dc2626;
            margin-bottom: 8px;
        }



        .pdf-upload p {
            margin: 4px 0 15px;
            color: #6b7280;
            font-size: 13px;
        }



        .pdf-upload input {
            max-width: 500px;
            margin: auto;
        }



        /* =========================
           CURRENT BOOK COVER
        ========================= */

        .current-cover {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 15px;
        }



        .current-cover-image {
            width: 100px;
            height: 135px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #ffffff;
        }



        .cover-info {
            min-width: 0;
        }



        .cover-info strong {
            display: block;
            font-size: 14px;
            color: #111827;
            margin-bottom: 4px;
            word-break: break-word;
        }



        .cover-info span {
            color: #6b7280;
            font-size: 12px;
        }



        .cover-upload {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 22px;
            text-align: center;
            background: #fafafa;
        }



        .cover-upload i {
            font-size: 34px;
            color: #111827;
            margin-bottom: 8px;
        }



        .cover-upload h6 {
            margin-bottom: 5px;
            font-weight: 700;
        }



        .cover-upload p {
            margin: 4px 0 15px;
            color: #6b7280;
            font-size: 13px;
        }



        .cover-upload input {
            max-width: 500px;
            margin: auto;
        }



        /* =========================
           PDF COMMENT
        ========================= */

        .pdf-comment {
            min-height: 100px !important;
            height: auto !important;
            resize: vertical;
        }



        /* =========================
           BUTTONS
        ========================= */

        .update-btn {
            background: #111827;
            border: none;
            color: white;
            height: 48px;
            padding: 0 25px;
            border-radius: 9px;
            font-weight: 600;
        }



        .update-btn:hover {
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

            .current-pdf {
                align-items: flex-start;
                flex-direction: column;
            }

            .current-cover {
                align-items: flex-start;
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
     MAIN
========================= -->

<div class="main">



    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h5>Edit Book</h5>

            <small>
                Update book information, cover and PDF
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

                <h2>Edit Book</h2>

                <p>
                    Update the selected book information.
                </p>

            </div>



            <a href="manage_books.php" class="back-btn">

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

                Failed to upload the new PDF file.

            </div>

        <?php endif; ?>



        <?php if (isset($_GET["coverfiletype"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-image"></i>

                Only JPG, JPEG, PNG and WEBP images are allowed for the book cover.

            </div>

        <?php endif; ?>



        <?php if (isset($_GET["coversize"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-file-image"></i>

                Book cover image size must be 5 MB or less.

            </div>

        <?php endif; ?>



        <?php if (isset($_GET["coverinvalid"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-image"></i>

                The uploaded book cover is not a valid image.

            </div>

        <?php endif; ?>



        <?php if (isset($_GET["coverupload"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-cloud-arrow-up"></i>

                Failed to upload the new book cover.

            </div>

        <?php endif; ?>



        <?php if (isset($_GET["error"])): ?>

            <div class="alert alert-danger custom-alert">

                <i class="fa-solid fa-circle-exclamation"></i>

                Failed to update the book.

            </div>

        <?php endif; ?>



        <!-- FORM CARD -->

        <div class="form-card">



            <div class="form-title">

                <div class="form-title-icon">

                    <i class="fa-solid fa-pen-to-square"></i>

                </div>



                <div>

                    <h4>Book Information</h4>

                    <p>
                        Modify the details below and save your changes.
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
                            value="<?php echo htmlspecialchars($book["book_name"]); ?>"
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
                            value="<?php echo htmlspecialchars($book["book_no"]); ?>"
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



                                <?php while ($author = mysqli_fetch_assoc($authors_query)): ?>

                                    <option
                                        value="<?php echo $author["author_id"]; ?>"
                                        <?php
                                        if ($author["author_id"] == $book["author_id"]) {
                                            echo "selected";
                                        }
                                        ?>
                                    >

                                        <?php echo htmlspecialchars($author["author_name"]); ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>



                            <button
                                type="button"
                                class="quick-add-btn"
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



                                <?php while ($category = mysqli_fetch_assoc($categories_query)): ?>

                                    <option
                                        value="<?php echo $category["cat_id"]; ?>"
                                        <?php
                                        if ($category["cat_id"] == $book["cat_id"]) {
                                            echo "selected";
                                        }
                                        ?>
                                    >

                                        <?php echo htmlspecialchars($category["cat_name"]); ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>



                            <button
                                type="button"
                                class="quick-add-btn"
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
                                value="<?php echo htmlspecialchars($book["book_price"]); ?>"
                                min="0"
                                required
                            >

                        </div>

                    </div>



                </div>



                <!-- CURRENT BOOK COVER -->

                <div class="mb-4">

                    <label class="form-label">
                        Current Book Cover
                    </label>



                    <?php if (
                        !empty($book["book_cover"]) &&
                        file_exists("../images/books/" . $book["book_cover"])
                    ): ?>

                        <div class="current-cover">

                            <img
                                src="../images/books/<?php echo rawurlencode($book["book_cover"]); ?>"
                                class="current-cover-image"
                                alt="Book Cover"
                            >



                            <div class="cover-info">

                                <strong>
                                    <?php echo htmlspecialchars($book["book_cover"]); ?>
                                </strong>

                                <span>
                                    Current book cover
                                </span>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="alert alert-secondary">

                            No book cover has been uploaded for this book.

                        </div>

                    <?php endif; ?>

                </div>



                <!-- NEW BOOK COVER -->

                <div class="mb-4">

                    <label class="form-label">
                        Replace Book Cover
                    </label>



                    <div class="cover-upload">

                        <i class="fa-solid fa-image"></i>



                        <h6>
                            Upload New Book Cover
                        </h6>



                        <p>
                            Leave this empty to keep the current cover.
                            JPG, JPEG, PNG or WEBP • Maximum 5 MB
                        </p>



                        <input
                            type="file"
                            name="book_cover"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                    </div>

                </div>



                <!-- CURRENT PDF -->

                <div class="mb-4">

                    <label class="form-label">
                        Current Book PDF
                    </label>



                    <?php if (!empty($book["book_file"])): ?>

                        <div class="current-pdf">

                            <div class="pdf-info">

                                <i class="fa-solid fa-file-pdf"></i>



                                <div>

                                    <strong>
                                        <?php echo htmlspecialchars($book["book_file"]); ?>
                                    </strong>

                                    <span>
                                        Current PDF
                                    </span>

                                </div>

                            </div>



                            <a
                                href="../books/<?php echo rawurlencode($book["book_file"]); ?>"
                                target="_blank"
                                class="read-btn"
                            >

                                <i class="fa-solid fa-eye"></i>

                                View PDF

                            </a>

                        </div>

                    <?php else: ?>

                        <div class="alert alert-secondary">

                            No PDF has been uploaded for this book.

                        </div>

                    <?php endif; ?>

                </div>



                <!-- NEW PDF -->

                <div class="mb-4">

                    <label class="form-label">
                        Replace PDF
                    </label>



                    <div class="pdf-upload">

                        <i class="fa-solid fa-file-pdf"></i>



                        <h6>
                            Upload New PDF
                        </h6>



                        <p>
                            Leave this empty to keep the current PDF.
                            PDF is optional • Maximum 20 MB
                        </p>



                        <input
                            type="file"
                            name="book_file"
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
                        class="form-control pdf-comment"
                        placeholder="Example: Physical book is available in the library, but PDF is not available."
                        maxlength="500"
                    ><?php echo htmlspecialchars($book["pdf_comment"] ?? ""); ?></textarea>



                    <div class="form-text">
                        Optional. Add a note if the PDF is unavailable or if users need additional information.
                    </div>

                </div>



                <!-- BUTTONS -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        name="update_book"
                        class="update-btn"
                    >

                        <i class="fa-solid fa-floppy-disk"></i>

                        Save Changes

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

                PDF upload is optional. If you upload a new PDF, the old PDF
                will automatically be replaced. If you upload a new book cover,
                the old cover will automatically be replaced. You can also add
                a PDF availability note when a PDF is not available.

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

                    New Author

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

                    New Category

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
       QUICK ADD AUTHOR
    ========================= */

    document.getElementById("saveAuthorBtn").addEventListener("click", function () {

        const authorName = document.getElementById("newAuthorName").value.trim();

        const message = document.getElementById("authorMessage");



        if (authorName === "") {

            message.innerHTML =
                '<div class="alert alert-danger py-2 mb-0">Please enter an author name.</div>';

            return;

        }



        const formData = new FormData();

        formData.append("quick_add_author", "1");

        formData.append("author_name", authorName);



        fetch("edit_book.php?id=<?php echo $book_id; ?>", {
            method: "POST",
            body: formData
        })

        .then(response => response.json())

        .then(data => {

            if (data.success) {

                const select = document.getElementById("authorSelect");

                let existingOption = select.querySelector(
                    'option[value="' + data.author_id + '"]'
                );



                if (!existingOption) {

                    existingOption = document.createElement("option");

                    existingOption.value = data.author_id;

                    existingOption.textContent = data.author_name;

                    select.appendChild(existingOption);

                }



                select.value = data.author_id;



                message.innerHTML =
                    '<div class="alert alert-success py-2 mb-0">Author added successfully.</div>';



                document.getElementById("newAuthorName").value = "";



                setTimeout(function () {

                    const modalElement = document.getElementById("authorModal");

                    const modal = bootstrap.Modal.getInstance(modalElement);

                    if (modal) {
                        modal.hide();
                    }

                    message.innerHTML = "";

                }, 700);

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

        });

    });



    /* =========================
       QUICK ADD CATEGORY
    ========================= */

    document.getElementById("saveCategoryBtn").addEventListener("click", function () {

        const categoryName = document.getElementById("newCategoryName").value.trim();

        const message = document.getElementById("categoryMessage");



        if (categoryName === "") {

            message.innerHTML =
                '<div class="alert alert-danger py-2 mb-0">Please enter a category name.</div>';

            return;

        }



        const formData = new FormData();

        formData.append("quick_add_category", "1");

        formData.append("category_name", categoryName);



        fetch("edit_book.php?id=<?php echo $book_id; ?>", {
            method: "POST",
            body: formData
        })

        .then(response => response.json())

        .then(data => {

            if (data.success) {

                const select = document.getElementById("categorySelect");

                let existingOption = select.querySelector(
                    'option[value="' + data.cat_id + '"]'
                );



                if (!existingOption) {

                    existingOption = document.createElement("option");

                    existingOption.value = data.cat_id;

                    existingOption.textContent = data.cat_name;

                    select.appendChild(existingOption);

                }



                select.value = data.cat_id;



                message.innerHTML =
                    '<div class="alert alert-success py-2 mb-0">Category added successfully.</div>';



                document.getElementById("newCategoryName").value = "";



                setTimeout(function () {

                    const modalElement = document.getElementById("categoryModal");

                    const modal = bootstrap.Modal.getInstance(modalElement);

                    if (modal) {
                        modal.hide();
                    }

                    message.innerHTML = "";

                }, 700);

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

        });

    });



    /* =========================
       CLEAR MODAL MESSAGES
    ========================= */

    document.getElementById("authorModal").addEventListener("hidden.bs.modal", function () {

        document.getElementById("authorMessage").innerHTML = "";

        document.getElementById("newAuthorName").value = "";

    });



    document.getElementById("categoryModal").addEventListener("hidden.bs.modal", function () {

        document.getElementById("categoryMessage").innerHTML = "";

        document.getElementById("newCategoryName").value = "";

    });

</script>



</body>

</html>