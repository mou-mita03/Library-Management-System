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

$message = "";
$error = "";


/* =========================
   DELETE BOOK
========================= */

if (isset($_GET["delete"])) {

    $book_id = (int) $_GET["delete"];

    $file_query = mysqli_query(
        $conn,
        "SELECT book_file FROM books WHERE book_id = $book_id"
    );

    $file_data = mysqli_fetch_assoc($file_query);

    $delete_query = "DELETE FROM books WHERE book_id = $book_id";

    if (mysqli_query($conn, $delete_query)) {

        if (!empty($file_data["book_file"])) {

            $file_path = "../books/" . $file_data["book_file"];

            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        header("Location: manage_books.php?deleted=1");
        exit();

    } else {

        $error = "Failed to delete the book.";
    }
}


/* =========================
   APPROVE BOOK
   CREATE NEW AUTHOR/CATEGORY
========================= */

if (isset($_GET["approve"])) {

    $book_id = (int) $_GET["approve"];

    mysqli_begin_transaction($conn);

    try {

        /* Get pending book information */

        $book_query = "
            SELECT
                author_id,
                pending_author_name,
                cat_id,
                pending_category_name
            FROM books
            WHERE book_id = $book_id
            AND added_by_type = 'Staff'
            AND approval_status = 'Pending'
            LIMIT 1
        ";

        $book_result = mysqli_query($conn, $book_query);

        if (
            !$book_result ||
            mysqli_num_rows($book_result) == 0
        ) {
            throw new Exception(
                "Book not found or already processed."
            );
        }

        $book = mysqli_fetch_assoc($book_result);


        /* =========================
           AUTHOR
        ========================= */

        $author_id = !empty($book["author_id"])
            ? (int) $book["author_id"]
            : null;

        $pending_author_name = trim(
            (string) $book["pending_author_name"]
        );


        if (
            $author_id === null &&
            $pending_author_name !== ""
        ) {

            $author_name_db = mysqli_real_escape_string(
                $conn,
                $pending_author_name
            );


            /* Check existing author */

            $existing_author_query = "
                SELECT author_id
                FROM authors
                WHERE LOWER(author_name)
                      = LOWER('$author_name_db')
                LIMIT 1
            ";

            $existing_author_result = mysqli_query(
                $conn,
                $existing_author_query
            );


            if (
                $existing_author_result &&
                mysqli_num_rows(
                    $existing_author_result
                ) > 0
            ) {

                $existing_author =
                    mysqli_fetch_assoc(
                        $existing_author_result
                    );

                $author_id =
                    (int) $existing_author["author_id"];

            } else {

                /* Create new author */

                $insert_author_query = "
                    INSERT INTO authors
                    (author_name)
                    VALUES
                    ('$author_name_db')
                ";

                if (
                    !mysqli_query(
                        $conn,
                        $insert_author_query
                    )
                ) {
                    throw new Exception(
                        "Failed to create new author."
                    );
                }

                $author_id =
                    mysqli_insert_id($conn);
            }
        }


        /* =========================
           CATEGORY
        ========================= */

        $cat_id = !empty($book["cat_id"])
            ? (int) $book["cat_id"]
            : null;

        $pending_category_name = trim(
            (string) $book["pending_category_name"]
        );


        if (
            $cat_id === null &&
            $pending_category_name !== ""
        ) {

            $category_name_db =
                mysqli_real_escape_string(
                    $conn,
                    $pending_category_name
                );


            /* Check existing category */

            $existing_category_query = "
                SELECT cat_id
                FROM category
                WHERE LOWER(cat_name)
                      = LOWER('$category_name_db')
                LIMIT 1
            ";

            $existing_category_result =
                mysqli_query(
                    $conn,
                    $existing_category_query
                );


            if (
                $existing_category_result &&
                mysqli_num_rows(
                    $existing_category_result
                ) > 0
            ) {

                $existing_category =
                    mysqli_fetch_assoc(
                        $existing_category_result
                    );

                $cat_id =
                    (int) $existing_category["cat_id"];

            } else {

                /* Create new category */

                $insert_category_query = "
                    INSERT INTO category
                    (cat_name)
                    VALUES
                    ('$category_name_db')
                ";

                if (
                    !mysqli_query(
                        $conn,
                        $insert_category_query
                    )
                ) {
                    throw new Exception(
                        "Failed to create new category."
                    );
                }

                $cat_id =
                    mysqli_insert_id($conn);
            }
        }


        /* =========================
           FINAL VALIDATION
        ========================= */

        if ($author_id === null) {

            throw new Exception(
                "Author information is missing."
            );
        }


        if ($cat_id === null) {

            throw new Exception(
                "Category information is missing."
            );
        }


        /* =========================
           APPROVE BOOK
        ========================= */

        $approve_query = "
            UPDATE books
            SET
                author_id = $author_id,
                pending_author_name = NULL,

                cat_id = $cat_id,
                pending_category_name = NULL,

                approval_status = 'Approved',
                rejection_reason = NULL

            WHERE book_id = $book_id
            AND added_by_type = 'Staff'
            AND approval_status = 'Pending'
        ";


        if (!mysqli_query($conn, $approve_query)) {

            throw new Exception(
                "Failed to approve the book."
            );
        }


        mysqli_commit($conn);

        header(
            "Location: manage_books.php?approved=1"
        );

        exit();

    } catch (Exception $e) {

        mysqli_rollback($conn);

        $error = $e->getMessage();
    }
}


/* =========================
   REJECT BOOK
========================= */

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST["reject_book"])
) {

    $book_id = (int) $_POST["book_id"];

    $rejection_reason =
        trim($_POST["rejection_reason"]);


    if ($rejection_reason == "") {

        $error =
            "Please enter a rejection reason.";

    } else {

        $reason =
            mysqli_real_escape_string(
                $conn,
                $rejection_reason
            );


        $update_query = "
            UPDATE books
            SET
                approval_status = 'Rejected',
                rejection_reason = '$reason'
            WHERE book_id = $book_id
            AND added_by_type = 'Staff'
            AND approval_status = 'Pending'
        ";


        if (mysqli_query($conn, $update_query)) {

            header(
                "Location: manage_books.php?rejected=1"
            );

            exit();

        } else {

            $error =
                "Failed to reject the book.";
        }
    }
}


