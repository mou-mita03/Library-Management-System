<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Signup - Library Management System</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        body {
            background-color: #f2f2f2;
        }

        .signup-box {
            width: 450px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .title {
            text-align: center;
            margin-bottom: 25px;
        }
    </style>
</head>

<body>

<div class="signup-box">

    <h2 class="title">Create Account</h2>

    <form action="register.php" method="POST">

        <div class="form-group">
            <label>Name</label>
            <input type="text"
                   name="name"
                   class="form-control"
                   placeholder="Enter your name"
                   required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email"
                   name="email"
                   class="form-control"
                   placeholder="Enter your email"
                   required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Enter password"
                   required>
        </div>

        <div class="form-group">
            <label>Mobile</label>
            <input type="number"
                   name="mobile"
                   class="form-control"
                   placeholder="Enter mobile number"
                   required>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address"
                      class="form-control"
                      placeholder="Enter your address"
                      required></textarea>
        </div>

        <button type="submit"
                class="btn btn-primary btn-block">
            Register
        </button>

    </form>

    <div class="text-center mt-3">
        <a href="index.php">Already have an account? Login</a>
    </div>

</div>

</body>
</html>