<?php

$connection = mysqli_connect("localhost", "root", "", "lms");

if (!$connection) {
    die("Database connection failed");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $mobile = $_POST["mobile"];
    $address = $_POST["address"];

    $query = "INSERT INTO users (name, email, password, mobile, address)
              VALUES ('$name', '$email', '$password', '$mobile', '$address')";

    if (mysqli_query($connection, $query)) {

        echo "<script>
                alert('Registration Successful!');
                window.location.href='index.php';
              </script>";

    } else {

        echo "Registration Failed: " . mysqli_error($connection);
    }
}

?>