/* =========================
   SUCCESS MESSAGES
========================= */

if (isset($_GET["deleted"])) {
    $message =
        "Book deleted successfully.";
}

if (isset($_GET["approved"])) {
    $message =
        "Book approved successfully. New Author/Category were added if required.";
}

if (isset($_GET["rejected"])) {
    $message =
        "Book rejected successfully.";
}


/* =========================
   ADMIN PROFILE
========================= */

$admin_id =
    (int) $_SESSION["admin_id"];


$admin_query = "
    SELECT
        name,
        email,
        profile_image
    FROM admins
    WHERE id = $admin_id
    LIMIT 1
";


$admin_result =
    mysqli_query(
        $conn,
        $admin_query
    );


$admin =
    mysqli_fetch_assoc(
        $admin_result
    );


/* =========================
   PENDING COUNT
========================= */

$pending_query = "
    SELECT COUNT(*) AS total
    FROM books
    WHERE added_by_type = 'Staff'
    AND approval_status = 'Pending'
";


$pending_result =
    mysqli_query(
        $conn,
        $pending_query
    );


$pending_count =
    mysqli_fetch_assoc(
        $pending_result
    )["total"];


/* =========================
   ALL BOOKS
========================= */

$query = "
    SELECT

        books.book_id,
        books.book_name,
        books.book_no,
        books.book_price,
        books.book_file,
        books.pdf_comment,

        books.added_by_type,
        books.approval_status,

        books.rejection_reason,

        books.pending_author_name,
        books.pending_category_name,

        authors.author_name,
        category.cat_name,

        staffs.name AS staff_name

    FROM books

    LEFT JOIN authors
        ON books.author_id = authors.author_id

    LEFT JOIN category
        ON books.cat_id = category.cat_id

    LEFT JOIN staffs
        ON books.added_by = staffs.id

    ORDER BY books.book_id DESC
";


