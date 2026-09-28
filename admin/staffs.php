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
   ADD STAFF
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_staff"])) {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $mobile = trim($_POST["mobile"]);

    if ($name == "" || $email == "" || $password == "" || $mobile == "") {

        $error = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } else {

        $name_db = mysqli_real_escape_string($conn, $name);
        $email_db = mysqli_real_escape_string($conn, $email);
        $password_db = mysqli_real_escape_string($conn, $password);
        $mobile_db = mysqli_real_escape_string($conn, $mobile);

        $check = mysqli_query(
            $conn,
            "SELECT id FROM staffs WHERE email = '$email_db' LIMIT 1"
        );

        if (mysqli_num_rows($check) > 0) {

            $error = "This email is already registered.";

        } else {

            $insert = "
                INSERT INTO staffs
                (name, email, password, mobile)
                VALUES
                ('$name_db', '$email_db', '$password_db', '$mobile_db')
            ";

            if (mysqli_query($conn, $insert)) {

                header("Location: staffs.php?added=1");
                exit();

            } else {

                $error = "Failed to add staff.";
            }
        }
    }
}


/* =========================
   DELETE STAFF
========================= */

if (isset($_GET["delete"])) {

    $staff_id = (int) $_GET["delete"];

    $delete = "
        DELETE FROM staffs
        WHERE id = $staff_id
    ";

    if (mysqli_query($conn, $delete)) {

        header("Location: staffs.php?deleted=1");
        exit();

    } else {

        $error = "Failed to delete staff.";
    }
}


/* =========================
   MESSAGES
========================= */

if (isset($_GET["added"])) {
    $message = "Staff added successfully.";
}

if (isset($_GET["deleted"])) {
    $message = "Staff removed successfully.";
}


/* =========================
   STAFF LIST
========================= */

$result = mysqli_query(
    $conn,
    "
    SELECT id, name, email, mobile, profile_image
    FROM staffs
    ORDER BY id DESC
    "
);


/* =========================
   ADMIN INFO
========================= */

$admin_id = (int) $_SESSION["admin_id"];

$admin_result = mysqli_query(
    $conn,
    "
    SELECT name, profile_image
    FROM admins
    WHERE id = $admin_id
    LIMIT 1
    "
);

$admin = mysqli_fetch_assoc($admin_result);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Management - Admin Panel</title>

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
            font-weight: 600;
            color: #4b5563;
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

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .form-control {
            height: 45px;
            border-radius: 7px;
        }

        .btn-add {
            background: #2563eb;
            border: none;
            color: white;
            padding: 10px 18px;
            border-radius: 7px;
            font-weight: 600;
        }

        .btn-add:hover {
            background: #1d4ed8;
            color: white;
        }

        .staff-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .staff-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .staff-name {
            font-weight: 700;
        }

        .staff-email {
            color: #9ca3af;
            font-size: 12px;
        }

        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border: none;
            padding: 7px 11px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-delete:hover {
            background: #fee2e2;
            color: #b91c1c;
        }

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


<!-- SIDEBAR -->

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

    <a href="book_approvals.php" class="nav-link">
        <i class="fa-solid fa-circle-check"></i>
        Book Approvals
    </a>

    <a href="manage_users.php" class="nav-link">
        <i class="fa-solid fa-users"></i>
        Manage Users
    </a>

    <a href="staffs.php" class="nav-link active">
        <i class="fa-solid fa-user-tie"></i>
        Staff Management
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


<!-- MAIN -->

<div class="main">


    <div class="topbar">

        <div class="page-title">

            <h2>Staff Management</h2>

            <p>
                Add and manage library staff members.
            </p>

        </div>


        <div class="admin-info">

            <span>
                <?php echo htmlspecialchars($admin["name"]); ?>
            </span>

            <div class="admin-avatar">

                <?php if (!empty($admin["profile_image"])): ?>

                    <img
                        src="uploads/<?php echo htmlspecialchars($admin["profile_image"]); ?>"
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


    <!-- ADD STAFF -->

    <div class="content-card">

        <div class="section-title">
            <i class="fa-solid fa-user-plus"></i>
            Add New Staff
        </div>

        <form method="POST">

            <div class="row">

                <div class="col-md-3 mb-3">

                    <label>Full Name</label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="Staff name"
                        required
                    >

                </div>


                <div class="col-md-3 mb-3">

                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="Staff email"
                        required
                    >

                </div>


                <div class="col-md-2 mb-3">

                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Password"
                        required
                    >

                </div>


                <div class="col-md-2 mb-3">

                    <label>Mobile</label>

                    <input
                        type="text"
                        name="mobile"
                        class="form-control"
                        placeholder="Mobile"
                        required
                    >

                </div>


                <div class="col-md-2 mb-3 d-flex align-items-end">

                    <button
                        type="submit"
                        name="add_staff"
                        class="btn-add w-100"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add Staff
                    </button>

                </div>

            </div>

        </form>

    </div>


    <!-- STAFF LIST -->

    <div class="content-card">

        <div class="section-title">
            <i class="fa-solid fa-users"></i>
            Staff Members
        </div>


        <div class="table-responsive">

            <table class="table">

                <thead>

                    <tr>

                        <th>#</th>
                        <th>Staff</th>
                        <th>Mobile</th>
                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php $count = 1; ?>

                    <?php while ($staff = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <?php echo $count++; ?>
                            </td>


                            <td>

                                <div class="d-flex align-items-center">

                                    <div class="staff-avatar mr-3">

                                        <?php if (!empty($staff["profile_image"])): ?>

                                            <img
                                                src="../staff/uploads/<?php echo htmlspecialchars($staff["profile_image"]); ?>"
                                                alt="Staff"
                                            >

                                        <?php else: ?>

                                            <i class="fa-solid fa-user"></i>

                                        <?php endif; ?>

                                    </div>

                                    <div>

                                        <div class="staff-name">

                                            <?php
                                            echo htmlspecialchars(
                                                $staff["name"]
                                            );
                                            ?>

                                        </div>

                                        <div class="staff-email">

                                            <?php
                                            echo htmlspecialchars(
                                                $staff["email"]
                                            );
                                            ?>

                                        </div>

                                    </div>

                                </div>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $staff["mobile"]
                                );
                                ?>

                            </td>


                            <td>

                                <a
                                    href="staffs.php?delete=<?php echo $staff["id"]; ?>"
                                    class="btn-delete"
                                    onclick="return confirm('Remove this staff member?');"
                                >

                                    <i class="fa-solid fa-trash"></i>
                                    Remove

                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="4" class="text-center text-muted py-5">

                            No staff members found.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>

</html>