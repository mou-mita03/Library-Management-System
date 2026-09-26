<?php

session_start();

if (!isset($_SESSION["user_id"])) {

    header("Location: ../index.php");
    exit();

}


/* ================= DATABASE CONNECTION ================= */

$connection = mysqli_connect("localhost", "root", "", "lms");

if (!$connection) {

    die("Database connection failed");

}


/* ================= GET ISSUED BOOKS ================= */

/*
   Only show books issued to the currently logged-in user.
*/

$student_id = $_SESSION["user_id"];

$query = "
    SELECT
        s_no,
        book_no,
        book_name,
        book_author,
        status,
        issue_date

    FROM issued_books

    WHERE student_id = '$student_id'

    ORDER BY issue_date DESC
";


$result = mysqli_query($connection, $query);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Issued Books | Library Management System</title>


    <!-- Bootstrap -->

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">


    <!-- Font Awesome -->

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family: Arial, sans-serif;

            background: #f5f7fb;

            color: #333;
        }


        /* ================= SIDEBAR ================= */

        .sidebar {

            position: fixed;

            left: 0;

            top: 0;

            width: 250px;

            height: 100vh;

            background: #111827;

            padding: 25px 15px;

            z-index: 1000;
        }


        .logo {

            color: white;

            font-size: 21px;

            font-weight: bold;

            text-align: center;

            margin-bottom: 40px;
        }


        .logo i {

            margin-right: 8px;

            color: #4f9cff;
        }


        .menu-title {

            color: #8d98a9;

            font-size: 12px;

            text-transform: uppercase;

            margin: 20px 15px 10px;

            letter-spacing: 1px;
        }


        .sidebar a {

            display: flex;

            align-items: center;

            color: #cbd5e1;

            text-decoration: none;

            padding: 13px 15px;

            margin-bottom: 6px;

            border-radius: 8px;

            transition: 0.3s;
        }


        .sidebar a i {

            width: 25px;

            font-size: 16px;
        }


        .sidebar a:hover,
        .sidebar a.active {

            background: #2563eb;

            color: white;
        }


        .logout {

            position: absolute;

            bottom: 25px;

            left: 15px;

            right: 15px;
        }


        .logout a {

            background: #dc2626;

            color: white;

            justify-content: center;
        }


        .logout a:hover {

            background: #b91c1c;
        }


        /* ================= MAIN CONTENT ================= */

        .main-content {

            margin-left: 250px;

            min-height: 100vh;
        }


        /* ================= TOPBAR ================= */

        .topbar {

            height: 75px;

            background: white;

            border-bottom: 1px solid #e5e7eb;

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 0 35px;
        }


        .page-title {

            font-size: 23px;

            font-weight: bold;

            color: #111827;
        }


        .user-profile {

            display: flex;

            align-items: center;
        }


        .user-icon {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            background: #2563eb;

            color: white;

            display: flex;

            justify-content: center;

            align-items: center;

            margin-right: 10px;
        }


        .user-name {

            font-weight: 600;

            color: #374151;
        }


        /* ================= CONTENT ================= */

        .content {

            padding: 35px;
        }


        .section-heading {

            margin-bottom: 25px;
        }


        .section-heading h2 {

            font-size: 25px;

            font-weight: bold;

            color: #111827;
        }


        .section-heading p {

            color: #6b7280;

            margin-top: 5px;
        }


        /* ================= TABLE CARD ================= */

        .table-card {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 13px;

            overflow: hidden;

            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        }


        .table-responsive {

            margin: 0;
        }


        .table {

            margin-bottom: 0;
        }


        .table thead th {

            background: #111827;

            color: white;

            border: none;

            font-size: 13px;

            padding: 15px;

            white-space: nowrap;
        }


        .table tbody td {

            padding: 15px;

            vertical-align: middle;

            color: #4b5563;

            border-color: #edf0f4;

            font-size: 14px;
        }


        .table tbody tr:hover {

            background: #f8fafc;
        }


        /* ================= STATUS ================= */

        .status {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;
        }


        .status-issued {

            background: #dbeafe;

            color: #1d4ed8;
        }


        .status-returned {

            background: #dcfce7;

            color: #166534;
        }


        /* ================= EMPTY STATE ================= */

        .empty-state {

            text-align: center;

            padding: 60px 20px;
        }


        .empty-icon {

            width: 70px;

            height: 70px;

            margin: 0 auto 20px;

            border-radius: 50%;

            background: #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;
        }


        .empty-state h4 {

            color: #111827;

            font-weight: bold;

            margin-bottom: 8px;
        }


        .empty-state p {

            color: #6b7280;

            margin: 0;
        }


        /* ================= FOOTER ================= */

        .footer {

            text-align: center;

            color: #9ca3af;

            font-size: 13px;

            margin-top: 40px;
        }


        /* ================= RESPONSIVE ================= */

        @media (max-width: 768px) {

            .sidebar {

                width: 210px;
            }

            .main-content {

                margin-left: 210px;
            }

            .topbar {

                padding: 0 20px;
            }

            .content {

                padding: 20px;
            }

        }


        @media (max-width: 576px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;
            }

            .main-content {

                margin-left: 0;
            }

            .logout {

                position: relative;

                left: auto;

                right: auto;

                bottom: auto;

                margin-top: 20px;
            }

            .topbar {

                padding: 15px;

                height: auto;
            }

            .content {

                padding: 15px;
            }

        }

    </style>