$result =
    mysqli_query(
        $conn,
        $query
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

    <title>
        Manage Books - Admin Panel
    </title>


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
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

            font-family: Arial, sans-serif;

            background: #f4f6f9;

            color: #1f2937;
        }


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
            margin-top: 18px;

            padding-top: 12px;

            border-top:
                1px solid #374151;
        }


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

            font-size: 25px;

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


        .admin-avatar {
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


        .admin-avatar img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        .top-actions {
            display: flex;

            gap: 10px;

            margin-bottom: 20px;
        }


        .btn-add {
            background: #2563eb;

            color: white;

            padding: 10px 16px;

            border-radius: 7px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;
        }


        .btn-add:hover {
            background: #1d4ed8;

            color: white;

            text-decoration: none;
        }


        .btn-approval {
            background: #fff7ed;

            color: #c2410c;

            padding: 10px 16px;

            border-radius: 7px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            border:
                1px solid #fed7aa;
        }


        .btn-approval:hover {
            background: #ffedd5;

            color: #9a3412;

            text-decoration: none;
        }


        .book-card {
            background: white;

            border-radius: 12px;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);

            overflow: hidden;
        }


        .table-container {
            padding: 20px 25px 25px;
        }


        .table th {
            border-top: none;

            color: #6b7280;

            font-size: 12px;

            text-transform: uppercase;

            white-space: nowrap;
        }


        .table td {
            vertical-align: middle;

            font-size: 14px;
        }


        .book-name {
            font-weight: 700;

            color: #111827;
        }


        .source-badge {
            display: inline-block;

            margin-top: 4px;

            font-size: 11px;

            color: #6b7280;
        }


        .new-info {
            display: block;

            margin-top: 4px;

            font-size: 11px;

            color: #2563eb;

            font-weight: 600;
        }


        .status {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 11px;

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


        .pdf-available {
            color: #15803d;

            font-weight: 600;
        }


        .pdf-view {
            display: inline-block;

            margin-top: 6px;

            color: #dc2626;

            font-weight: 600;

            text-decoration: none;

            font-size: 12px;
        }


        .pdf-view:hover {
            color: #b91c1c;

            text-decoration: none;
        }


        .pdf-note {
            display: block;

            margin-top: 6px;

            color: #6b7280;

            font-size: 11px;

            line-height: 1.4;

            max-width: 180px;
        }


        .pdf-unavailable {
            color: #9ca3af;

            font-weight: 600;

            font-size: 12px;
        }


        .action-buttons {
            display: flex;

            flex-direction: column;

            gap: 6px;

            min-width: 160px;
        }


        .btn-edit {
            background: #eff6ff;

            color: #2563eb;

            padding: 7px 10px;

            border-radius: 6px;

            text-align: center;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;
        }


        .btn-edit:hover {
            background: #dbeafe;

            color: #1d4ed8;

            text-decoration: none;
        }


        .btn-delete {
            background: #fef2f2;

            color: #dc2626;

            padding: 7px 10px;

            border-radius: 6px;

            text-align: center;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;
        }


        .btn-delete:hover {
            background: #fee2e2;

            color: #b91c1c;

            text-decoration: none;
        }


        .btn-approve {
            background: #ecfdf5;

            color: #15803d;

            border: none;

            padding: 7px 10px;

            border-radius: 6px;

            font-size: 12px;

            font-weight: 600;

            text-decoration: none;

            text-align: center;
        }


        .btn-approve:hover {
            background: #dcfce7;

            color: #166534;

            text-decoration: none;
        }


        .reject-form {
            margin-top: 5px;
        }


        .reject-form textarea {
            width: 100%;

            min-height: 65px;

            border:
                1px solid #d1d5db;

            border-radius: 6px;

            padding: 7px;

            font-size: 12px;

            resize: vertical;

            margin-bottom: 5px;
        }


        .btn-reject {
            width: 100%;

            background: #fef2f2;

            color: #dc2626;

            border: none;

            padding: 7px 10px;

            border-radius: 6px;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;
        }


        .btn-reject:hover {
            background: #fee2e2;

            color: #b91c1c;
        }


        .rejection-note {
            margin-top: 5px;

            color: #dc2626;

            font-size: 11px;

            max-width: 150px;
        }


        .empty-state {
            text-align: center;

            padding: 70px 20px;

            color: #9ca3af;
        }


        .empty-state i {
            font-size: 45px;

            margin-bottom: 15px;
        }


        .alert {
            border-radius: 8px;
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

            .page-title h2 {
                font-size: 22px;
            }

            .table-container {
                overflow-x: auto;
                padding: 15px 12px;
            }

            .top-actions {
                flex-direction: column;
            }

            .btn-add, .btn-approval {
                width: 100%;
                text-align: center;
            }

        }

    </style>

</head>


<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <div class="brand">

        <i class="fa-solid fa-book-open"></i>

        Library Admin

    </div>


    <div class="menu-title">

        Main Menu

    </div>


    <a
        href="dashboard.php"
        class="nav-link"
    >

        <i class="fa-solid fa-chart-line"></i>

        Dashboard

    </a>


    <a
        href="manage_books.php"
        class="nav-link active"
    >

        <i class="fa-solid fa-book"></i>

        Manage Books

    </a>


    <a
        href="book_approvals.php"
        class="nav-link"
    >

        <i class="fa-solid fa-circle-check"></i>

        Book Approvals

        <?php if ($pending_count > 0): ?>

            <span class="badge badge-warning ml-1">

                <?php
                echo $pending_count;
                ?>

            </span>

        <?php endif; ?>

    </a>


    <a
        href="manage_users.php"
        class="nav-link"
    >

        <i class="fa-solid fa-users"></i>

        Manage Users

    </a>


    <a
        href="staffs.php"
        class="nav-link"
    >

        <i class="fa-solid fa-user-tie"></i>

        Staff Management

    </a>


    <a
        href="orders.php"
        class="nav-link"
    >

        <i class="fa-solid fa-cart-shopping"></i>

        Orders

    </a>


    <a
        href="authors.php"
        class="nav-link"
    >

        <i class="fa-solid fa-pen-nib"></i>

        Authors

    </a>


    <a
        href="categories.php"
        class="nav-link"
    >

        <i class="fa-solid fa-layer-group"></i>

        Categories

    </a>


    <a
        href="issued_books.php"
        class="nav-link"
    >

        <i
            class="fa-solid fa-book-open-reader"
        ></i>

        Issued Books

    </a>


    <a
        href="profile.php"
        class="nav-link"
    >

        <i class="fa-solid fa-user"></i>

        Profile

    </a>


    <div class="logout">

        <a
            href="admin_logout.php"
            class="nav-link"
        >

            <i
                class="fa-solid fa-right-from-bracket"
            ></i>

            Logout

        </a>

    </div>


</div>


<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" onclick="document.querySelector('.sidebar').classList.remove('show'); this.classList.remove('show');"></div>

<!-- MAIN -->

<div class="main">


    <div class="topbar">


        <div class="page-title d-flex align-items-center">

            <button type="button" class="sidebar-toggle-btn mr-3" onclick="document.querySelector('.sidebar').classList.toggle('show'); document.querySelector('.sidebar-overlay').classList.toggle('show');">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div>
                <h2 class="mb-0">
                    Manage Books
                </h2>

                <p class="mb-0">
                    Manage library books and staff submissions.
                </p>
            </div>

        </div>


        <div class="admin-info">


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
                        alt="Admin"
                    >


                <?php else: ?>


                    <i class="fa-solid fa-user"></i>


                <?php endif; ?>


            </div>


        </div>


    </div>


    <!-- MESSAGES -->

    <?php if ($message != ""): ?>

        <div class="alert alert-success">

            <i
                class="fa-solid fa-circle-check"
            ></i>

            <?php
            echo htmlspecialchars(
                $message
            );
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error != ""): ?>

        <div class="alert alert-danger">

            <i
                class="fa-solid fa-circle-exclamation"
            ></i>

            <?php
            echo htmlspecialchars(
                $error
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- TOP ACTIONS -->

    <div class="top-actions">


        <a
            href="add_book.php"
            class="btn-add"
        >

            <i class="fa-solid fa-plus"></i>

            Add New Book

        </a>


        <?php if ($pending_count > 0): ?>

            <a
                href="book_approvals.php"
                class="btn-approval"
            >

                <i class="fa-solid fa-clock"></i>

                <?php echo $pending_count; ?>

                Pending Approval

            </a>

        <?php endif; ?>


    </div>


    <!-- BOOK TABLE -->

    <div class="book-card">


        <?php if (
            $result &&
            mysqli_num_rows($result) > 0
        ): ?>


            <div class="table-container">


                <div class="table-responsive">


                    <table class="table">


                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Book Name</th>

                                <th>Author</th>

                                <th>Category</th>

                                <th>Book No.</th>

                                <th>Price</th>

                                <th>PDF</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php $count = 1; ?>


                        <?php while (
                            $book =
                            mysqli_fetch_assoc(
                                $result
                            )
                        ): ?>


                            <tr>


                                <!-- NUMBER -->

                                <td>

                                    <?php
                                    echo $count++;
                                    ?>

                                </td>


                                <!-- BOOK -->

                                <td>


                                    <div class="book-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $book["book_name"]
                                        );
                                        ?>

                                    </div>


                                    <?php if (
                                        $book[
                                            "added_by_type"
                                        ]
                                        == "Staff"
                                    ): ?>


                                        <span
                                            class="source-badge"
                                        >

                                            Added by Staff:

                                            <?php
                                            echo htmlspecialchars(
                                                $book[
                                                    "staff_name"
                                                ]
                                                ?? "Unknown"
                                            );
                                            ?>

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="source-badge"
                                        >

                                            Added by Admin

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- AUTHOR -->

                                <td>


                                    <?php if (
                                        $book[
                                            "approval_status"
                                        ]
                                        == "Pending"
                                        &&
                                        !empty(
                                            $book[
                                                "pending_author_name"
                                            ]
                                        )
                                    ): ?>


                                        <span
                                            class="new-info"
                                        >

                                            <i
                                                class="fa-solid fa-user-plus"
                                            ></i>

                                            New Author:

                                            <?php
                                            echo htmlspecialchars(
                                                $book[
                                                    "pending_author_name"
                                                ]
                                            );
                                            ?>

                                        </span>


                                    <?php else: ?>


                                        <?php
                                        echo htmlspecialchars(
                                            $book[
                                                "author_name"
                                            ]
                                            ?? "Not Assigned"
                                        );
                                        ?>


                                    <?php endif; ?>


                                </td>


                                <!-- CATEGORY -->

                                <td>


                                    <?php if (
                                        $book[
                                            "approval_status"
                                        ]
                                        == "Pending"
                                        &&
                                        !empty(
                                            $book[
                                                "pending_category_name"
                                            ]
                                        )
                                    ): ?>


                                        <span
                                            class="new-info"
                                        >

                                            <i
                                                class="fa-solid fa-layer-group"
                                            ></i>

                                            New Category:

                                            <?php
                                            echo htmlspecialchars(
                                                $book[
                                                    "pending_category_name"
                                                ]
                                            );
                                            ?>

                                        </span>


                                    <?php else: ?>


                                        <?php
                                        echo htmlspecialchars(
                                            $book[
                                                "cat_name"
                                            ]
                                            ?? "Not Assigned"
                                        );
                                        ?>


                                    <?php endif; ?>


                                </td>


                                <!-- BOOK NO -->

                                <td>

                                    <?php
                                    echo $book["book_no"];
                                    ?>

                                </td>


                                <!-- PRICE -->

                                <td>

                                    ৳<?php
                                    echo number_format(
                                        $book["book_price"]
                                    );
                                    ?>

                                </td>


                                <!-- PDF -->

                                <td>


                                    <?php if (
                                        !empty(
                                            $book["book_file"]
                                        )
                                    ): ?>


                                        <div
                                            class="pdf-available"
                                        >

                                            <i
                                                class="fa-solid fa-circle-check"
                                            ></i>

                                            Available

                                        </div>


                                        <a
                                            href="view_pdf.php?id=<?php
                                            echo $book[
                                                "book_id"
                                            ];
                                            ?>"
                                            class="pdf-view"
                                        >

                                            <i
                                                class="fa-solid fa-file-pdf"
                                            ></i>

                                            View PDF

                                        </a>


                                        <?php if (
                                            !empty(
                                                $book[
                                                    "pdf_comment"
                                                ]
                                            )
                                        ): ?>


                                            <span
                                                class="pdf-note"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $book[
                                                        "pdf_comment"
                                                    ]
                                                );
                                                ?>

                                            </span>


                                        <?php endif; ?>


                                    <?php else: ?>


                                        <div
                                            class="pdf-unavailable"
                                        >

                                            <i
                                                class="fa-solid fa-circle-xmark"
                                            ></i>

                                            Not Available

                                        </div>


                                        <?php if (
                                            !empty(
                                                $book[
                                                    "pdf_comment"
                                                ]
                                            )
                                        ): ?>


                                            <span
                                                class="pdf-note"
                                            >

                                                <i
                                                    class="fa-solid fa-circle-info"
                                                ></i>

                                                <?php
                                                echo htmlspecialchars(
                                                    $book[
                                                        "pdf_comment"
                                                    ]
                                                );
                                                ?>

                                            </span>


                                        <?php endif; ?>


                                    <?php endif; ?>


                                </td>


                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $book[
                                            "approval_status"
                                        ]
                                        == "Pending"
                                    ): ?>


                                        <span
                                            class="status status-pending"
                                        >

                                            Pending

                                        </span>


                                    <?php elseif (
                                        $book[
                                            "approval_status"
                                        ]
                                        == "Rejected"
                                    ): ?>


                                        <span
                                            class="status status-rejected"
                                        >

                                            Rejected

                                        </span>


                                        <?php if (
                                            !empty(
                                                $book[
                                                    "rejection_reason"
                                                ]
                                            )
                                        ): ?>


                                            <div
                                                class="rejection-note"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $book[
                                                        "rejection_reason"
                                                    ]
                                                );
                                                ?>

                                            </div>


                                        <?php endif; ?>


                                    <?php else: ?>


                                        <span
                                            class="status status-approved"
                                        >

                                            Approved

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- ACTION -->

                                <td>


                                    <?php if (
                                        $book[
                                            "added_by_type"
                                        ]
                                        == "Staff"
                                        &&
                                        $book[
                                            "approval_status"
                                        ]
                                        == "Pending"
                                    ): ?>


                                        <div
                                            class="action-buttons"
                                        >


                                            <a
                                                href="manage_books.php?approve=<?php
                                                echo $book[
                                                    "book_id"
                                                ];
                                                ?>"
                                                class="btn-approve"
                                                onclick="
                                                return confirm(
                                                    'Approve this book? Any new Author and Category will also be created.'
                                                );
                                                "
                                            >

                                                <i
                                                    class="fa-solid fa-check"
                                                ></i>

                                                Approve

                                            </a>


                                            <form
                                                method="POST"
                                                class="reject-form"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="book_id"
                                                    value="<?php
                                                    echo $book[
                                                        "book_id"
                                                    ];
                                                    ?>"
                                                >


                                                <textarea
                                                    name="rejection_reason"
                                                    placeholder="Write rejection reason..."
                                                    required
                                                ></textarea>


                                                <button
                                                    type="submit"
                                                    name="reject_book"
                                                    class="btn-reject"
                                                    onclick="
                                                    return confirm(
                                                        'Reject this book?'
                                                    );
                                                    "
                                                >

                                                    <i
                                                        class="fa-solid fa-xmark"
                                                    ></i>

                                                    Reject

                                                </button>


                                            </form>


                                        </div>


                                    <?php else: ?>


                                        <div
                                            class="action-buttons"
                                        >


                                            <a
                                                href="edit_book.php?id=<?php
                                                echo $book[
                                                    "book_id"
                                                ];
                                                ?>"
                                                class="btn-edit"
                                            >

                                                <i
                                                    class="fa-solid fa-pen"
                                                ></i>

                                                Edit

                                            </a>


                                            <a
                                                href="manage_books.php?delete=<?php
                                                echo $book[
                                                    "book_id"
                                                ];
                                                ?>"
                                                class="btn-delete"
                                                onclick="
                                                return confirm(
                                                    'Are you sure you want to delete this book?'
                                                );
                                                "
                                            >

                                                <i
                                                    class="fa-solid fa-trash"
                                                ></i>

                                                Delete

                                            </a>


                                        </div>


                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>

            </div>


        <?php else: ?>


            <div class="empty-state">

                <i
                    class="fa-solid fa-book-open"
                ></i>

                <h5>
                    No Books Found
                </h5>

                <p>
                    There are no books in the system.
                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>