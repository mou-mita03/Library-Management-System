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


/* ================= ADD AUTHOR ================= */

if (isset($_POST["add_author"])) {

    $author_name = trim($_POST["author_name"]);

    if ($author_name != "") {

        $check = mysqli_query(
            $conn,
            "SELECT author_id
             FROM authors
             WHERE author_name = '" . mysqli_real_escape_string($conn, $author_name) . "'"
        );

        if (mysqli_num_rows($check) > 0) {

            header("Location: authors.php?exists=1");
            exit();

        } else {

            $safe_name = mysqli_real_escape_string(
                $conn,
                $author_name
            );

            mysqli_query(
                $conn,
                "INSERT INTO authors (author_name)
                 VALUES ('$safe_name')"
            );

            header("Location: authors.php?added=1");
            exit();
        }
    }
}


/* ================= DELETE AUTHOR ================= */

if (isset($_GET["delete"])) {

    $author_id = (int) $_GET["delete"];


    /* Check whether author is being used by any book */

    $check_books = mysqli_query(
        $conn,
        "SELECT book_id
         FROM books
         WHERE author_id = $author_id"
    );


    if (mysqli_num_rows($check_books) > 0) {

        header("Location: authors.php?used=1");
        exit();

    }


    mysqli_query(
        $conn,
        "DELETE FROM authors
         WHERE author_id = $author_id"
    );


    header("Location: authors.php?deleted=1");
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


/* ================= GET AUTHORS ================= */

$authors_query = mysqli_query(
    $conn,
    "SELECT author_id, author_name
     FROM authors
     ORDER BY author_id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Authors - Library Management System</title>


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


        /* Menu */

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


        /* ================= CARDS ================= */

        .content-card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }


        .card-title {
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
        }


        .card-title i {
            margin-right: 8px;
        }


        /* Add Author */

        .form-control {
            height: 46px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
        }


        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }


        .add-btn {
            height: 46px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0 22px;
            font-weight: 600;
        }


        .add-btn:hover {
            background: #1d4ed8;
            color: white;
        }


        /* Table */

        table {
            vertical-align: middle !important;
        }


        thead th {
            background: #f3f4f6 !important;
            color: #374151;
            font-size: 13px;
        }


        tbody td {
            font-size: 14px;
        }


        .author-name {
            font-weight: 600;
        }


        .author-id {
            color: #6b7280;
        }


        /* Action */

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 7px;
            text-decoration: none;
        }


        .delete-btn {
            background: #fee2e2;
            color: #dc2626;
        }


        .delete-btn:hover {
            background: #fecaca;
            color: #b91c1c;
        }


        /* Empty */

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }


        .empty-state i {
            font-size: 45px;
            color: #9ca3af;
            margin-bottom: 15px;
        }


        /* Mobile */

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


            .add-form {
                flex-direction: column;
            }


            .add-btn {
                width: 100%;
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


    <a href="authors.php" class="active">

        <i class="fa-solid fa-user-pen"></i>

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
                Authors
            </h2>

            <p>
                Add and manage book authors
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

    <?php if (isset($_GET["added"])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="fa-solid fa-circle-check"></i>

            Author added successfully.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET["deleted"])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="fa-solid fa-circle-check"></i>

            Author deleted successfully.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET["exists"])): ?>

        <div class="alert alert-warning alert-dismissible fade show">

            <i class="fa-solid fa-triangle-exclamation"></i>

            This author already exists.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET["used"])): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="fa-solid fa-circle-exclamation"></i>

            This author cannot be deleted because one or more books
            are using this author.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- ================= ADD AUTHOR ================= -->

    <div class="content-card">


        <div class="card-title">

            <i class="fa-solid fa-user-plus"></i>

            Add New Author

        </div>


        <form
            method="POST"
            class="row g-3 add-form">


            <div class="col-md-9">

                <input
                    type="text"
                    name="author_name"
                    class="form-control"
                    placeholder="Enter author name"
                    required>

            </div>


            <div class="col-md-3">

                <button
                    type="submit"
                    name="add_author"
                    class="add-btn w-100">

                    <i class="fa-solid fa-plus"></i>

                    Add Author

                </button>

            </div>


        </form>

    </div>


    <!-- ================= AUTHORS LIST ================= -->

    <div class="content-card">


        <div class="card-title">

            <i class="fa-solid fa-users"></i>

            All Authors

        </div>


        <?php if ($authors_query && mysqli_num_rows($authors_query) > 0): ?>


            <div class="table-responsive">


                <table class="table table-hover">


                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Author ID</th>

                            <th>Author Name</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    $serial = 1;

                    while ($author = mysqli_fetch_assoc($authors_query)):

                    ?>


                        <tr>


                            <td>

                                <?php
                                echo $serial++;
                                ?>

                            </td>


                            <td class="author-id">

                                <?php
                                echo htmlspecialchars(
                                    $author["author_id"]
                                );
                                ?>

                            </td>


                            <td class="author-name">

                                <?php
                                echo htmlspecialchars(
                                    $author["author_name"]
                                );
                                ?>

                            </td>


                            <td>


                                <a
                                    href="authors.php?delete=<?php echo $author["author_id"]; ?>"
                                    class="action-btn delete-btn"
                                    title="Delete Author"
                                    onclick="return confirm('Are you sure you want to delete this author?');">

                                    <i class="fa-solid fa-trash"></i>

                                </a>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-state">


                <i class="fa-solid fa-user-pen"></i>


                <h5>
                    No Authors Found
                </h5>


                <p>
                    Start by adding your first author.
                </p>


            </div>


        <?php endif; ?>


    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>