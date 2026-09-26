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

/* =========================
   CATEGORY LIST
========================= */

$category_query = "
    SELECT cat_id, cat_name
    FROM category
    ORDER BY cat_name ASC
";

$category_result = mysqli_query($conn, $category_query);

$selected_category = isset($_GET["category"])
    ? (int) $_GET["category"]
    : 0;


/* =========================
   BOOK QUERY
   ONLY APPROVED BOOKS
========================= */

$book_query = "
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
";


/* =========================
   CATEGORY FILTER
========================= */

if ($selected_category > 0) {

    $book_query .= "
        AND books.cat_id = $selected_category
    ";
}


$book_query .= "
    ORDER BY books.book_id DESC
";


$book_result = mysqli_query($conn, $book_query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Browse Books - Library Management System</title>

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
           FILTER
        ========================= */

        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
        }

        .filter-card label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
        }

        .filter-card select {
            height: 45px;
            border-radius: 7px;
            border: 1px solid #d1d5db;
        }

        .filter-card select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.10);
        }

        .show-all {
            display: inline-block;
            margin-top: 10px;
            color: #2563eb;
            font-size: 13px;
            text-decoration: none;
        }

        .show-all:hover {
            text-decoration: none;
            color: #1d4ed8;
        }


        /* =========================
           BOOK CARD
        ========================= */

        .book-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            height: 100%;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
            transition: 0.2s;
        }

        .book-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(0,0,0,.08);
        }

        .book-icon {
            width: 55px;
            height: 55px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 18px;
        }

        .book-name {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .author {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .author i {
            margin-right: 5px;
        }

        .category {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            margin-bottom: 15px;
        }

        .book-info {
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            margin-top: 5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .info-label {
            color: #6b7280;
        }

        .info-value {
            color: #111827;
            font-weight: 600;
        }

        .price {
            color: #2563eb;
            font-size: 19px;
            font-weight: 700;
        }

        .pdf-available {
            color: #15803d;
            font-weight: 600;
        }

        .pdf-not {
            color: #9ca3af;
        }


        /* =========================
           BUTTONS
        ========================= */

        .book-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 18px;
        }

        .btn-read {
            background: #2563eb;
            color: white;
            padding: 10px;
            border-radius: 7px;
            text-align: center;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-read:hover {
            background: #1d4ed8;
            color: white;
            text-decoration: none;
        }

        .btn-order {
            background: #111827;
            color: white;
            padding: 10px;
            border-radius: 7px;
            text-align: center;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-order:hover {
            background: #1f2937;
            color: white;
            text-decoration: none;
        }

        .btn-disabled {
            background: #e5e7eb;
            color: #9ca3af;
            padding: 10px;
            border-radius: 7px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty-state {
            background: white;
            border-radius: 12px;
            text-align: center;
            padding: 70px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .empty-state h5 {
            color: #374151;
            font-weight: 700;
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


    <a href="dashboard.php" class="nav-link">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
    </a>


    <a href="books.php" class="nav-link active">
        <i class="fa-solid fa-book"></i>
        Browse Books
    </a>


    <a href="orders.php" class="nav-link">
        <i class="fa-solid fa-cart-shopping"></i>
        My Orders
    </a>


    <a href="issued_books.php" class="nav-link">
        <i class="fa-solid fa-book-open-reader"></i>
        Issued Books
    </a>


    <a href="profile.php" class="nav-link">
        <i class="fa-solid fa-user"></i>
        My Profile
    </a>


    <div class="logout">

        <a href="../logout.php" class="nav-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <div class="page-title">

            <h2>Browse Books</h2>

            <p>
                Explore available books and order your copy.
            </p>

        </div>

    </div>


    <!-- =========================
         CATEGORY FILTER
    ========================= -->

    <div class="filter-card">

        <form method="GET">

            <div class="row align-items-end">

                <div class="col-md-5">

                    <label>
                        Filter by Category
                    </label>

                    <select
                        name="category"
                        class="form-control"
                        onchange="this.form.submit()"
                    >

                        <option value="0">
                            All Categories
                        </option>

                        <?php while ($category = mysqli_fetch_assoc($category_result)): ?>

                            <option
                                value="<?php echo $category["cat_id"]; ?>"
                                <?php
                                if (
                                    $selected_category ==
                                    $category["cat_id"]
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $category["cat_name"]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

            </div>

        </form>


        <?php if ($selected_category > 0): ?>

            <a href="books.php" class="show-all">

                <i class="fa-solid fa-xmark"></i>
                Show All Books

            </a>

        <?php endif; ?>

    </div>


    <!-- =========================
         BOOK LIST
    ========================= -->

    <?php if ($book_result && mysqli_num_rows($book_result) > 0): ?>

        <div class="row">

            <?php while ($book = mysqli_fetch_assoc($book_result)): ?>

                <div class="col-lg-4 col-md-6 mb-4">

                    <div class="book-card">


                        <!-- ICON -->

                        <div class="book-icon">

                            <i class="fa-solid fa-book"></i>

                        </div>


                        <!-- BOOK NAME -->

                        <div class="book-name">

                            <?php
                            echo htmlspecialchars(
                                $book["book_name"]
                            );
                            ?>

                        </div>


                        <!-- AUTHOR -->

                        <div class="author">

                            <i class="fa-solid fa-pen-nib"></i>

                            <?php
                            echo htmlspecialchars(
                                $book["author_name"]
                            );
                            ?>

                        </div>


                        <!-- CATEGORY -->

                        <div class="category">

                            <i class="fa-solid fa-layer-group"></i>

                            <?php
                            echo htmlspecialchars(
                                $book["cat_name"]
                            );
                            ?>

                        </div>


                        <!-- BOOK INFO -->

                        <div class="book-info">


                            <div class="info-row">

                                <span class="info-label">
                                    Book Number
                                </span>

                                <span class="info-value">

                                    <?php
                                    echo $book["book_no"];
                                    ?>

                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Price
                                </span>

                                <span class="price">

                                    ৳<?php
                                    echo number_format(
                                        $book["book_price"]
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Digital PDF
                                </span>

                                <?php if (!empty($book["book_file"])): ?>

                                    <span class="pdf-available">

                                        <i class="fa-solid fa-circle-check"></i>
                                        Available

                                    </span>

                                <?php else: ?>

                                    <span class="pdf-not">

                                        Not Available

                                    </span>

                                <?php endif; ?>

                            </div>


                        </div>


                        <!-- ACTIONS -->

                        <div class="book-actions">


                            <?php if (!empty($book["book_file"])): ?>

                                <a
                                    href="read_book.php?id=<?php echo $book["book_id"]; ?>"
                                    class="btn-read"
                                >

                                    <i class="fa-solid fa-book-open"></i>
                                    Read Book

                                </a>

                            <?php else: ?>

                                <div class="btn-disabled">

                                    <i class="fa-solid fa-file-circle-xmark"></i>
                                    PDF Not Available

                                </div>

                            <?php endif; ?>


                            <a
                                href="order.php?id=<?php echo $book["book_id"]; ?>"
                                class="btn-order"
                            >

                                <i class="fa-solid fa-cart-shopping"></i>
                                Order Now

                            </a>


                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>


    <?php else: ?>


        <div class="empty-state">

            <i class="fa-solid fa-book-open"></i>

            <h5>
                No Books Available
            </h5>

            <p>
                There are currently no approved books in this category.
            </p>

        </div>


    <?php endif; ?>


</div>


</body>

</html>