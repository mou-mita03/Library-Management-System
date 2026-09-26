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


/* Delete User */

if (isset($_GET["delete"])) {

    $user_id = (int) $_GET["delete"];

    mysqli_query(
        $conn,
        "DELETE FROM users WHERE id = $user_id"
    );

    header("Location: manage_users.php?deleted=1");
    exit();
}


/* Get Admin Profile */

$admin_id = (int) $_SESSION["admin_id"];

$admin_query = mysqli_query(
    $conn,
    "SELECT name, email, profile_image
     FROM admins
     WHERE id = $admin_id"
);

$admin = mysqli_fetch_assoc($admin_query);


/* Get Users */

$users_query = mysqli_query(
    $conn,
    "SELECT id, name, email, mobile, address
     FROM users
     ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Users - Library Management System</title>

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


        /* User Count */

        .user-count {
            background: #eff6ff;
            color: #2563eb;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }


        /* ================= TABLE ================= */

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

        .user-name {
            font-weight: 600;
            color: #111827;
        }

        .email {
            color: #4b5563;
        }

        .mobile {
            white-space: nowrap;
        }


        /* Status */

        .status-badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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
            padding: 50px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #9ca3af;
        }


        /* ================= MOBILE ================= */

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


    <!-- Main Menu -->

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


    <a href="manage_users.php" class="active">

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


    <a href="issued_books.php">

        <i class="fa-solid fa-book-open-reader"></i>

        Issued Books

    </a>


    <!-- Account -->

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
                Manage Users
            </h2>

            <p>
                View and manage registered library users
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


    <!-- Success Message -->

    <?php if (isset($_GET["deleted"])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="fa-solid fa-circle-check"></i>

            User deleted successfully.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- Users -->

    <div class="content-card">


        <div class="card-header-custom">


            <h4>

                <i class="fa-solid fa-users me-2"></i>

                All Registered Users

            </h4>


            <?php

            $total_users = 0;

            if ($users_query) {
                $total_users = mysqli_num_rows($users_query);
            }

            ?>


            <span class="user-count">

                <i class="fa-solid fa-user"></i>

                <?php echo $total_users; ?>

                Users

            </span>

        </div>


        <?php if ($users_query && mysqli_num_rows($users_query) > 0): ?>


            <div class="table-responsive">


                <table class="table table-hover">


                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Name</th>

                            <th>Email</th>

                            <th>Mobile</th>

                            <th>Address</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    $serial = 1;

                    while ($user = mysqli_fetch_assoc($users_query)):

                    ?>


                        <tr>


                            <td>

                                <?php
                                echo $serial++;
                                ?>

                            </td>


                            <td class="user-name">

                                <?php
                                echo htmlspecialchars(
                                    $user["name"]
                                );
                                ?>

                            </td>


                            <td class="email">

                                <?php
                                echo htmlspecialchars(
                                    $user["email"]
                                );
                                ?>

                            </td>


                            <td class="mobile">

                                <?php
                                echo htmlspecialchars(
                                    $user["mobile"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $user["address"]
                                );
                                ?>

                            </td>


                            <td>

                                <span class="status-badge">

                                    <i class="fa-solid fa-circle-check"></i>

                                    Active

                                </span>

                            </td>


                            <td>


                                <a
                                    href="manage_users.php?delete=<?php echo $user["id"]; ?>"
                                    class="action-btn delete-btn"
                                    title="Delete User"
                                    onclick="return confirm('Are you sure you want to delete this user?');">

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


                <i class="fa-solid fa-users-slash"></i>


                <h5>
                    No Users Found
                </h5>


                <p>
                    There are currently no registered users.
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