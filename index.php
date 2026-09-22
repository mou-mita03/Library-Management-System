<?php

session_start();

$conn = mysqli_connect("localhost", "root", "", "lms");

$total_books = 0;
$total_authors = 0;
$total_categories = 0;
$total_users = 0;

$library_books = [];
$library_categories = [];

$profile_image = "";
$profile_link = "";
$profile_alt = "";
$logged_in_role = "";

if ($conn) {

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM books WHERE approval_status = 'Approved'"
    );

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $total_books = $row["total"];
    }


    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM authors"
    );

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $total_authors = $row["total"];
    }


    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM category"
    );

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $total_categories = $row["total"];
    }


    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM users"
    );

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $total_users = $row["total"];
    }


    /* =========================================================
       LOGGED-IN USER PROFILE
    ========================================================= */

    if (isset($_SESSION["admin_id"])) {

        $admin_id = (int) $_SESSION["admin_id"];

        $profile_query = "
            SELECT
                profile_image
            FROM admins
            WHERE id = $admin_id
            LIMIT 1
        ";

        $profile_result = mysqli_query(
            $conn,
            $profile_query
        );

        if (
            $profile_result &&
            $profile_row = mysqli_fetch_assoc($profile_result)
        ) {

            $profile_image = !empty($profile_row["profile_image"])
                ? $profile_row["profile_image"]
                : "";

        }

        $profile_link = "admin/profile.php";
        $profile_alt = "Admin Profile";
        $logged_in_role = "Admin";

    } elseif (isset($_SESSION["staff_id"])) {

        $staff_id = (int) $_SESSION["staff_id"];

        $profile_query = "
            SELECT
                profile_image
            FROM staffs
            WHERE id = $staff_id
            LIMIT 1
        ";

        $profile_result = mysqli_query(
            $conn,
            $profile_query
        );

        if (
            $profile_result &&
            $profile_row = mysqli_fetch_assoc($profile_result)
        ) {

            $profile_image = !empty($profile_row["profile_image"])
                ? $profile_row["profile_image"]
                : "";

        }

        $profile_link = "staff/profile.php";
        $profile_alt = "Staff Profile";
        $logged_in_role = "Staff";

    } elseif (isset($_SESSION["user_id"])) {

        $user_id = (int) $_SESSION["user_id"];

        $profile_query = "
            SELECT
                profile_image
            FROM users
            WHERE id = $user_id
            LIMIT 1
        ";

        $profile_result = mysqli_query(
            $conn,
            $profile_query
        );

        if (
            $profile_result &&
            $profile_row = mysqli_fetch_assoc($profile_result)
        ) {

            $profile_image = !empty($profile_row["profile_image"])
                ? $profile_row["profile_image"]
                : "";

        }

        $profile_link = "user/profile.php";
        $profile_alt = "User Profile";
        $logged_in_role = "User";

    }


    /* =========================================================
       APPROVED LIBRARY BOOKS
    ========================================================= */

    $book_query = "
        SELECT
            books.book_id,
            books.book_name,
            books.book_no,
            books.book_price,
            books.book_file,
            books.pdf_comment,
            books.book_cover,
            authors.author_name,
            category.cat_id,
            category.cat_name
        FROM books
        LEFT JOIN authors
            ON books.author_id = authors.author_id
        LEFT JOIN category
            ON books.cat_id = category.cat_id
        WHERE books.approval_status = 'Approved'
        ORDER BY books.book_id DESC
    ";

    $book_result = mysqli_query($conn, $book_query);

    if ($book_result) {

        while ($book_row = mysqli_fetch_assoc($book_result)) {

            $library_books[] = $book_row;
        }
    }


    /* =========================================================
       CATEGORIES USED BY APPROVED BOOKS
    ========================================================= */

    $category_query = "
        SELECT DISTINCT
            category.cat_id,
            category.cat_name
        FROM category
        INNER JOIN books
            ON books.cat_id = category.cat_id
        WHERE books.approval_status = 'Approved'
        ORDER BY category.cat_name ASC
    ";

    $category_result = mysqli_query($conn, $category_query);

    if ($category_result) {

        while ($category_row = mysqli_fetch_assoc($category_result)) {

            $library_categories[] = $category_row;
        }
    }


    mysqli_close($conn);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Library Management System</title>


    <!-- Bootstrap 4.6.2 -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
    >


    <!-- Font Awesome 6.5.2 -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #ffffff;
        }


        /* =========================================================
           NAVBAR
        ========================================================= */

        .navbar {
            background: #ffffff !important;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            padding: 14px 0;
            position: relative;
            z-index: 1000;
        }


        .navbar-brand {
            font-size: 25px;
            font-weight: 800;
            color: #111827 !important;
        }


        .navbar-brand i {
            color: #4f46e5;
            margin-right: 7px;
        }


        .navbar-nav .nav-link {
            color: #374151 !important;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
            margin-right: 10px;
            transition: 0.2s ease;
        }


        .navbar-nav .nav-link:hover {
            color: #4f46e5 !important;
        }


        .login-btn {
            background: #4f46e5;
            color: #ffffff !important;
            border-radius: 8px;
            padding: 9px 18px !important;
            margin-left: 12px;
        }


        .login-btn:hover {
            background: #3730a3;
        }


        /* PROFILE BUTTON */

        .profile-nav-item {
            display: flex;
            align-items: center;
            margin-left: 8px;
        }


        .profile-nav-link {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            color: #4f46e5;
            border: 2px solid #e5e7eb;
            text-decoration: none !important;
            transition: 0.2s ease;
        }


        .profile-nav-link:hover {
            border-color: #4f46e5;
            transform: translateY(-2px);
            color: #4f46e5;
            box-shadow: 0 5px 15px rgba(79,70,229,0.15);
        }


        .profile-nav-link img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }


        .profile-nav-link i {
            font-size: 17px;
        }


        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            border-radius: 10px;
            padding: 8px;
        }


        .dropdown-item {
            padding: 10px 14px;
            border-radius: 7px;
            font-size: 14px;
        }


        .dropdown-item:hover {
            background: #eef2ff;
            color: #4f46e5;
        }



        /* =========================================================
           HERO
        ========================================================= */

        .hero {
            min-height: 650px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            text-align: center;
            color: #ffffff;
        }


        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;

            width: 100%;
            height: 100%;

            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;

            opacity: 0;

            animation: heroBackground 8s infinite;
        }


        .hero-slide.slide-1 {
            background-image:
                url("images/hero/library-bg-1.jpg");

            animation-delay: 0s;
        }


        .hero-slide.slide-2 {
            background-image:
                url("images/hero/library-bg-2.jpg");

            animation-delay: 4s;
        }


        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;

            width: 100%;
            height: 100%;

            background:
                linear-gradient(
                    rgba(15, 23, 42, 0.70),
                    rgba(15, 23, 42, 0.78)
                );

            z-index: 2;
        }


        @keyframes heroBackground {

            0% {
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            40% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 0;
            }

        }


        .hero-content {
            position: relative;
            z-index: 3;
            width: 100%;
        }


        .hero small {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            padding: 8px 17px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 20px;
        }


        .hero h1 {
            font-size: 55px;
            font-weight: 800;
            line-height: 1.12;
            margin-bottom: 20px;
        }


        .hero p {
            max-width: 700px;
            margin: 0 auto 32px;
            font-size: 17px;
            line-height: 1.7;
            color: #e5e7eb;
        }


        .hero-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
        }


        .hero-btn {
            display: inline-block;
            padding: 13px 25px;
            border-radius: 8px;
            text-decoration: none !important;
            font-weight: 700;
            font-size: 14px;
            transition: 0.25s ease;
        }


        .hero-btn-primary {
            background: #ffffff;
            color: #3730a3;
        }


        .hero-btn-primary:hover {
            transform: translateY(-3px);
            background: #eef2ff;
            color: #3730a3;
        }


        .hero-btn-secondary {
            background: #4f46e5;
            color: #ffffff;
        }


        .hero-btn-secondary:hover {
            transform: translateY(-3px);
            background: #6366f1;
            color: #ffffff;
        }



        /* =========================================================
           STATS
        ========================================================= */

        .stats {
            background: #ffffff;
            padding: 35px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            position: relative;
            z-index: 5;
        }


        .stat-item {
            text-align: center;
            border-right: 1px solid #e5e7eb;
        }


        .stat-item:last-child {
            border-right: none;
        }


        .stat-item h3 {
            color: #4f46e5;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 5px;
        }


        .stat-item p {
            color: #6b7280;
            font-size: 13px;
            margin: 0;
        }



        /* =========================================================
           COMMON SECTION
        ========================================================= */

        .section {
            padding: 90px 20px;
        }


        .section-title {
            text-align: center;
            margin-bottom: 55px;
        }


        .section-title span {
            color: #4f46e5;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }


        .section-title h2 {
            font-size: 38px;
            font-weight: 800;
            margin-top: 8px;
            margin-bottom: 12px;
        }


        .section-title p {
            color: #6b7280;
            max-width: 650px;
            margin: auto;
            line-height: 1.7;
        }



        /* =========================================================
           ABOUT
        ========================================================= */

        .about {
            background: #f8fafc;
        }


        .about-content h3 {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 18px;
        }


        .about-content p {
            color: #6b7280;
            line-height: 1.8;
            font-size: 15px;
            margin-bottom: 15px;
        }


        .about-list {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }


        .about-list li {
            margin-bottom: 12px;
            color: #374151;
            font-size: 14px;
        }


        .about-list i {
            color: #4f46e5;
            margin-right: 10px;
        }


        .about-visual {
            min-height: 350px;
            background:
                linear-gradient(
                    rgba(79,70,229,0.15),
                    rgba(79,70,229,0.15)
                ),
                url("images/library/library-1.jpg");

            background-size: cover;
            background-position: center;

            border-radius: 20px;
            overflow: hidden;

            box-shadow: 0 20px 45px rgba(15,23,42,0.12);
        }



        /* =========================================================
           SERVICES
        ========================================================= */

        .service-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 30px 25px;
            height: 100%;
            text-align: center;
            transition: 0.25s ease;
        }


        .service-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 18px 40px rgba(15,23,42,0.09);
        }


        .service-icon {
            width: 65px;
            height: 65px;
            border-radius: 15px;
            background: #eef2ff;
            color: #4f46e5;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 20px;

            font-size: 25px;
        }


        .service-card h4 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 12px;
        }


        .service-card p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.7;
            margin: 0;
        }



        /* =========================================================
           CREATOR GALLERY
        ========================================================= */

        .creator-section {
            background: #f8fafc;
        }


        .creator-row {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }


        .creator-col {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            padding-left: 15px;
            padding-right: 15px;
            margin-bottom: 30px;
        }


        .creator-col-bottom {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }


        .creator-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            overflow: hidden;
            height: 100%;
            text-align: center;
            transition: 0.25s ease;
        }


        .creator-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 18px 40px rgba(15,23,42,0.10);
        }


        .creator-image {
            width: 100%;
            height: 300px;
            overflow: hidden;
            background: #eef2ff;
        }


        .creator-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: 0.35s ease;
        }


        .creator-card:hover .creator-image img {
            transform: scale(1.04);
        }


        .creator-info {
            padding: 22px 18px 25px;
        }


        .creator-info h4 {
            margin: 0 0 8px;
            font-size: 20px;
            font-weight: 800;
            color: #111827;
        }


        .creator-role {
            color: #4f46e5;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
        }


        .creator-info p {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
            margin: 0 auto 18px;
            max-width: 310px;
            min-height: 42px;
        }


        .creator-social {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 12px;
        }


        .creator-social a {
            width: 40px;
            height: 40px;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            text-decoration: none !important;
            font-size: 17px;

            transition: 0.2s ease;
        }


        .facebook-link {
            background: #eef2ff;
            color: #1877f2;
        }


        .github-link {
            background: #f3f4f6;
            color: #111827;
        }


        .creator-social a:hover {
            transform: translateY(-3px);
        }


        .facebook-link:hover {
            background: #1877f2;
            color: #ffffff;
        }


        .github-link:hover {
            background: #111827;
            color: #ffffff;
        }



        /* =========================================================
           LIBRARY BOOK GALLERY
        ========================================================= */

        .library-gallery-section {
            background: #ffffff;
        }


        .library-controls {
            max-width: 1100px;
            margin: -25px auto 45px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }


        .category-label {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
        }


        .category-select {
            min-width: 230px;
            height: 43px;
            border-radius: 9px;
            border: 1px solid #d1d5db;
            padding: 0 13px;
            background: #ffffff;
            color: #374151;
            font-size: 13px;
            outline: none;
        }


        .category-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.10);
        }


        .book-gallery-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            height: 100%;
            transition: 0.25s ease;
            display: flex;
            flex-direction: column;
        }


        .book-gallery-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 40px rgba(15,23,42,0.10);
        }


        .book-cover {
            height: 210px;
            background:
                linear-gradient(
                    135deg,
                    #eef2ff,
                    #e0e7ff
                );

            display: flex;
            align-items: center;
            justify-content: center;

            position: relative;
            overflow: hidden;
        }


        .book-cover::before {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(79,70,229,0.07);
            top: -70px;
            right: -50px;
        }


        .book-cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            position: relative;
            z-index: 1;
        }


        .book-cover-icon {
            width: 88px;
            height: 88px;
            border-radius: 22px;
            background: #ffffff;
            color: #4f46e5;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 40px;

            box-shadow: 0 15px 30px rgba(79,70,229,0.12);

            position: relative;
            z-index: 2;
        }


        .book-pdf-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            background: #dc2626;
            color: #ffffff;
            padding: 6px 9px;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 800;
            z-index: 3;
        }


        .book-info {
            padding: 20px 18px 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }


        .book-name {
            font-size: 18px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 9px;

            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }


        .book-meta {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 6px;
            line-height: 1.6;
        }


        .book-meta strong {
            color: #374151;
        }


        .book-category {
            display: inline-block;
            align-self: flex-start;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 10px;
            font-weight: 800;
            padding: 5px 9px;
            border-radius: 20px;
            margin-top: 5px;
            margin-bottom: 16px;
        }


        .book-actions {
            margin-top: auto;
            display: flex;
            gap: 8px;
        }


        .book-action-btn {
            flex: 1;
            text-align: center;
            padding: 9px 7px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none !important;
            transition: 0.2s ease;
        }


        .preview-book-btn {
            background: #4f46e5;
            color: #ffffff !important;
        }


        .preview-book-btn:hover {
            background: #3730a3;
        }


        .download-book-btn {
            background: #111827;
            color: #ffffff !important;
        }


        .download-book-btn:hover {
            background: #000000;
        }


        .login-download-btn {
            background: #f3f4f6;
            color: #374151 !important;
        }


        .login-download-btn:hover {
            background: #e5e7eb;
        }


        .pdf-unavailable {
            background: #f3f4f6;
            color: #6b7280;
            cursor: default;
        }


        .pdf-comment {
            color: #6b7280;
            font-size: 11px;
            line-height: 1.5;
            margin-top: 10px;
            padding: 9px 10px;
            background: #f8fafc;
            border-left: 3px solid #d1d5db;
            border-radius: 6px;
        }


        .show-more-wrapper {
            text-align: center;
            margin-top: 35px;
        }


        .show-more-btn {
            border: none;
            background: #111827;
            color: #ffffff;
            padding: 11px 22px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
        }


        .show-more-btn:hover {
            background: #000000;
            transform: translateY(-2px);
        }


        .empty-books {
            width: 100%;
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }


        .empty-books i {
            font-size: 35px;
            margin-bottom: 12px;
            color: #9ca3af;
        }



        /* =========================================================
           HOW IT WORKS
        ========================================================= */

        .how-card {
            text-align: center;
            padding: 20px;
        }


        .how-number {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #4f46e5;
            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
            font-weight: 800;

            margin: 0 auto 20px;
        }


        .how-card h4 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 10px;
        }


        .how-card p {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
        }



        /* =========================================================
           LOGIN
        ========================================================= */

        .login-section {
            background: #f8fafc;
        }


        .login-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 40px 25px;
            text-align: center;
            height: 100%;
            transition: 0.25s ease;
        }


        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(15,23,42,0.08);
        }


        .login-card i {
            font-size: 35px;
            color: #4f46e5;
            margin-bottom: 18px;
        }


        .login-card h4 {
            font-size: 21px;
            font-weight: 800;
            margin-bottom: 10px;
        }


        .login-card p {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
            min-height: 42px;
        }


        .login-card a {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            margin-top: 8px;
            transition: 0.2s ease;
        }


        .login-card a:hover {
            background: #3730a3;
            color: #ffffff;
        }



        /* =========================================================
           FOOTER
        ========================================================= */

        footer {
            background: #111827;
            color: #ffffff;
            padding: 45px 0 20px;
        }


        .footer-brand {
            font-size: 25px;
            font-weight: 800;
            margin-bottom: 15px;
        }


        .footer-brand i {
            color: #818cf8;
            margin-right: 7px;
        }


        footer p {
            color: #9ca3af;
            font-size: 13px;
            line-height: 1.7;
        }


        .footer-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
        }


        .footer-links {
            list-style: none;
            padding: 0;
        }


        .footer-links li {
            margin-bottom: 8px;
        }


        .footer-links a {
            color: #9ca3af;
            text-decoration: none;
            font-size: 13px;
            transition: 0.2s ease;
        }


        .footer-links a:hover {
            color: #ffffff;
        }


        .copyright {
            border-top: 1px solid rgba(255,255,255,0.08);
            margin-top: 30px;
            padding-top: 18px;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
        }



        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 991px) {

            .navbar-nav {
                padding-top: 15px;
            }


            .login-btn {
                margin-left: 0;
                display: inline-block;
            }


            .profile-nav-item {
                justify-content: flex-start;
                margin-left: 0;
                margin-top: 8px;
            }


            .hero h1 {
                font-size: 43px;
            }


            .stat-item {
                margin-bottom: 20px;
                border-right: none;
            }


            .creator-col,
            .creator-col-bottom {
                flex: 0 0 50%;
                max-width: 50%;
            }


            .library-controls {
                justify-content: center;
            }

        }



        @media (max-width: 767px) {

            .hero {
                min-height: 600px;
            }


            .hero h1 {
                font-size: 35px;
            }


            .hero p {
                font-size: 15px;
            }


            .section {
                padding: 70px 20px;
            }


            .section-title h2 {
                font-size: 29px;
            }


            .about-visual {
                margin-top: 30px;
            }


            .creator-col,
            .creator-col-bottom {
                flex: 0 0 100%;
                max-width: 100%;
            }


            .creator-image {
                height: 300px;
            }


            .hero-slide {
                background-position: center center;
            }


            .library-controls {
                flex-direction: column;
                align-items: stretch;
                margin-top: -20px;
            }


            .category-select {
                width: 100%;
            }


            .book-actions {
                flex-direction: column;
            }

        }



    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg navbar-light">

    <div class="container">

        <a class="navbar-brand" href="#">
            <i class="fa-solid fa-book-open"></i>
            Library
        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-toggle="collapse"
            data-target="#navbarNav"
        >
            <span class="navbar-toggler-icon"></span>
        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarNav"
        >

            <ul class="navbar-nav ml-auto">

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        Home
                    </a>
                </li>


                <li class="nav-item">
                    <a class="nav-link" href="#about">
                        About
                    </a>
                </li>


                <li class="nav-item">
                    <a class="nav-link" href="#services">
                        Services
                    </a>
                </li>


                <li class="nav-item">
                    <a class="nav-link" href="#creators">
                        Creators
                    </a>
                </li>


                <li class="nav-item">
                    <a class="nav-link" href="#how-it-works">
                        How It Works
                    </a>
                </li>


                <li class="nav-item">
                    <a class="nav-link" href="#gallery">
                        Gallery
                    </a>
                </li>


                <li class="nav-item dropdown">

                    <a
                        class="nav-link login-btn dropdown-toggle"
                        href="#"
                        id="loginDropdown"
                        role="button"
                        data-toggle="dropdown"
                    >
                        Login
                    </a>


                    <div
                        class="dropdown-menu dropdown-menu-right"
                        aria-labelledby="loginDropdown"
                    >

                        <a
                            class="dropdown-item"
                            href="user/index.php"
                        >
                            <i class="fa-solid fa-user mr-2"></i>
                            User Login
                        </a>


                        <a
                            class="dropdown-item"
                            href="staff/index.php"
                        >
                            <i class="fa-solid fa-user-tie mr-2"></i>
                            Staff Login
                        </a>


                        <a
                            class="dropdown-item"
                            href="admin/indexad.php"
                        >
                            <i class="fa-solid fa-user-shield mr-2"></i>
                            Admin Login
                        </a>

                    </div>

                </li>


                <?php if ($logged_in_role != ""): ?>

                    <li class="nav-item profile-nav-item">

                        <a
                            href="<?php echo htmlspecialchars($profile_link); ?>"
                            class="profile-nav-link"
                            title="<?php echo htmlspecialchars($profile_alt); ?>"
                        >

                            <?php if (!empty($profile_image)): ?>

                                <?php if ($logged_in_role == "User"): ?>

                                    <img
                                        src="user/uploads/<?php echo rawurlencode($profile_image); ?>"
                                        alt="<?php echo htmlspecialchars($profile_alt); ?>"
                                    >

                                <?php elseif ($logged_in_role == "Staff"): ?>

                                    <img
                                        src="staff/uploads/<?php echo rawurlencode($profile_image); ?>"
                                        alt="<?php echo htmlspecialchars($profile_alt); ?>"
                                    >

                                <?php elseif ($logged_in_role == "Admin"): ?>

                                    <img
                                        src="admin/uploads/<?php echo rawurlencode($profile_image); ?>"
                                        alt="<?php echo htmlspecialchars($profile_alt); ?>"
                                    >

                                <?php endif; ?>

                            <?php else: ?>

                                <?php if ($logged_in_role == "Admin"): ?>

                                    <i class="fa-solid fa-user-shield"></i>

                                <?php elseif ($logged_in_role == "Staff"): ?>

                                    <i class="fa-solid fa-user-tie"></i>

                                <?php else: ?>

                                    <i class="fa-solid fa-user"></i>

                                <?php endif; ?>

                            <?php endif; ?>

                        </a>

                    </li>

                <?php endif; ?>


            </ul>

        </div>

    </div>

