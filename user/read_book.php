<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "lms");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


/* =========================
   CHECK BOOK ID
========================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: books.php");
    exit();
}

$book_id = intval($_GET["id"]);


/* =========================
   GET BOOK INFORMATION
========================= */

$query = mysqli_query(
    $conn,
    "
    SELECT
        books.book_id,
        books.book_name,
        books.book_no,
        books.book_price,
        books.book_file,
        authors.author_name,
        category.cat_name
    FROM books

    LEFT JOIN authors
        ON books.author_id = authors.author_id

    LEFT JOIN category
        ON books.cat_id = category.cat_id

    WHERE books.book_id = $book_id
    "
);


if (!$query || mysqli_num_rows($query) == 0) {
    header("Location: books.php");
    exit();
}


$book = mysqli_fetch_assoc($query);


/* =========================
   CHECK PDF
========================= */

$book_file = $book["book_file"];

if (empty($book_file)) {
    $pdf_exists = false;
} else {
    $pdf_path = "../books/" . $book_file;
    $pdf_exists = file_exists($pdf_path);
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
        Read <?php echo htmlspecialchars($book["book_name"]); ?>
    </title>


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
            background: #eef2f7;
            font-family: Arial, sans-serif;
            color: #1f2937;
        }


        /* =========================
           TOP BAR
        ========================= */

        .topbar {
            height: 75px;
            background: #111827;
            color: white;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 30px;

            box-shadow: 0 3px 12px rgba(0,0,0,0.15);
        }


        .brand {
            font-size: 21px;
            font-weight: 700;
        }


        .brand i {
            margin-right: 8px;
        }


        .back-btn {
            color: white;
            text-decoration: none;

            padding: 9px 16px;

            border: 1px solid rgba(255,255,255,0.3);

            border-radius: 7px;

            transition: 0.3s;
        }


        .back-btn:hover {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.1);
        }


        /* =========================
           BOOK INFO
        ========================= */

        .book-info {
            background: white;

            margin: 25px 30px 15px;

            padding: 20px 25px;

            border-radius: 12px;

            box-shadow: 0 3px 15px rgba(0,0,0,0.06);
        }


        .book-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }


        .book-details {
            color: #64748b;
            font-size: 14px;
        }


        .book-details span {
            margin-right: 20px;
        }


        /* =========================
           PDF READER
        ========================= */

        .reader-container {
            margin: 15px 30px 30px;

            background: white;

            border-radius: 12px;

            overflow: hidden;

            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }


        .reader-header {
            padding: 15px 20px;

            background: #f8fafc;

            border-bottom: 1px solid #e5e7eb;

            font-weight: 600;
        }


        .pdf-viewer {
            width: 100%;
            height: calc(100vh - 200px);

            min-height: 600px;

            border: none;

            display: block;
        }


        /* =========================
           NO PDF
        ========================= */

        .no-pdf {
            min-height: 500px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            align-items: center;

            text-align: center;

            padding: 40px;
        }


        .no-pdf i {
            font-size: 70px;
            color: #dc2626;
            margin-bottom: 20px;
        }


        .no-pdf h4 {
            font-weight: 700;
            margin-bottom: 10px;
        }


        .no-pdf p {
            color: #64748b;
        }


        @media (max-width: 768px) {

            .topbar {
                padding: 0 15px;
            }

            .book-info,
            .reader-container {
                margin-left: 15px;
                margin-right: 15px;
            }

            .book-title {
                font-size: 20px;
            }

            .pdf-viewer {
                height: calc(100vh - 230px);
                min-height: 500px;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     TOP BAR
========================= -->

<div class="topbar">

    <div class="brand">

        <i class="fa-solid fa-book-open"></i>

        Library Reader

    </div>


    <a
        href="books.php"
        class="back-btn"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Books

    </a>

</div>



<!-- =========================
     BOOK INFORMATION
========================= -->

<div class="book-info">

    <div class="book-title">

        <?php
        echo htmlspecialchars($book["book_name"]);
        ?>

    </div>


    <div class="book-details">

        <span>

            <i class="fa-solid fa-user"></i>

            <?php
            echo htmlspecialchars(
                $book["author_name"] ?? "Unknown"
            );
            ?>

        </span>


        <span>

            <i class="fa-solid fa-layer-group"></i>

            <?php
            echo htmlspecialchars(
                $book["cat_name"] ?? "Unknown"
            );
            ?>

        </span>


        <span>

            <i class="fa-solid fa-hashtag"></i>

            Book No:
            <?php
            echo htmlspecialchars($book["book_no"]);
            ?>

        </span>

    </div>

</div>



<!-- =========================
     PDF READER
========================= -->

<div class="reader-container">


    <div class="reader-header">

        <i class="fa-solid fa-file-pdf"></i>

        PDF Reader

    </div>


    <?php if ($pdf_exists): ?>


        <iframe
            class="pdf-viewer"
            src="../books/<?php echo rawurlencode($book_file); ?>"
            title="PDF Reader"
        >
        </iframe>


    <?php else: ?>


        <div class="no-pdf">

            <i class="fa-solid fa-file-circle-xmark"></i>


            <h4>
                PDF Not Available
            </h4>


            <p>
                The PDF for this book has not been uploaded yet.
            </p>


            <a
                href="books.php"
                class="btn btn-dark"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Books

            </a>

        </div>


    <?php endif; ?>


</div>


</body>

</html>