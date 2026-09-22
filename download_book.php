<?php

session_start();


/*
=========================================================
ONLY LOGGED-IN USER CAN DOWNLOAD
=========================================================
*/

if (!isset($_SESSION["user_id"])) {

    header("Location: index.php");

    exit();
}


$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "lms"
);


if (!$conn) {
    die("Database connection failed.");
}


$book_id =
    isset($_GET["id"])
        ? (int)$_GET["id"]
        : 0;


if ($book_id <= 0) {
    die("Invalid book ID.");
}


$stmt = mysqli_prepare(
    $conn,
    "SELECT
        book_id,
        book_name,
        book_file
     FROM books
     WHERE book_id = ?
       AND approval_status = 'Approved'
     LIMIT 1"
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $book_id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$book =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);

mysqli_close($conn);


if (!$book) {

    die("Book not found.");

}


if (empty($book["book_file"])) {

    die("PDF is not available.");

}


$file_name =
    basename($book["book_file"]);


$file_path =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    "books" .
    DIRECTORY_SEPARATOR .
    $file_name;


if (!file_exists($file_path)) {

    die("PDF file not found.");

}


/*
=========================================================
FORCE DOWNLOAD
=========================================================
*/

header("Content-Type: application/pdf");

header(
    'Content-Disposition: attachment; filename="' .
    $file_name .
    '"'
);

header(
    "Content-Length: " .
    filesize($file_path)
);

header(
    "X-Content-Type-Options: nosniff"
);


readfile($file_path);

exit();

?>