</nav>



<!-- =========================================================
     HERO
========================================================= -->

<section class="hero" id="home">

    <div class="hero-slide slide-1"></div>

    <div class="hero-slide slide-2"></div>

    <div class="hero-overlay"></div>

    <div class="hero-content">

        <div class="container">

            <small>
                SMART LIBRARY MANAGEMENT SYSTEM
            </small>


            <h1>
                Your Knowledge,<br>
                Organized Better.
            </h1>


            <p>
                Discover, manage and explore books with a modern
                Library Management System designed to make your
                library experience easier and smarter.
            </p>


            <div class="hero-buttons">

                <a
                    href="#services"
                    class="hero-btn hero-btn-primary"
                >
                    <i class="fa-solid fa-book-open mr-2"></i>
                    Explore Library
                </a>


                <a
                    href="#login"
                    class="hero-btn hero-btn-secondary"
                >
                    <i class="fa-solid fa-right-to-bracket mr-2"></i>
                    Login to Portal
                </a>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     STATS
========================================================= -->

<section class="stats">

    <div class="container">

        <div class="row">

            <div class="col-md-3 col-6">

                <div class="stat-item">

                    <h3>
                        <?php echo $total_books; ?>+
                    </h3>

                    <p>
                        Available Books
                    </p>

                </div>

            </div>


            <div class="col-md-3 col-6">

                <div class="stat-item">

                    <h3>
                        <?php echo $total_authors; ?>+
                    </h3>

                    <p>
                        Authors
                    </p>

                </div>

            </div>


            <div class="col-md-3 col-6">

                <div class="stat-item">

                    <h3>
                        <?php echo $total_categories; ?>+
                    </h3>

                    <p>
                        Categories
                    </p>

                </div>

            </div>


            <div class="col-md-3 col-6">

                <div class="stat-item">

                    <h3>
                        <?php echo $total_users; ?>+
                    </h3>

                    <p>
                        Registered Users
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     ABOUT
========================================================= -->

