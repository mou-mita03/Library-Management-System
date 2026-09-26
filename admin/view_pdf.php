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

if (!isset($_GET["id"])) {
    header("Location: manage_books.php");
    exit();
}

$book_id = (int) $_GET["id"];

$query = "
    SELECT book_name, book_file
    FROM books
    WHERE book_id = $book_id
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Book not found.");
}

$book = mysqli_fetch_assoc($result);

if (empty($book["book_file"])) {
    die("PDF not available.");
}

$pdf_path = "../books/" . $book["book_file"];

if (!file_exists($pdf_path)) {
    die("PDF file not found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($book["book_name"]); ?> - PDF
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #111827;
            font-family: Arial, sans-serif;
        }

        .topbar {
            height: 65px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 22px;
        }

        .back-btn {
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
        }

        .back-btn:hover {
            background: #1d4ed8;
        }

        .title {
            font-weight: 700;
            color: #1f2937;
            max-width: 55%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pdf-container {
            width: 100%;
            height: calc(100vh - 65px);
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>

<body>

    <div class="topbar">

        <a href="manage_books.php" class="back-btn">
            ← Back to Manage Books
        </a>

        <div class="title">
            <?php echo htmlspecialchars($book["book_name"]); ?>
        </div>

    </div>

    <div class="pdf-container">

        <iframe
            src="../books/<?php echo rawurlencode($book["book_file"]); ?>">
        </iframe>

    </div>

</body>

</html>