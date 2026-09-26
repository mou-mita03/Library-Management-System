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
   APPROVE BOOK
========================= */

if (isset($_GET["approve"])) {

    $book_id = (int) $_GET["approve"];

    mysqli_begin_transaction($conn);

    try {

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

        if (!$book_result || mysqli_num_rows($book_result) == 0) {
            throw new Exception("Book not found or already processed.");
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


        if ($author_id === null && $pending_author_name !== "") {

            $author_name_db = mysqli_real_escape_string(
                $conn,
                $pending_author_name
            );

            $existing_author_query = "
                SELECT author_id
                FROM authors
                WHERE LOWER(author_name) = LOWER('$author_name_db')
                LIMIT 1
            ";

            $existing_author_result = mysqli_query(
                $conn,
                $existing_author_query
            );

            if (
                $existing_author_result &&
                mysqli_num_rows($existing_author_result) > 0
            ) {

                $existing_author = mysqli_fetch_assoc(
                    $existing_author_result
                );

                $author_id = (int) $existing_author["author_id"];

            } else {

                $insert_author_query = "
                    INSERT INTO authors (author_name)
                    VALUES ('$author_name_db')
                ";

                if (!mysqli_query($conn, $insert_author_query)) {
                    throw new Exception("Failed to create new author.");
                }

                $author_id = mysqli_insert_id($conn);
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


        if ($cat_id === null && $pending_category_name !== "") {

            $category_name_db = mysqli_real_escape_string(
                $conn,
                $pending_category_name
            );

            $existing_category_query = "
                SELECT cat_id
                FROM category
                WHERE LOWER(cat_name) = LOWER('$category_name_db')
                LIMIT 1
            ";

            $existing_category_result = mysqli_query(
                $conn,
                $existing_category_query
            );

            if (
                $existing_category_result &&
                mysqli_num_rows($existing_category_result) > 0
            ) {

                $existing_category = mysqli_fetch_assoc(
                    $existing_category_result
                );

                $cat_id = (int) $existing_category["cat_id"];

            } else {

                $insert_category_query = "
                    INSERT INTO category (cat_name)
                    VALUES ('$category_name_db')
                ";

                if (!mysqli_query($conn, $insert_category_query)) {
                    throw new Exception("Failed to create new category.");
                }

                $cat_id = mysqli_insert_id($conn);
            }
        }


        /* =========================
           VALIDATION
        ========================= */

        if ($author_id === null) {
            throw new Exception("Author information is missing.");
        }

        if ($cat_id === null) {
            throw new Exception("Category information is missing.");
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
            throw new Exception("Failed to approve the book.");
        }


        mysqli_commit($conn);

        header("Location: book_approvals.php?approved=1");
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

    $rejection_reason = trim(
        $_POST["rejection_reason"]
    );

    if ($rejection_reason == "") {

        $error = "Please enter a rejection reason.";

    } else {

        $reason = mysqli_real_escape_string(
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

            header("Location: book_approvals.php?rejected=1");
            exit();

        } else {

            $error = "Failed to reject the book.";
        }
    }
}


/* =========================
   MESSAGES
========================= */

if (isset($_GET["approved"])) {
    $message = "Book approved successfully. New Author/Category were added if required.";
}

if (isset($_GET["rejected"])) {
    $message = "Book rejected successfully.";
}


/* =========================
   ADMIN INFO
========================= */

$admin_id = (int) $_SESSION["admin_id"];

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
$admin = mysqli_fetch_assoc($admin_result);


/* =========================
   PENDING BOOKS
========================= */

$query = "
    SELECT
        books.book_id,
        books.book_name,
        books.book_no,
        books.book_price,
        books.book_file,

        books.pending_author_name,
        books.pending_category_name,

        authors.author_name,
        category.cat_name,

        staffs.name AS staff_name,
        staffs.email AS staff_email

    FROM books

    LEFT JOIN authors
        ON books.author_id = authors.author_id

    LEFT JOIN category
        ON books.cat_id = category.cat_id

    LEFT JOIN staffs
        ON books.added_by = staffs.id

    WHERE books.added_by_type = 'Staff'
    AND books.approval_status = 'Pending'

    ORDER BY books.book_id DESC
";

$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Book Approvals - Admin Panel</title>

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
            background: #f4f6f9;
            font-family: Arial, sans-serif;
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
            margin-top: 18px;
            padding-top: 12px;
            border-top: 1px solid #374151;
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

        .approval-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
            overflow: hidden;
        }

        .card-header-custom {
            padding: 20px 25px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header-custom h5 {
            margin: 0;
            font-weight: 700;
        }

        .pending-count {
            background: #fff7ed;
            color: #c2410c;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

        .staff-name {
            font-weight: 600;
            color: #374151;
        }

        .staff-email {
            font-size: 12px;
            color: #9ca3af;
        }

        .new-info {
            color: #2563eb;
            font-weight: 600;
            font-size: 12px;
        }

        .pdf-btn {
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
        }

        .pdf-btn:hover {
            color: #b91c1c;
            text-decoration: none;
        }

        .btn-approve {
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .btn-approve:hover {
            background: #15803d;
            color: white;
        }

        .reject-box textarea {
            width: 220px;
            min-height: 70px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px;
            font-size: 12px;
            resize: vertical;
            margin-bottom: 6px;
        }

        .btn-reject {
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-reject:hover {
            background: #b91c1c;
        }

        .empty {
            padding: 70px 20px;
            text-align: center;
            color: #9ca3af;
        }

        .empty i {
            font-size: 45px;
            color: #16a34a;
            margin-bottom: 15px;
        }

        @media(max-width: 768px) {

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

            .table-container {
                overflow-x: auto;
            }

        }

    </style>

</head>

<body>


<div class="sidebar">

    <div class="brand">
        <i class="fa-solid fa-book-open"></i>
        Library Admin
    </div>

    <div class="menu-title">
        Main Menu
    </div>

    <a href="dashboard.php" class="nav-link">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
    </a>

    <a href="manage_books.php" class="nav-link">
        <i class="fa-solid fa-book"></i>
        Manage Books
    </a>

    <a href="book_approvals.php" class="nav-link active">
        <i class="fa-solid fa-circle-check"></i>
        Book Approvals
    </a>

    <a href="manage_users.php" class="nav-link">
        <i class="fa-solid fa-users"></i>
        Manage Users
    </a>

    <a href="staffs.php" class="nav-link">
        <i class="fa-solid fa-user-tie"></i>
        Staff Management
    </a>

    <a href="orders.php" class="nav-link">
        <i class="fa-solid fa-cart-shopping"></i>
        Orders
    </a>

    <a href="authors.php" class="nav-link">
        <i class="fa-solid fa-pen-nib"></i>
        Authors
    </a>

    <a href="categories.php" class="nav-link">
        <i class="fa-solid fa-layer-group"></i>
        Categories
    </a>

    <a href="issued_books.php" class="nav-link">
        <i class="fa-solid fa-book-open-reader"></i>
        Issued Books
    </a>

    <a href="profile.php" class="nav-link">
        <i class="fa-solid fa-user"></i>
        Profile
    </a>

    <div class="logout">

        <a href="admin_logout.php" class="nav-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</div>


<div class="main">

    <div class="topbar">

        <div class="page-title">

            <h2>
                Book Approvals
            </h2>

            <p>
                Review books submitted by library staff.
            </p>

        </div>


        <div class="admin-info">

            <span>
                <?php
                echo htmlspecialchars($admin["name"]);
                ?>
            </span>

            <div class="admin-avatar">

                <?php if (!empty($admin["profile_image"])): ?>

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


    <?php if ($message != ""): ?>

        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <?php if ($error != ""): ?>

        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="approval-card">

        <div class="card-header-custom">

            <h5>
                <i class="fa-solid fa-clock"></i>
                Pending Book Submissions
            </h5>

            <span class="pending-count">
                <?php echo mysqli_num_rows($result); ?>
                Pending
            </span>

        </div>


        <?php if ($result && mysqli_num_rows($result) > 0): ?>

            <div class="table-container">

                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>
                                <th>Book</th>
                                <th>Staff</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Book No.</th>
                                <th>Price</th>
                                <th>PDF</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php while ($book = mysqli_fetch_assoc($result)): ?>

                            <tr>

                                <td>

                                    <div class="book-name">
                                        <?php
                                        echo htmlspecialchars(
                                            $book["book_name"]
                                        );
                                        ?>
                                    </div>

                                </td>


                                <td>

                                    <div class="staff-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $book["staff_name"]
                                        );
                                        ?>

                                    </div>

                                    <div class="staff-email">

                                        <?php
                                        echo htmlspecialchars(
                                            $book["staff_email"]
                                        );
                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $book["pending_author_name"]
                                        )
                                    ): ?>

                                        <span class="new-info">

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
                                            $book["author_name"]
                                            ?? "Not Assigned"
                                        );
                                        ?>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $book["pending_category_name"]
                                        )
                                    ): ?>

                                        <span class="new-info">

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
                                            $book["cat_name"]
                                            ?? "Not Assigned"
                                        );
                                        ?>

                                    <?php endif; ?>

                                </td>


                                <td>
                                    <?php echo $book["book_no"]; ?>
                                </td>


                                <td>
                                    ৳<?php
                                    echo number_format(
                                        $book["book_price"]
                                    );
                                    ?>
                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $book["book_file"]
                                        )
                                    ): ?>

                                        <a
                                            href="view_pdf.php?id=<?php
                                            echo $book["book_id"];
                                            ?>"
                                            class="pdf-btn"
                                        >

                                            <i
                                                class="fa-solid fa-file-pdf"
                                            ></i>

                                            View PDF

                                        </a>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            No PDF
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td style="min-width:240px;">

                                    <a
                                        href="book_approvals.php?approve=<?php
                                        echo $book["book_id"];
                                        ?>"
                                        class="btn btn-approve btn-sm"
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
                                        class="reject-box"
                                    >

                                        <input
                                            type="hidden"
                                            name="book_id"
                                            value="<?php
                                            echo $book["book_id"];
                                            ?>"
                                        >

                                        <textarea
                                            name="rejection_reason"
                                            placeholder="Reason for rejection"
                                            required
                                        ></textarea>

                                        <br>

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

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php else: ?>

            <div class="empty">

                <i class="fa-solid fa-circle-check"></i>

                <h5>
                    No Pending Books
                </h5>

                <p>
                    There are currently no books waiting for approval.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>


</body>

</html>