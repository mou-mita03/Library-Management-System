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

$admin_id = $_SESSION["admin_id"];

$message = "";
$message_type = "";

/* =========================
   FETCH ADMIN DATA
========================= */

$query = "SELECT * FROM admins WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

if (!$admin) {
    session_destroy();
    header("Location: indexad.php");
    exit();
}


/* =========================
   UPDATE PROFILE
========================= */

if (isset($_POST["update_profile"])) {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $mobile = trim($_POST["mobile"]);

    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    $profile_image = $admin["profile_image"];

    /* =========================
       BASIC VALIDATION
    ========================= */

    if ($name == "" || $email == "" || $mobile == "") {

        $message = "Please fill in all profile information.";
        $message_type = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "danger";

    } else {

        /* =========================
           PROFILE IMAGE UPLOAD
        ========================= */

        if (
            isset($_FILES["profile_image"]) &&
            $_FILES["profile_image"]["error"] == UPLOAD_ERR_OK
        ) {

            $file_name = $_FILES["profile_image"]["name"];
            $file_tmp = $_FILES["profile_image"]["tmp_name"];
            $file_size = $_FILES["profile_image"]["size"];

            $extension = strtolower(
                pathinfo($file_name, PATHINFO_EXTENSION)
            );

            $allowed_extensions = ["jpg", "jpeg", "png", "webp"];

            if (!in_array($extension, $allowed_extensions)) {

                $message = "Only JPG, JPEG, PNG and WEBP images are allowed.";
                $message_type = "danger";

            } elseif ($file_size > 5 * 1024 * 1024) {

                $message = "Profile image must be less than 5 MB.";
                $message_type = "danger";

            } else {

                $new_file_name = "admin_" . $admin_id . "_" . time() . "." . $extension;

                $upload_folder = __DIR__ . "/uploads/";

                if (!is_dir($upload_folder)) {
                    mkdir($upload_folder, 0777, true);
                }

                $destination = $upload_folder . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {

                    /* Delete old image */
                    if (!empty($profile_image)) {

                        $old_image = $upload_folder . $profile_image;

                        if (file_exists($old_image)) {
                            unlink($old_image);
                        }
                    }

                    $profile_image = $new_file_name;

                } else {

                    $message = "Failed to upload profile image.";
                    $message_type = "danger";
                }
            }
        }


        /* =========================
           PASSWORD VALIDATION
        ========================= */

        if ($message == "") {

            $password_update = false;

            if (
                $current_password != "" ||
                $new_password != "" ||
                $confirm_password != ""
            ) {

                $password_update = true;

                if ($current_password == "") {

                    $message = "Please enter your current password.";
                    $message_type = "danger";

                } elseif ($current_password !== $admin["password"]) {

                    $message = "Current password is incorrect.";
                    $message_type = "danger";

                } elseif ($new_password == "") {

                    $message = "Please enter a new password.";
                    $message_type = "danger";

                } elseif (strlen($new_password) < 6) {

                    $message = "New password must be at least 6 characters.";
                    $message_type = "danger";

                } elseif ($confirm_password == "") {

                    $message = "Please confirm your new password.";
                    $message_type = "danger";

                } elseif ($new_password !== $confirm_password) {

                    $message = "New password and confirm password do not match.";
                    $message_type = "danger";
                }
            }


            /* =========================
               UPDATE DATABASE
            ========================= */

            if ($message == "") {

                if ($password_update) {

                    $update_query = "
                        UPDATE admins
                        SET name = ?, 
                            email = ?, 
                            mobile = ?, 
                            profile_image = ?, 
                            password = ?
                        WHERE id = ?
                    ";

                    $stmt = mysqli_prepare($conn, $update_query);

                    mysqli_stmt_bind_param(
                        $stmt,
                        "sssssi",
                        $name,
                        $email,
                        $mobile,
                        $profile_image,
                        $new_password,
                        $admin_id
                    );

                } else {

                    $update_query = "
                        UPDATE admins
                        SET name = ?, 
                            email = ?, 
                            mobile = ?, 
                            profile_image = ?
                        WHERE id = ?
                    ";

                    $stmt = mysqli_prepare($conn, $update_query);

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssssi",
                        $name,
                        $email,
                        $mobile,
                        $profile_image,
                        $admin_id
                    );
                }


                if (mysqli_stmt_execute($stmt)) {

                    $_SESSION["admin_name"] = $name;
                    $_SESSION["admin_email"] = $email;

                    $message = "Profile updated successfully.";
                    $message_type = "success";

                    /* Refresh admin data */

                    $query = "SELECT * FROM admins WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $admin_id);
                    mysqli_stmt_execute($stmt);

                    $result = mysqli_stmt_get_result($stmt);
                    $admin = mysqli_fetch_assoc($result);

                } else {

                    $message = "Failed to update profile.";
                    $message_type = "danger";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Profile - Library Management System</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #111827;
            color: white;
            padding: 25px 15px;
            overflow-y: auto;
        }

        .sidebar-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar-logo i {
            font-size: 35px;
            color: #60a5fa;
            margin-bottom: 8px;
        }

        .sidebar-logo h4 {
            margin: 0;
            font-weight: 700;
        }

        .sidebar-logo small {
            color: #9ca3af;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #d1d5db;
            padding: 13px 15px;
            border-radius: 10px;
            margin-bottom: 7px;
            transition: 0.2s;
            font-size: 15px;
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
            background: #2563eb;
            color: white;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .top-header {
            background: white;
            border-radius: 15px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .top-header h2 {
            margin: 0;
            font-weight: 700;
            color: #111827;
        }

        .top-header p {
            margin: 5px 0 0;
            color: #6b7280;
        }

        /* =========================
           PROFILE CARD
        ========================= */

        .profile-card {
            background: white;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 25px;
            padding-bottom: 25px;
            margin-bottom: 25px;
            border-bottom: 1px solid #e5e7eb;
        }

        .profile-image-wrapper {
            position: relative;
        }

        .profile-image {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #eff6ff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .default-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
            border: 5px solid #eff6ff;
        }

        .profile-info h3 {
            margin: 0 0 5px;
            font-weight: 700;
        }

        .profile-info p {
            margin: 0;
            color: #6b7280;
        }

        /* =========================
           FORM
        ========================= */

        .form-section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #111827;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-control {
            height: 48px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 10px 14px;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* =========================
           PASSWORD EYE BUTTON
        ========================= */

        .password-input-wrapper {
            position: relative;
        }

        .password-input-wrapper .form-control {
            padding-right: 50px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            font-size: 17px;
            padding: 5px;
        }

        .password-toggle:hover {
            color: #2563eb;
        }

        .password-toggle:focus {
            outline: none;
        }

        .password-info {
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
        }

        /* =========================
           IMAGE UPLOAD
        ========================= */

        .custom-file-label {
            height: 48px;
            padding: 12px 14px;
            border-radius: 10px;
        }

        .custom-file-input:focus ~ .custom-file-label {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* =========================
           BUTTON
        ========================= */

        .btn-update {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-update:hover {
            background: #1d4ed8;
            color: white;
            transform: translateY(-1px);
        }

        /* =========================
           ALERT
        ========================= */

        .alert {
            border-radius: 10px;
            border: none;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 768px) {

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

        }

    </style>

</head>

<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="sidebar-logo">

        <i class="fa-solid fa-book-open"></i>

        <h4>Library Admin</h4>

        <small>Management System</small>

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


    <a href="profile.php" class="active">

        <i class="fa-solid fa-user"></i>

        Admin Profile

    </a>


    <a href="admin_logout.php">

        <i class="fa-solid fa-right-from-bracket"></i>

        Logout

    </a>

</div>



<!-- =========================
     MAIN CONTENT
========================= -->

<div class="main-content">


    <!-- HEADER -->

    <div class="top-header">

        <h2>

            <i class="fa-solid fa-user-circle"></i>

            Admin Profile

        </h2>

        <p>
            Manage your account information, profile photo and password.
        </p>

    </div>



    <!-- ALERT -->

    <?php if ($message != ""): ?>

        <div class="alert alert-<?php echo $message_type; ?>">

            <?php if ($message_type == "success"): ?>

                <i class="fa-solid fa-circle-check"></i>

            <?php else: ?>

                <i class="fa-solid fa-circle-exclamation"></i>

            <?php endif; ?>

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>



    <!-- PROFILE CARD -->

    <div class="profile-card">


        <!-- PROFILE HEADER -->

        <div class="profile-header">

            <div class="profile-image-wrapper">

                <?php if (!empty($admin["profile_image"]) && file_exists(__DIR__ . "/uploads/" . $admin["profile_image"])): ?>

                    <img
                        src="uploads/<?php echo htmlspecialchars($admin["profile_image"]); ?>"
                        class="profile-image"
                        alt="Admin Profile"
                    >

                <?php else: ?>

                    <div class="default-avatar">

                        <i class="fa-solid fa-user"></i>

                    </div>

                <?php endif; ?>

            </div>


            <div class="profile-info">

                <h3>
                    <?php echo htmlspecialchars($admin["name"]); ?>
                </h3>

                <p>
                    <i class="fa-solid fa-envelope"></i>

                    <?php echo htmlspecialchars($admin["email"]); ?>
                </p>

                <p>
                    <i class="fa-solid fa-shield-halved"></i>

                    Administrator
                </p>

            </div>

        </div>



        <!-- FORM -->

        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- =========================
                 PERSONAL INFORMATION
            ========================= -->

            <div class="form-section-title">

                <i class="fa-solid fa-user-pen"></i>

                Personal Information

            </div>


            <div class="row">


                <!-- NAME -->

                <div class="col-md-6">

                    <div class="form-group">

                        <label>

                            Full Name

                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($admin["name"]); ?>"
                            placeholder="Enter your name"
                            required
                        >

                    </div>

                </div>



                <!-- EMAIL -->

                <div class="col-md-6">

                    <div class="form-group">

                        <label>

                            Email Address

                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?php echo htmlspecialchars($admin["email"]); ?>"
                            placeholder="Enter your email"
                            required
                        >

                    </div>

                </div>



                <!-- MOBILE -->

                <div class="col-md-6">

                    <div class="form-group">

                        <label>

                            Mobile Number

                        </label>

                        <input
                            type="text"
                            name="mobile"
                            class="form-control"
                            value="<?php echo htmlspecialchars($admin["mobile"]); ?>"
                            placeholder="Enter mobile number"
                            required
                        >

                    </div>

                </div>



                <!-- PROFILE IMAGE -->

                <div class="col-md-6">

                    <div class="form-group">

                        <label>

                            Profile Photo

                        </label>

                        <div class="custom-file">

                            <input
                                type="file"
                                name="profile_image"
                                class="custom-file-input"
                                id="profileImage"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <label
                                class="custom-file-label"
                                for="profileImage"
                            >
                                Choose image
                            </label>

                        </div>

                        <small class="text-muted">

                            JPG, JPEG, PNG or WEBP. Maximum 5 MB.

                        </small>

                    </div>

                </div>

            </div>



            <hr>



            <!-- =========================
                 CHANGE PASSWORD
            ========================= -->

            <div class="form-section-title">

                <i class="fa-solid fa-lock"></i>

                Change Password

            </div>


            <p class="text-muted">

                Leave all password fields empty if you don't want to change your password.

            </p>


            <div class="row">


                <!-- CURRENT PASSWORD -->

                <div class="col-md-4">

                    <div class="form-group">

                        <label>

                            Current Password

                        </label>

                        <div class="password-input-wrapper">

                            <input
                                type="password"
                                name="current_password"
                                id="current_password"
                                class="form-control"
                                placeholder="Enter current password"
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('current_password', this)"
                            >

                                <i class="fa-solid fa-eye"></i>

                            </button>

                        </div>

                    </div>

                </div>



                <!-- NEW PASSWORD -->

                <div class="col-md-4">

                    <div class="form-group">

                        <label>

                            New Password

                        </label>

                        <div class="password-input-wrapper">

                            <input
                                type="password"
                                name="new_password"
                                id="new_password"
                                class="form-control"
                                placeholder="Enter new password"
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('new_password', this)"
                            >

                                <i class="fa-solid fa-eye"></i>

                            </button>

                        </div>

                        <div class="password-info">

                            Minimum 6 characters.

                        </div>

                    </div>

                </div>



                <!-- CONFIRM PASSWORD -->

                <div class="col-md-4">

                    <div class="form-group">

                        <label>

                            Confirm New Password

                        </label>

                        <div class="password-input-wrapper">

                            <input
                                type="password"
                                name="confirm_password"
                                id="confirm_password"
                                class="form-control"
                                placeholder="Re-enter new password"
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('confirm_password', this)"
                            >

                                <i class="fa-solid fa-eye"></i>

                            </button>

                        </div>

                    </div>

                </div>

            </div>



            <!-- UPDATE BUTTON -->

            <div class="text-right mt-3">

                <button
                    type="submit"
                    name="update_profile"
                    class="btn btn-update"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Save Changes

                </button>

            </div>


        </form>

    </div>

</div>



<!-- =========================
     JAVASCRIPT
========================= -->

<script>

function togglePassword(inputId, button) {

    const input = document.getElementById(inputId);

    const icon = button.querySelector("i");


    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove("fa-eye");

        icon.classList.add("fa-eye-slash");

    } else {

        input.type = "password";

        icon.classList.remove("fa-eye-slash");

        icon.classList.add("fa-eye");

    }

}


/* Bootstrap file input filename */

document
    .getElementById("profileImage")
    .addEventListener("change", function(e) {

        const fileName = e.target.files[0]
            ? e.target.files[0].name
            : "Choose image";

        const label = document.querySelector(
            'label[for="profileImage"]'
        );

        label.textContent = fileName;

    });

</script>


</body>

</html>