<section class="section about" id="about">

    <div class="container">

        <div class="section-title">

            <span>
                About System
            </span>

            <h2>
                A Smarter Way to Manage Your Library
            </h2>

            <p>
                Our Library Management System helps organize books,
                authors, categories and users in one convenient platform.
            </p>

        </div>


        <div class="row align-items-center">

            <div class="col-lg-6">

                <div class="about-content">

                    <h3>
                        Everything Organized in One Place
                    </h3>


                    <p>
                        This system is designed to simplify everyday
                        library operations and make information easier
                        to access for both users and library staff.
                    </p>


                    <p>
                        From managing books and authors to handling
                        users and library resources, everything can
                        be managed through a structured digital system.
                    </p>


                    <ul class="about-list">

                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            Easy book management
                        </li>


                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            Organized author and category information
                        </li>


                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            User management system
                        </li>


                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            Simple and user-friendly interface
                        </li>

                    </ul>

                </div>

            </div>


            <div class="col-lg-6">

                <div class="about-visual"></div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     SERVICES
========================================================= -->

<section class="section" id="services">

    <div class="container">

        <div class="section-title">

            <span>
                Services
            </span>

            <h2>
                What Our System Provides
            </h2>

            <p>
                Powerful and simple features for modern library management.
            </p>

        </div>


        <div class="row">


            <div class="col-lg-4 col-md-6 mb-4">

                <div class="service-card">

                    <div class="service-icon">
                        <i class="fa-solid fa-book"></i>
                    </div>

                    <h4>
                        Book Management
                    </h4>

                    <p>
                        Manage books, book information,
                        availability and library collections
                        efficiently.
                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6 mb-4">

                <div class="service-card">

                    <div class="service-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <h4>
                        User Management
                    </h4>

                    <p>
                        Manage registered users and provide
                        them with convenient access to library
                        resources.
                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6 mb-4">

                <div class="service-card">

                    <div class="service-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>

                    <h4>
                        Categories
                    </h4>

                    <p>
                        Organize books into different categories
                        to make searching and management easier.
                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6 mb-4">

                <div class="service-card">

                    <div class="service-icon">
                        <i class="fa-solid fa-pen-nib"></i>
                    </div>

                    <h4>
                        Author Management
                    </h4>

                    <p>
                        Keep author information organized and
                        connected with library books.
                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6 mb-4">

                <div class="service-card">

                    <div class="service-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <h4>
                        Easy Search
                    </h4>

                    <p>
                        Find useful library information quickly
                        through an organized system.
                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6 mb-4">

                <div class="service-card">

                    <div class="service-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>

                    <h4>
                        System Management
                    </h4>

                    <p>
                        Provide staff and administrators with
                        tools for efficient system operations.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     CREATORS
