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

$query = "
    SELECT
        books.book_id,
        books.book_name,
        books.book_no,
        books.book_price,
        books.book_file,
        books.approval_status,
        books.rejection_reason,
        authors.author_name,
        category.cat_name
    FROM books
    LEFT JOIN authors
        ON books.author_id = authors.author_id
    LEFT JOIN category
        ON books.cat_id = category.cat_id
    WHERE books.added_by = $staff_id
    AND books.added_by_type = 'Staff'
    ORDER BY books.book_id DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Books - Staff Panel</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        body {
            margin: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
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

        .page-title h2 {
            font-size: 25px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-title p {
            color: #6b7280;
            margin-bottom: 25px;
        }

        .card-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
            overflow: hidden;
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

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .approved {
            background: #ecfdf5;
            color: #15803d;
        }

        .rejected {
            background: #fef2f2;
            color: #dc2626;
        }

        .pdf-btn {
            color: #dc2626;
            font-weight: 600;
            text-decoration: none;
        }

        .pdf-btn:hover {
            color: #b91c1c;
            text-decoration: none;
        }

        .reason {
            color: #dc2626;
            font-size: 12px;
            margin-top: 5px;
        }

        .empty {
            text-align: center;
            padding: 70px 20px;
            color: #9ca3af;
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

    <a href="my_books.php" class="nav-link active">
        <i class="fa-solid fa-book"></i>
        My Books
    </a>

    <a href="orders.php" class="nav-link">
        <i class="fa-solid fa-cart-shopping"></i>
        Orders
    </a>

    <a href="profile.php" class="nav-link">
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


<div class="main">

    <div class="page-title">
        <h2>My Added Books</h2>

        <p>
            Check the approval status of your submitted books.
        </p>
    </div>


    <div class="card-box">

        <?php if ($result && mysqli_num_rows($result) > 0): ?>

            <div class="table-responsive">

                <table class="table mb-0">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>PDF</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php $count = 1; ?>

                    <?php while ($book = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <?php echo $count++; ?>
                            </td>

                            <td>
                                <div class="book-name">
                                    <?php echo htmlspecialchars($book["book_name"]); ?>
                                </div>

                                <small class="text-muted">
                                    Book No:
                                    <?php echo $book["book_no"]; ?>
                                </small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($book["author_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($book["cat_name"]); ?>
                            </td>

                            <td>
                                ৳<?php echo number_format($book["book_price"]); ?>
                            </td>

                            <td>

                                <?php if (!empty($book["book_file"])): ?>

                                    <span class="text-success">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Available
                                    </span>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Not Available
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($book["approval_status"] == "Pending"): ?>

                                    <span class="status pending">
                                        Pending
                                    </span>

                                <?php elseif ($book["approval_status"] == "Approved"): ?>

                                    <span class="status approved">
                                        Approved
                                    </span>

                                <?php else: ?>

                                    <span class="status rejected">
                                        Rejected
                                    </span>

                                    <?php if (!empty($book["rejection_reason"])): ?>

                                        <div class="reason">
                                            <?php
                                            echo htmlspecialchars(
                                                $book["rejection_reason"]
                                            );
                                            ?>
                                        </div>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty">

                <i class="fa-solid fa-book fa-3x mb-3"></i>

                <h5>No Books Added Yet</h5>

                <p>
                    You have not submitted any books.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>