</head>


<body>


<!-- ================= SIDEBAR ================= -->

<div class="sidebar">


    <div class="logo">

        <i class="fa-solid fa-book-open"></i>

        Library System

    </div>


    <div class="menu-title">

        Main Menu

    </div>


    <a href="dashboard.php">

        <i class="fa-solid fa-house"></i>

        Dashboard

    </a>


    <a href="books.php">

        <i class="fa-solid fa-book"></i>

        Books

    </a>


    <a href="issued_books.php"
       class="active">

        <i class="fa-solid fa-book-open-reader"></i>

        Issued Books

    </a>


    <div class="menu-title">

        Account

    </div>


    <a href="#">

        <i class="fa-solid fa-user"></i>

        My Profile

    </a>


    <div class="logout">

        <a href="../logout.php">

            <i class="fa-solid fa-right-from-bracket mr-2"></i>

            Logout

        </a>

    </div>


</div>



<!-- ================= MAIN CONTENT ================= -->

<div class="main-content">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="page-title">

            Issued Books

        </div>


        <div class="user-profile">

            <div class="user-icon">

                <i class="fa-solid fa-user"></i>

            </div>


            <div class="user-name">

                <?php

                echo htmlspecialchars($_SESSION["user_name"]);

                ?>

            </div>

        </div>

    </div>



    <!-- CONTENT -->

    <div class="content">


        <div class="section-heading">

            <h2>

                My Issued Books

            </h2>

            <p>

                View the books currently issued to your account.

            </p>

        </div>



        <div class="table-card">


            <?php

            if ($result && mysqli_num_rows($result) > 0) {

            ?>


            <div class="table-responsive">

                <table class="table">


                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Book No</th>

                            <th>Book Name</th>

                            <th>Author</th>

                            <th>Issue Date</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    $serial = 1;

                    while ($book = mysqli_fetch_assoc($result)) {

                    ?>


                        <tr>


                            <td>

                                <?php echo $serial++; ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars($book["book_no"]);

                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars($book["book_name"]);

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars($book["book_author"]);

                                ?>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime($book["issue_date"])
                                );

                                ?>

                            </td>


                            <td>


                                <?php

                                if ($book["status"] == 1) {

                                ?>


                                    <span class="status status-issued">

                                        <i class="fa-solid fa-book-open mr-1"></i>

                                        Issued

                                    </span>


                                <?php

                                } else {

                                ?>


                                    <span class="status status-returned">

                                        <i class="fa-solid fa-check mr-1"></i>

                                        Returned

                                    </span>


                                <?php

                                }

                                ?>


                            </td>


                        </tr>


                    <?php

                    }

                    ?>


                    </tbody>

                </table>

            </div>


            <?php

            } else {

            ?>


            <div class="empty-state">


                <div class="empty-icon">

                    <i class="fa-solid fa-book-open"></i>

                </div>


                <h4>

                    No Issued Books

                </h4>


                <p>

                    You currently don't have any books issued.

                </p>


            </div>


            <?php

            }

            ?>


        </div>



        <div class="footer">

            Library Management System © 2026

        </div>


    </div>


</div>


</body>

</html>