========================================================= -->

<section class="section creator-section" id="creators">

    <div class="container">

        <div class="section-title">

            <span>
                Our Team
            </span>

            <h2>
                Meet the Creators
            </h2>

            <p>
                The team members who contributed to building
                and developing this Library Management System.
            </p>

        </div>


        <div class="row creator-row">


            <!-- Murad -->

            <div class="creator-col">

                <div class="creator-card">

                    <div class="creator-image">

                        <img
                            src="images/creators/murad.jpg"
                            alt="Murad Hasan"
                        >

                    </div>


                    <div class="creator-info">

                        <h4>
                            Murad Hasan
                        </h4>


                        <div class="creator-role">
                            Project Developer
                        </div>


                        <p>
                            Murad contributed to the development
                            and overall design of the Library
                            Management System.
                        </p>


                        <div class="creator-social">

                            <a
                                href="https://www.facebook.com/cadet.murad.hasan"
                                target="_blank"
                                class="facebook-link"
                                title="Facebook"
                            >
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>


                            <a
                                href="https://github.com/Muradpuc"
                                target="_blank"
                                class="github-link"
                                title="GitHub"
                            >
                                <i class="fa-brands fa-github"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </div>



            <!-- Moumita -->

            <div class="creator-col">

                <div class="creator-card">

                    <div class="creator-image">

                        <img
                            src="images/creators/momita.jpg"
                            alt="Moumita"
                        >

                    </div>


                    <div class="creator-info">

                        <h4>
                            Moumita
                        </h4>


                        <div class="creator-role">
                            UI/UX Contributor
                        </div>


                        <p>
                            Momita contributed to the user interface,
                            visual design and project presentation.
                        </p>


                        <div class="creator-social">

                            <a
                                href="https://www.facebook.com/smita.orni"
                                target="_blank"
                                class="facebook-link"
                                title="Facebook"
                            >
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>


                            <a
                                href="https://github.com/mou-mita03"
                                target="_blank"
                                class="github-link"
                                title="GitHub"
                            >
                                <i class="fa-brands fa-github"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </div>



            <!-- Mishu -->

            <div class="creator-col">

                <div class="creator-card">

                    <div class="creator-image">

                        <img
                            src="images/creators/mishu.jpg"
                            alt="Mishu"
                        >

                    </div>


                    <div class="creator-info">

                        <h4>
                            Mishu
                        </h4>


                        <div class="creator-role">
                            Database Contributor
                        </div>


                        <p>
                            Mishu contributed to database organization,
                            structure and system data management.
                        </p>


                        <div class="creator-social">

                            <a
                                href="https://www.facebook.com/share/1BrWrayJqt/"
                                target="_blank"
                                class="facebook-link"
                                title="Facebook"
                            >
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>


                            <a
                                href="https://github.com/mishu05d"
                                target="_blank"
                                class="github-link"
                                title="GitHub"
                            >
                                <i class="fa-brands fa-github"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </div>



            <!-- Punom -->

            <div class="creator-col creator-col-bottom">

                <div class="creator-card">

                    <div class="creator-image">

                        <img
                            src="images/creators/punom.jpg"
                            alt="Punom"
                        >

                    </div>


                    <div class="creator-info">

                        <h4>
                            Punom
                        </h4>


                        <div class="creator-role">
                            Project Contributor
                        </div>


                        <p>
                            Punom contributed to project planning,
                            documentation and overall system activities.
                        </p>


                        <div class="creator-social">

                            <a
                                href="https://www.facebook.com/share/1JfrRwMYvG/"
                                target="_blank"
                                class="facebook-link"
                                title="Facebook"
                            >
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>


                            <a
                                href="https://github.com/punambiswas14167"
                                target="_blank"
                                class="github-link"
                                title="GitHub"
                            >
                                <i class="fa-brands fa-github"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </div>



            <!-- Foisal -->

            <div class="creator-col creator-col-bottom">

                <div class="creator-card">

                    <div class="creator-image">

                        <img
                            src="images/creators/foisal.jpg"
                            alt="Foisal"
                        >

                    </div>


                    <div class="creator-info">

                        <h4>
                            Foisal
                        </h4>


                        <div class="creator-role">
                            Backend Contributor
                        </div>


                        <p>
                            Foisal contributed to backend functionality,
                            system operations and project development.
                        </p>


                        <div class="creator-social">

                            <a
                                href="#"
                                class="facebook-link"
                                title="Facebook"
                            >
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>


                            <a
                                href="https://github.com/ssfsameer"
                                target="_blank"
                                class="github-link"
                                title="GitHub"
                            >
                                <i class="fa-brands fa-github"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section class="section about" id="how-it-works">

    <div class="container">

        <div class="section-title">

            <span>
                Simple Process
            </span>

            <h2>
                How It Works
            </h2>

            <p>
                Accessing and using the library system is simple and easy.
            </p>

        </div>


        <div class="row">


            <div class="col-md-4">

                <div class="how-card">

                    <div class="how-number">
                        1
                    </div>


                    <h4>
                        Login
                    </h4>


                    <p>
                        Choose your portal and log in using
                        your account credentials.
                    </p>

                </div>

            </div>



            <div class="col-md-4">

                <div class="how-card">

                    <div class="how-number">
                        2
                    </div>


                    <h4>
                        Explore
                    </h4>


                    <p>
                        Explore books, authors, categories
                        and available library resources.
                    </p>

                </div>

            </div>



            <div class="col-md-4">

                <div class="how-card">

                    <div class="how-number">
                        3
                    </div>


                    <h4>
                        Manage
                    </h4>


                    <p>
                        Manage library resources and system
                        activities through the appropriate portal.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     LIBRARY BOOK GALLERY
