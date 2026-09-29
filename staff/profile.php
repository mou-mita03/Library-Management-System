<?php
session_start();

if (!isset($_SESSION["staff_id"])) {
    header("Location: index.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "lms");

if (!$conn) {
    die("Database connection failed.");
}

$staff_id = (int) $_SESSION["staff_id"];

$message = "";
$error = "";


/* =========================
   UPDATE PROFILE
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $mobile = trim($_POST["mobile"]);


    if ($name == "" || $email == "" || $mobile == "") {

        $error = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        $name_db = mysqli_real_escape_string($conn, $name);
        $email_db = mysqli_real_escape_string($conn, $email);
        $mobile_db = mysqli_real_escape_string($conn, $mobile);

        /* Check duplicate email */

        $check_query = "
            SELECT id
            FROM staffs
            WHERE email = '$email_db'
            AND id != $staff_id
            LIMIT 1
        ";

        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {

            $error = "This email is already being used.";

        } else {

            /* Current profile image */

            $current_query = "
                SELECT profile_image
                FROM staffs
                WHERE id = $staff_id
                LIMIT 1
            ";

            $current_result = mysqli_query($conn, $current_query);
            $current_staff = mysqli_fetch_assoc($current_result);

            $profile_image = $current_staff["profile_image"];


            /* =========================
               PROFILE IMAGE UPLOAD
            ========================= */

            if (
                isset($_FILES["profile_image"]) &&
                $_FILES["profile_image"]["error"] == 0
            ) {

                $image_name = $_FILES["profile_image"]["name"];
                $image_tmp = $_FILES["profile_image"]["tmp_name"];
                $image_size = $_FILES["profile_image"]["size"];

                $extension = strtolower(
                    pathinfo($image_name, PATHINFO_EXTENSION)
                );

                $allowed_extensions = [
                    "jpg",
                    "jpeg",
                    "png",
                    "webp"
                ];

                if (!in_array($extension, $allowed_extensions)) {

                    $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

                } elseif ($image_size > 5 * 1024 * 1024) {

                    $error = "Profile image must be less than 5 MB.";

                } else {

                    $upload_folder = "uploads/";

                    if (!is_dir($upload_folder)) {
                        mkdir($upload_folder, 0777, true);
                    }

                    $new_image_name =
                        time() . "_" .
                        uniqid() . "." .
                        $extension;

                    $image_path =
                        $upload_folder .
                        $new_image_name;


                    if (move_uploaded_file($image_tmp, $image_path)) {

                        /* Delete old image */

                        if (
                            !empty($profile_image) &&
                            file_exists($upload_folder . $profile_image)
                        ) {
                            unlink($upload_folder . $profile_image);
                        }

                        $profile_image = $new_image_name;

                    } else {

                        $error = "Failed to upload profile image.";
                    }
                }
            }


            /* =========================
               UPDATE DATABASE
            ========================= */

            if ($error == "") {

                $profile_image_db = mysqli_real_escape_string(
                    $conn,
                    $profile_image
                );

                $update_query = "
                    UPDATE staffs
                    SET
                        name = '$name_db',
                        email = '$email_db',
                        mobile = '$mobile_db',
                        profile_image = '$profile_image_db'
                    WHERE id = $staff_id
                ";

                if (mysqli_query($conn, $update_query)) {

                    $_SESSION["staff_name"] = $name;
                    $_SESSION["staff_email"] = $email;

                    $message = "Profile updated successfully.";

                } else {

                    $error = "Failed to update profile.";
                }
            }
        }
    }
}


/* =========================
   GET STAFF INFO
========================= */

$query = "
    SELECT
        id,
        name,
        email,
        mobile,
        profile_image
    FROM staffs
    WHERE id = $staff_id
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {

    session_destroy();

    header("Location: index.php");
    exit();
}

$staff = mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Profile - Library Management System</title>

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
            padding: 25px 15px;
        }

        .brand {
            color: white;
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

        .page-title {
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

        .profile-card {
            max-width: 850px;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .avatar-wrapper {
            position: relative;
        }

        .avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            overflow: hidden;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-header h4 {
            margin: 0 0 5px;
            font-weight: 700;
        }

        .profile-header p {
            margin: 0;
            color: #6b7280;
        }

        .image-note {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 7px;
        }

        label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .form-control {
            height: 46px;
            border-radius: 7px;
            border: 1px solid #d1d5db;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.10);
        }

        input[type="file"] {
            height: auto;
            padding: 10px;
        }

        .btn-save {
            background: #2563eb;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 7px;
            font-weight: 600;
        }

        .btn-save:hover {
            background: #1d4ed8;
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

        }

    </style>

</head>

<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <div class="brand">
        <i class="fa-solid fa-book-open"></i>
        Library Staff
    </div>

    <a href="dashboard.php" class="nav-link">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
    </a>

    <a href="add_book.php" class="nav-link">
        <i class="fa-solid fa-plus"></i>
        Add Book
    </a>

    <a href="my_books.php" class="nav-link">
        <i class="fa-solid fa-book"></i>
        My Books
    </a>

    <a href="orders.php" class="nav-link">
        <i class="fa-solid fa-cart-shopping"></i>
        Orders
    </a>

    <a href="profile.php" class="nav-link active">
        <i class="fa-solid fa-user"></i>
        Profile
    </a>

    <div class="logout">

        <a href="logout.php" class="nav-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</div>


<!-- MAIN -->

<div class="main">

    <div class="page-title">

        <h2>Staff Profile</h2>

        <p>
            Update your account information and profile picture.
        </p>

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


    <div class="profile-card">

        <div class="profile-header">

            <div class="avatar-wrapper">

                <div class="avatar">

                    <?php if (!empty($staff["profile_image"])): ?>

                        <img
                            src="uploads/<?php echo htmlspecialchars($staff["profile_image"]); ?>"
                            alt="Staff Profile"
                        >

                    <?php else: ?>

                        <i class="fa-solid fa-user"></i>

                    <?php endif; ?>

                </div>

            </div>


            <div>

                <h4>
                    <?php echo htmlspecialchars($staff["name"]); ?>
                </h4>

                <p>
                    Staff Account
                </p>

            </div>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="form-group">

                <label>
                    Profile Picture
                </label>

                <input
                    type="file"
                    name="profile_image"
                    class="form-control"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <div class="image-note">
                    JPG, JPEG, PNG or WEBP. Maximum 5 MB.
                </div>

            </div>


            <div class="form-group">

                <label>
                    Full Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?php echo htmlspecialchars($staff["name"]); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($staff["email"]); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Mobile Number
                </label>

                <input
                    type="text"
                    name="mobile"
                    class="form-control"
                    value="<?php echo htmlspecialchars($staff["mobile"]); ?>"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn-save"
            >

                <i class="fa-solid fa-floppy-disk"></i>
                Save Changes

            </button>

        </form>

    </div>

</div>

</body>

</html>