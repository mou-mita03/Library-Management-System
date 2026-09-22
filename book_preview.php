<?php

$conn = mysqli_connect("localhost", "root", "", "lms");

if (!$conn) {
    die("Database connection failed.");
}

$book_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($book_id <= 0) {
    die("Invalid book ID.");
}


$stmt = mysqli_prepare(
    $conn,
    "SELECT
        books.book_id,
        books.book_name,
        books.book_file,
        authors.author_name,
        category.cat_name
     FROM books
     LEFT JOIN authors
        ON books.author_id = authors.author_id
     LEFT JOIN category
        ON books.cat_id = category.cat_id
     WHERE books.book_id = ?
       AND books.approval_status = 'Approved'
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $book_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$book = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);
mysqli_close($conn);


if (!$book) {
    die("Book not found.");
}


if (empty($book["book_file"])) {
    die("PDF is not available for this book.");
}


$file_name = basename($book["book_file"]);

$file_path =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    "books" .
    DIRECTORY_SEPARATOR .
    $file_name;


/* =========================================================
   PDF STREAM
========================================================= */

if (isset($_GET["pdf"]) && $_GET["pdf"] === "1") {

    if (!file_exists($file_path)) {
        die("PDF file not found.");
    }


    header("Content-Type: application/pdf");

    header(
        'Content-Disposition: inline; filename="' .
        $file_name .
        '"'
    );

    header("Content-Length: " . filesize($file_path));

    header("X-Content-Type-Options: nosniff");

    readfile($file_path);

    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($book["book_name"]); ?>
        - Library
    </title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
    >

    <style>

        body {
            margin: 0;
            background: #111827;
            font-family: Arial, Helvetica, sans-serif;
            overflow: hidden;
        }

        .topbar {
            height: 65px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            position: relative;
            z-index: 10;
        }

        .book-title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        .book-meta {
            font-size: 12px;
            color: #6b7280;
            margin-top: 3px;
        }

        .back-btn {
            background: #111827;
            color: #ffffff !important;
            padding: 9px 15px;
            border-radius: 8px;
            text-decoration: none !important;
            font-size: 12px;
            font-weight: 700;
        }

        .pdf-container {
            width: 100%;
            height: calc(100vh - 65px);
        }

        .pdf-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

    </style>

</head>

<body>


<div class="topbar">

    <div>

        <div class="book-title">

            <?php
            echo htmlspecialchars(
                $book["book_name"]
            );
            ?>

        </div>


        <div class="book-meta">

            <?php
            echo !empty($book["author_name"])
                ? "Author: " .
                  htmlspecialchars($book["author_name"])
                : "Library Book";
            ?>

            <?php if (!empty($book["cat_name"])): ?>

                &nbsp; | &nbsp;

                <?php
                echo htmlspecialchars(
                    $book["cat_name"]
                );
                ?>

            <?php endif; ?>

        </div>

    </div>


    <a
        href="index.php#gallery"
        class="back-btn"
    >

        ← Back to Library

    </a>

</div>


<div class="pdf-container">

    <iframe
        src="book_preview.php?id=<?php echo $book_id; ?>&pdf=1#toolbar=0&navpanes=0&scrollbar=0"
        title="Book PDF"
    ></iframe>

</div>


</body>

</html>