========================================================= -->

<section class="section library-gallery-section" id="gallery">

    <div class="container">

        <div class="section-title">

            <span>
                Library Collection
            </span>

            <h2>
                Explore Our Library
            </h2>

            <p>
                Browse our available books, view their PDF copies,
                and discover books by category.
            </p>

        </div>


        <!-- CATEGORY FILTER -->

        <div class="library-controls">

            <div class="category-label">
                <i class="fa-solid fa-filter mr-1"></i>
                Browse by Category
            </div>


            <select
                id="bookCategoryFilter"
                class="category-select"
            >

                <option value="all">
                    All Categories
                </option>


                <?php foreach ($library_categories as $category): ?>

                    <option
                        value="<?php echo (int)$category["cat_id"]; ?>"
                    >
                        <?php
                        echo htmlspecialchars(
                            $category["cat_name"]
                        );
                        ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div
            class="row"
            id="bookGallery"
        >


            <?php if (count($library_books) > 0): ?>


                <?php foreach ($library_books as $index => $book): ?>

                    <?php

                    $book_id = (int)$book["book_id"];

                    $book_name = htmlspecialchars(
                        $book["book_name"]
                    );

                    $author_name = !empty($book["author_name"])
                        ? htmlspecialchars($book["author_name"])
                        : "Unknown Author";

                    $category_name = !empty($book["cat_name"])
                        ? htmlspecialchars($book["cat_name"])
                        : "Uncategorized";

                    $category_id = !empty($book["cat_id"])
                        ? (int)$book["cat_id"]
                        : 0;

                    $has_pdf =
                        !empty($book["book_file"]);

                    $pdf_comment = !empty($book["pdf_comment"])
                        ? htmlspecialchars($book["pdf_comment"])
                        : "";

                    $has_cover =
                        !empty($book["book_cover"]) &&
                        file_exists(
                            __DIR__ . "/images/books/" .
                            $book["book_cover"]
                        );

                    ?>

                    <div
                        class="col-lg-4 col-md-6 mb-4 book-item"
                        data-category="<?php echo $category_id; ?>"
                    >

                        <div class="book-gallery-card">

                            <div class="book-cover">

                                <?php if ($has_cover): ?>

                                    <img
                                        src="images/books/<?php echo rawurlencode($book["book_cover"]); ?>"
                                        class="book-cover-image"
                                        alt="<?php echo $book_name; ?> Book Cover"
                                    >

                                <?php else: ?>

                                    <div class="book-cover-icon">
                                        <i class="fa-solid fa-book"></i>
                                    </div>

                                <?php endif; ?>


                                <?php if ($has_pdf): ?>

                                    <div class="book-pdf-badge">
                                        PDF
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="book-info">

                                <div class="book-name">
                                    <?php echo $book_name; ?>
                                </div>


                                <div class="book-meta">
                                    <strong>Author:</strong>
                                    <?php echo $author_name; ?>
                                </div>


                                <div class="book-meta">
                                    <strong>Book No:</strong>
                                    <?php echo (int)$book["book_no"]; ?>
                                </div>


                                <div class="book-category">
                                    <?php echo $category_name; ?>
                                </div>


                                <div class="book-actions">

                                    <?php if ($has_pdf): ?>

                                        <a
                                            href="book_preview.php?id=<?php echo $book_id; ?>"
                                            target="_blank"
                                            class="book-action-btn preview-book-btn"
                                        >
                                            <i class="fa-solid fa-eye mr-1"></i>
                                            View PDF
                                        </a>


                                        <?php if (
                                            isset($_SESSION["user_id"]) ||
                                            isset($_SESSION["staff_id"]) ||
                                            isset($_SESSION["admin_id"])
                                        ): ?>

                                            <a
                                                href="download_book.php?id=<?php echo $book_id; ?>"
                                                class="book-action-btn download-book-btn"
                                            >
                                                <i class="fa-solid fa-download mr-1"></i>
                                                Download
                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="user/index.php"
                                                class="book-action-btn login-download-btn"
                                            >
                                                <i class="fa-solid fa-lock mr-1"></i>
                                                Login to Download
                                            </a>

                                        <?php endif; ?>


                                    <?php else: ?>

                                        <div
                                            class="book-action-btn pdf-unavailable"
                                        >
                                            <i class="fa-solid fa-file-circle-xmark mr-1"></i>
                                            PDF Not Available
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <?php if (!empty($pdf_comment)): ?>

                                    <div class="pdf-comment">
                                        <i class="fa-solid fa-circle-info mr-1"></i>
                                        <?php echo $pdf_comment; ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-books">

                    <i class="fa-solid fa-book-open"></i>

                    <h4>
                        No Books Available
                    </h4>

                    <p>
                        There are currently no approved books
                        in the library collection.
                    </p>

                </div>


            <?php endif; ?>


        </div>


        <!-- SHOW MORE -->

        <?php if (count($library_books) > 6): ?>

            <div class="show-more-wrapper">

                <button
                    type="button"
                    id="showMoreBooks"
                    class="show-more-btn"
                >
                    <i class="fa-solid fa-chevron-down mr-2"></i>
                    Show More Books
                </button>

            </div>

        <?php endif; ?>


    </div>

