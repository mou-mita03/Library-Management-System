<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $conn = mysqli_connect("localhost", "root", "", "lms");

    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {

            if ($password === $user["password"]) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Incorrect password.";
            }

        } else {
            $error = "No account found with this email.";
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Login - Library Management System</title>

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
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f6fb;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .login-container {
            width: 100%;
            max-width: 1050px;
            min-height: 620px;
            background: #ffffff;
            border-radius: 26px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 25px 70px rgba(15, 23, 42, 0.12);
        }

        /* LEFT SIDE */

        .welcome-panel {
            width: 44%;
            padding: 55px 45px;
            background: linear-gradient(145deg, #4338ca, #6366f1);
            color: #ffffff;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .welcome-panel::before {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            top: -100px;
            right: -100px;
        }

        .welcome-panel::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            bottom: -100px;
            left: -80px;
        }

        .brand {
            position: relative;
            z-index: 2;
        }

        .brand-icon {
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 17px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 25px;
            margin-bottom: 25px;
        }

        .brand h1 {
            font-size: 36px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 17px;
        }

        .brand p {
            color: rgba(255, 255, 255, 0.78);
            font-size: 15px;
            line-height: 1.7;
            margin: 0;
        }

        .feature-list {
            position: relative;
            z-index: 2;
        }

        .feature {
            display: flex;
            align-items: center;
            margin-bottom: 17px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }

        .feature-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.13);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 13px;
        }

        /* RIGHT SIDE */

        .form-panel {
            width: 56%;
            padding: 55px 65px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 30px;
        }

        .form-header .small-title {
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .form-header h2 {
            color: #111827;
            font-size: 31px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-header p {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }

        .alert {
            border-radius: 12px;
            font-size: 14px;
            border: none;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            color: #374151;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 15px;
            z-index: 2;
        }

        .form-control {
            height: 52px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding-left: 45px;
            padding-right: 45px;
            font-size: 14px;
            transition: 0.2s;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.10);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #9ca3af;
            cursor: pointer;
            padding: 3px;
        }

        .password-toggle:hover {
            color: #4f46e5;
        }

        .login-btn {
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            transition: 0.25s;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.20);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 25px rgba(79, 70, 229, 0.26);
        }

        .bottom-links {
            margin-top: 24px;
            text-align: center;
            font-size: 13px;
        }

        .bottom-links a {
            color: #4f46e5;
            font-weight: 700;
            text-decoration: none;
        }

        .back-link {
            display: block;
            margin-top: 16px;
            color: #6b7280 !important;
            font-weight: 500 !important;
        }

        .back-link:hover {
            color: #111827 !important;
        }

        @media (max-width: 850px) {

            .login-container {
                max-width: 500px;
                min-height: auto;
            }

            .welcome-panel {
                display: none;
            }

            .form-panel {
                width: 100%;
                padding: 45px 35px;
            }
        }

        @media (max-width: 480px) {

            .login-wrapper {
                padding: 15px;
            }

            .login-container {
                border-radius: 20px;
            }

            .form-panel {
                padding: 35px 23px;
            }

            .form-header h2 {
                font-size: 27px;
            }
        }

    </style>

</head>

<body>

<div class="login-wrapper">

    <div class="login-container">

        <!-- LEFT -->
        <div class="welcome-panel">

            <div class="brand">

                <div class="brand-icon">
                    <i class="fa-solid fa-book-open"></i>
                </div>

                <h1>Welcome Back!</h1>

                <p>
                    Continue your journey with the Library Management System.
                    Access books, orders and your personal library account.
                </p>

            </div>

            <div class="feature-list">

                <div class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    Browse your library
                </div>

                <div class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    Order books easily
                </div>

                <div class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    Manage your account
                </div>

            </div>

        </div>


        <!-- RIGHT -->
        <div class="form-panel">

            <div class="form-header">

                <div class="small-title">
                    User Portal
                </div>

                <h2>Sign in to your account</h2>

                <p>
                    Enter your details to continue.
                </p>

            </div>


            <?php if ($error !== ""): ?>

                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <form method="POST">

                <div class="form-group">

                    <label>Email Address</label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-envelope input-icon"></i>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter your email"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label>Password</label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-lock input-icon"></i>

                        <input
                            type="password"
                            name="password"
                            id="userPassword"
                            class="form-control"
                            placeholder="Enter your password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                        >
                            <i class="fa-solid fa-eye" id="passwordIcon"></i>
                        </button>

                    </div>

                </div>


                <button type="submit" class="login-btn">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i>
                    Sign In
                </button>

            </form>


            <div class="bottom-links">

                Don't have an account?
                <a href="../signup.php">Create Account</a>

                <a href="../index.php" class="back-link">
                    <i class="fa-solid fa-arrow-left mr-1"></i>
                    Back to role selection
                </a>

            </div>

        </div>

    </div>

</div>


<script>

function togglePassword() {

    const password = document.getElementById("userPassword");
    const icon = document.getElementById("passwordIcon");

    if (password.type === "password") {

        password.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");

    } else {

        password.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");

    }
}

</script>

</body>
</html>