</section>



<!-- =========================================================
     LOGIN
========================================================= -->

<section class="section login-section" id="login">

    <div class="container">

        <div class="section-title">

            <span>
                Portal Access
            </span>

            <h2>
                Login to Your Portal
            </h2>

            <p>
                Select the appropriate portal according to your role.
            </p>

        </div>


        <div class="row">


            <div class="col-lg-4 col-md-6 mb-4">

                <div class="login-card">

                    <i class="fa-solid fa-user"></i>

                    <h4>
                        User Portal
                    </h4>

                    <p>
                        Access your library account and explore
                        available resources.
                    </p>

                    <a href="user/index.php">
                        User Login
                    </a>

                </div>

            </div>



            <div class="col-lg-4 col-md-6 mb-4">

                <div class="login-card">

                    <i class="fa-solid fa-user-tie"></i>

                    <h4>
                        Staff Portal
                    </h4>

                    <p>
                        Manage library operations and system
                        activities as a staff member.
                    </p>

                    <a href="staff/index.php">
                        Staff Login
                    </a>

                </div>

            </div>



            <div class="col-lg-4 col-md-6 mb-4">

                <div class="login-card">

                    <i class="fa-solid fa-user-shield"></i>

                    <h4>
                        Admin Portal
                    </h4>

                    <p>
                        Manage the complete library system
                        through the administration portal.
                    </p>

                    <a href="admin/indexad.php">
                        Admin Login
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="container">

        <div class="row">


            <div class="col-lg-5 mb-4">

                <div class="footer-brand">

                    <i class="fa-solid fa-book-open"></i>
                    Library

                </div>


                <p>
                    A modern Library Management System designed
                    to make library resources easier to organize,
                    access and manage.
                </p>

            </div>



            <div class="col-lg-3 col-md-6 mb-4">

                <div class="footer-title">
                    Quick Links
                </div>


                <ul class="footer-links">

                    <li>
                        <a href="#home">Home</a>
                    </li>

                    <li>
                        <a href="#about">About</a>
                    </li>

                    <li>
                        <a href="#services">Services</a>
                    </li>

                    <li>
                        <a href="#gallery">Library Collection</a>
                    </li>

                </ul>

            </div>



            <div class="col-lg-4 col-md-6 mb-4">

                <div class="footer-title">
                    Portals
                </div>


                <ul class="footer-links">

                    <li>
                        <a href="user/index.php">
                            User Portal
                        </a>
                    </li>


                    <li>
                        <a href="staff/index.php">
                            Staff Portal
                        </a>
                    </li>


                    <li>
                        <a href="admin/indexad.php">
                            Admin Portal
                        </a>
                    </li>

                </ul>

            </div>

        </div>


        <div class="copyright">

            © <?php echo date("Y"); ?> Library Management System.
            All Rights Reserved.

        </div>

    </div>

</footer>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js">
</script>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const categoryFilter =
        document.getElementById("bookCategoryFilter");

    const bookItems =
        Array.from(
            document.querySelectorAll(".book-item")
        );

    const showMoreButton =
        document.getElementById("showMoreBooks");


    let expanded = false;


    function updateBooks() {

        const selectedCategory =
            categoryFilter.value;


        const matchedBooks =
            bookItems.filter(function (item) {

                if (selectedCategory === "all") {
                    return true;
                }

                return item.dataset.category === selectedCategory;

            });


        bookItems.forEach(function (item) {

            item.style.display = "none";

        });


        const visibleBooks =
            expanded
                ? matchedBooks
                : matchedBooks.slice(0, 6);


        visibleBooks.forEach(function (item) {

            item.style.display = "block";

        });


        if (showMoreButton) {

            if (matchedBooks.length > 6) {

                showMoreButton.style.display = "inline-block";

                if (expanded) {

                    showMoreButton.innerHTML =
                        '<i class="fa-solid fa-chevron-up mr-2"></i> Show Less';

                } else {

                    showMoreButton.innerHTML =
                        '<i class="fa-solid fa-chevron-down mr-2"></i> Show More Books';

                }

            } else {

                showMoreButton.style.display = "none";

            }

        }

    }


    if (categoryFilter) {

        categoryFilter.addEventListener(
            "change",
            function () {

                expanded = false;

                updateBooks();

            }
        );

    }


    if (showMoreButton) {

        showMoreButton.addEventListener(
            "click",
            function () {

                expanded = !expanded;

                updateBooks();

            }
        );

    }


    updateBooks();

});

</script>


</body>

</html>