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
   AUTHORS
========================= */

$authors = mysqli_query(
    $conn,
    "SELECT author_id, author_name
     FROM authors
     ORDER BY author_name ASC"
);



/* =========================
   CATEGORIES
========================= */

$categories = mysqli_query(
    $conn,
    "SELECT cat_id, cat_name
     FROM category
     ORDER BY cat_name ASC"
);



/* =========================
   ADD BOOK
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $book_name = trim($_POST["book_name"]);

    $author_option = trim($_POST["author_option"]);
    $new_author_name = trim($_POST["new_author_name"]);

    $category_option = trim($_POST["category_option"]);
    $new_category_name = trim($_POST["new_category_name"]);

    $book_no = (int) $_POST["book_no"];
    $book_price = (int) $_POST["book_price"];

    $pdf_comment = trim($_POST["pdf_comment"] ?? "");



    /* =========================
       BASIC VALIDATION
    ========================= */

    if ($book_name == "") {

        $error = "Book name is required.";

    } elseif ($author_option == "") {

        $error = "Please select an author.";

    } elseif (
        $author_option === "new"
        && $new_author_name == ""
    ) {

        $error = "Please enter the new author name.";

    } elseif ($category_option == "") {

        $error = "Please select a category.";

    } elseif (
        $category_option === "new"
        && $new_category_name == ""
    ) {

        $error = "Please enter the new category name.";

    } elseif ($book_no <= 0) {

        $error = "Please enter a valid book number.";

    } elseif ($book_price <= 0) {

        $error = "Please enter a valid book price.";

    }



    /* =========================
       AUTHOR DATA
    ========================= */

    $author_id = "NULL";
    $pending_author_name = "NULL";

    if ($error == "") {

        if ($author_option === "new") {

            $new_author_db = mysqli_real_escape_string(
                $conn,
                $new_author_name
            );



            $author_check = mysqli_query(
                $conn,
                "
                SELECT author_id
                FROM authors
                WHERE LOWER(author_name) =
                      LOWER('$new_author_db')
                LIMIT 1
                "
            );



            if (
                $author_check
                && mysqli_num_rows($author_check) > 0
            ) {

                $existing_author = mysqli_fetch_assoc(
                    $author_check
                );

                $author_id =
                    (int) $existing_author["author_id"];

                $pending_author_name = "NULL";

            } else {

                $author_id = "NULL";

                $pending_author_name =
                    "'$new_author_db'";
            }

        } else {

            $selected_author_id =
                (int) $author_option;

            if ($selected_author_id <= 0) {

                $error =
                    "Invalid author selected.";

            } else {

                $author_id =
                    $selected_author_id;

                $pending_author_name =
                    "NULL";
            }
        }
    }



    /* =========================
       CATEGORY DATA
    ========================= */

    $cat_id = "NULL";
    $pending_category_name = "NULL";

    if ($error == "") {

        if ($category_option === "new") {

            $new_category_db =
                mysqli_real_escape_string(
                    $conn,
                    $new_category_name
                );



            /* Check whether category already exists */

            $category_check = mysqli_query(
                $conn,
                "
                SELECT cat_id
                FROM category
                WHERE LOWER(cat_name) =
                      LOWER('$new_category_db')
                LIMIT 1
                "
            );



            if (
                $category_check
                && mysqli_num_rows(
                    $category_check
                ) > 0
            ) {

                $existing_category =
                    mysqli_fetch_assoc(
                        $category_check
                    );

                $cat_id =
                    (int) $existing_category["cat_id"];

                $pending_category_name =
                    "NULL";

            } else {

                $cat_id = "NULL";

                $pending_category_name =
                    "'$new_category_db'";
            }

        } else {

            $selected_cat_id =
                (int) $category_option;

            if ($selected_cat_id <= 0) {

                $error =
                    "Invalid category selected.";

            } else {

                $cat_id =
                    $selected_cat_id;

                $pending_category_name =
                    "NULL";
            }
        }
    }



    /* =========================
       PDF VALIDATION
    ========================= */

    $file_name = "";
    $file_tmp = "";
    $file_size = "";

    $has_pdf = false;

    if (
        $error == ""
        && isset($_FILES["book_file"])
        && $_FILES["book_file"]["error"] !== 4
    ) {

        if ($_FILES["book_file"]["error"] != 0) {

            $error =
                "Failed to upload PDF.";

        } else {

            $has_pdf = true;

            $file_name =
                $_FILES["book_file"]["name"];

            $file_tmp =
                $_FILES["book_file"]["tmp_name"];

            $file_size =
                $_FILES["book_file"]["size"];



            $file_extension =
                strtolower(
                    pathinfo(
                        $file_name,
                        PATHINFO_EXTENSION
                    )
                );



            if ($file_extension !== "pdf") {

                $error =
                    "Only PDF files are allowed.";

            } elseif (
                $file_size > 20 * 1024 * 1024
            ) {

                $error =
                    "PDF file size must be less than 20 MB.";
            }
        }
    }



    /* =========================
       BOOK COVER VALIDATION
    ========================= */

    $cover_name = "";
    $cover_tmp = "";
    $cover_size = "";
    $cover_unique_name = "";

    if (
        $error == ""
        && isset($_FILES["book_cover"])
        && $_FILES["book_cover"]["error"] === 0
    ) {

        $cover_name =
            $_FILES["book_cover"]["name"];

        $cover_tmp =
            $_FILES["book_cover"]["tmp_name"];

        $cover_size =
            $_FILES["book_cover"]["size"];



        $cover_extension =
            strtolower(
                pathinfo(
                    $cover_name,
                    PATHINFO_EXTENSION
                )
            );



        $allowed_cover_extensions = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];



        if (
            !in_array(
                $cover_extension,
                $allowed_cover_extensions,
                true
            )
        ) {

            $error =
                "Only JPG, JPEG, PNG and WEBP images are allowed for the book cover.";

        } elseif (
            $cover_size > 5 * 1024 * 1024
        ) {

            $error =
                "Book cover image size must be less than 5 MB.";

        } elseif (
            @getimagesize($cover_tmp) === false
        ) {

            $error =
                "The uploaded book cover is not a valid image.";
        }
    }



    /* =========================
       UPLOAD PDF + COVER
    ========================= */

    $unique_file_name = "";
    $file_path = "";

    $cover_unique_name = "";
    $cover_path = "";

    if ($error == "") {

        $upload_folder = "../books/";

        $cover_folder = "../images/books/";



        /* Create PDF folder */

        if (!is_dir($upload_folder)) {

            mkdir(
                $upload_folder,
                0777,
                true
            );
        }



        /* Create Cover folder */

        if (!is_dir($cover_folder)) {

            mkdir(
                $cover_folder,
                0777,
                true
            );
        }



        /* =========================
           PDF FILE NAME
        ========================= */

        if ($has_pdf) {

            $safe_file_name =
                preg_replace(
                    "/[^A-Za-z0-9._-]/",
                    "_",
                    basename($file_name)
                );



            $unique_file_name =
                time()
                . "_"
                . uniqid()
                . "_"
                . $safe_file_name;



            $file_path =
                $upload_folder .
                $unique_file_name;



            /* =========================
               UPLOAD PDF
            ========================= */

            if (
                !move_uploaded_file(
                    $file_tmp,
                    $file_path
                )
            ) {

                $error =
                    "Failed to upload PDF.";
            }
        }



        /* =========================
           BOOK COVER
        ========================= */

        if (
            $error == ""
            && isset($_FILES["book_cover"])
            && $_FILES["book_cover"]["error"] === 0
        ) {

            $cover_unique_name =
                time()
                . "_"
                . uniqid()
                . "_cover."
                . $cover_extension;



            $cover_path =
                $cover_folder .
                $cover_unique_name;



            if (
                !move_uploaded_file(
                    $cover_tmp,
                    $cover_path
                )
            ) {

                /* Delete uploaded PDF */

                if (
                    $file_path != ""
                    && file_exists($file_path)
                ) {

                    unlink($file_path);
                }

                $error =
                    "Failed to upload book cover.";
            }
        }



        /* =========================
           INSERT BOOK
        ========================= */

        if ($error == "") {

            $book_name_db =
                mysqli_real_escape_string(
                    $conn,
                    $book_name
                );



            $pdf_comment_db =
                mysqli_real_escape_string(
                    $conn,
                    $pdf_comment
                );



            $file_name_db = "";

            if ($unique_file_name != "") {

                $file_name_db =
                    mysqli_real_escape_string(
                        $conn,
                        $unique_file_name
                    );
            }



            $cover_name_db = "";

            if ($cover_unique_name != "") {

                $cover_name_db =
                    mysqli_real_escape_string(
                        $conn,
                        $cover_unique_name
                    );
            }



            /* =========================
               INSERT QUERY
            ========================= */

            $insert_query = "
                INSERT INTO books
                (
                    book_name,
                    author_id,
                    pending_author_name,
                    cat_id,
                    pending_category_name,
                    book_no,
                    book_price,
                    book_file,
                    pdf_comment,
                    book_cover,
                    added_by,
                    added_by_type,
                    approval_status
                )

                VALUES
                (
                    '$book_name_db',
                    $author_id,
                    $pending_author_name,
                    $cat_id,
                    $pending_category_name,
                    $book_no,
                    $book_price,
                    " .
                    (
                        $file_name_db != ""
                        ? "'$file_name_db'"
                        : "NULL"
                    ) .
                    ",
                    " .
                    (
                        $pdf_comment_db != ""
                        ? "'$pdf_comment_db'"
                        : "NULL"
                    ) .
                    ",
                    " .
                    (
                        $cover_name_db != ""
                        ? "'$cover_name_db'"
                        : "NULL"
                    ) .
                    ",
                    $staff_id,
                    'Staff',
                    'Pending'
                )
            ";



            if (
                mysqli_query(
                    $conn,
                    $insert_query
                )
            ) {

                $message =
                    "Book submitted successfully. Waiting for Admin approval.";

            } else {

                /* Delete uploaded PDF */

                if (
                    $file_path != ""
                    && file_exists($file_path)
                ) {

                    unlink($file_path);
                }



                /* Delete uploaded cover */

                if (
                    $cover_path != ""
                    && file_exists($cover_path)
                ) {

                    unlink($cover_path);
                }



                $error =
                    "Failed to save book information.";
            }
        }
    }
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
        Add Book - Staff Panel
    </title>



    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >



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
            color: white;

            padding: 25px 15px;
        }



        .brand {
            font-size: 21px;
            font-weight: 700;

            padding: 0 12px 25px;

            border-bottom:
                1px solid #374151;

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



        .form-card {
            max-width: 900px;

            background: white;

            border-radius: 12px;

            padding: 30px;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);
        }



        .form-section-title {
            font-size: 17px;

            font-weight: 700;

            margin-bottom: 20px;

            padding-bottom: 12px;

            border-bottom:
                1px solid #e5e7eb;
        }



        label {
            font-size: 14px;

            font-weight: 600;

            color: #374151;
        }



        .form-control {
            height: 46px;

            border:
                1px solid #d1d5db;

            border-radius: 7px;
        }



        .form-control:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,.10);
        }



        .new-box {
            display: none;

            background: #f8fafc;

            border:
                1px solid #dbeafe;

            border-radius: 8px;

            padding: 15px;

            margin-top: 10px;
        }



        .new-note {
            font-size: 12px;

            color: #6b7280;

            margin-top: 5px;
        }



        .pdf-box {
            border:
                2px dashed #d1d5db;

            border-radius: 10px;

            padding: 25px;

            text-align: center;

            background: #f9fafb;
        }



        .pdf-icon {
            font-size: 35px;

            color: #dc2626;

            margin-bottom: 10px;
        }



        .pdf-box small {
            color: #6b7280;
        }



        .cover-box {
            border:
                2px dashed #d1d5db;

            border-radius: 10px;

            padding: 25px;

            text-align: center;

            background: #f9fafb;
        }



        .cover-icon {
            font-size: 35px;

            color: #2563eb;

            margin-bottom: 10px;
        }



        .cover-box small {
            color: #6b7280;
        }



        .approval-note {
            background: #fff7ed;

            border:
                1px solid #fed7aa;

            color: #9a3412;

            padding: 14px 16px;

            border-radius: 8px;

            margin-bottom: 25px;

            font-size: 14px;
        }



        .btn-submit {
            background: #2563eb;

            color: white;

            border: none;

            padding: 12px 22px;

            border-radius: 7px;

            font-weight: 600;
        }



        .btn-submit:hover {
            background: #1d4ed8;

            color: white;
        }



        .btn-cancel {
            background: #e5e7eb;

            color: #374151;

            padding: 12px 22px;

            border-radius: 7px;

            font-weight: 600;

            text-decoration: none;

            margin-left: 8px;
        }



        .btn-cancel:hover {
            background: #d1d5db;

            color: #111827;

            text-decoration: none;
        }



        .alert {
            max-width: 900px;

            border-radius: 8px;
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



    <a
        href="dashboard.php"
        class="nav-link"
    >

        <i class="fa-solid fa-chart-line"></i>

        Dashboard

    </a>



    <a
        href="add_book.php"
        class="nav-link active"
    >

        <i class="fa-solid fa-plus"></i>

        Add Book

    </a>



    <a
        href="my_books.php"
        class="nav-link"
    >

        <i class="fa-solid fa-book"></i>

        My Books

    </a>



    <a
        href="orders.php"
        class="nav-link"
    >

        <i class="fa-solid fa-cart-shopping"></i>

        Orders

    </a>



    <a
        href="profile.php"
        class="nav-link"
    >

        <i class="fa-solid fa-user"></i>

        Profile

    </a>



    <div class="logout">

        <a
            href="logout.php"
            class="nav-link"
        >

            <i
                class="fa-solid fa-right-from-bracket"
            ></i>

            Logout

        </a>

    </div>



</div>



<!-- MAIN -->

<div class="main">



    <div class="page-title">

        <h2>
            Add New Book
        </h2>

        <p>
            Submit a new book for Admin approval.
        </p>

    </div>



    <?php if ($message != ""): ?>

        <div class="alert alert-success">

            <i
                class="fa-solid fa-circle-check"
            ></i>

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>



    <?php if ($error != ""): ?>

        <div class="alert alert-danger">

            <i
                class="fa-solid fa-circle-exclamation"
            ></i>

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>



    <div class="form-card">



        <div class="approval-note">

            <i class="fa-solid fa-clock"></i>

            <strong>
                Approval Required:
            </strong>

            Submit the complete book information once.
            Admin will review it before approval.

        </div>



        <div class="form-section-title">

            <i class="fa-solid fa-book"></i>

            Book Information

        </div>



        <form
            method="POST"
            enctype="multipart/form-data"
        >



            <div class="row">



                <!-- BOOK NAME -->

                <div class="col-md-6">

                    <div class="form-group">

                        <label>
                            Book Name
                        </label>

                        <input
                            type="text"
                            name="book_name"
                            class="form-control"
                            placeholder="Enter book name"
                            required
                        >

                    </div>

                </div>



                <!-- AUTHOR -->

                <div class="col-md-6">

                    <div class="form-group">

                        <label>
                            Author
                        </label>

                        <select
                            name="author_option"
                            id="author_option"
                            class="form-control"
                            onchange="toggleNewAuthor()"
                            required
                        >

                            <option value="">
                                Select Author
                            </option>



                            <?php while (
                                $author =
                                mysqli_fetch_assoc(
                                    $authors
                                )
                            ): ?>

                                <option
                                    value="<?php
                                    echo $author[
                                        "author_id"
                                    ];
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $author[
                                            "author_name"
                                        ]
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>



                            <option value="new">
                                + New Author
                            </option>

                        </select>



                        <div
                            id="newAuthorBox"
                            class="new-box"
                        >

                            <label>
                                New Author Name
                            </label>

                            <input
                                type="text"
                                name="new_author_name"
                                id="new_author_name"
                                class="form-control"
                                placeholder="Enter new author name"
                            >

                            <div class="new-note">

                                The new author will be created
                                when Admin approves the book.

                            </div>

                        </div>

                    </div>

                </div>



                <!-- CATEGORY -->

                <div class="col-md-6">

                    <div class="form-group">

                        <label>
                            Category
                        </label>

                        <select
                            name="category_option"
                            id="category_option"
                            class="form-control"
                            onchange="toggleNewCategory()"
                            required
                        >

                            <option value="">
                                Select Category
                            </option>



                            <?php while (
                                $category =
                                mysqli_fetch_assoc(
                                    $categories
                                )
                            ): ?>

                                <option
                                    value="<?php
                                    echo $category[
                                        "cat_id"
                                    ];
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $category[
                                            "cat_name"
                                        ]
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>



                            <option value="new">
                                + New Category
                            </option>

                        </select>



                        <div
                            id="newCategoryBox"
                            class="new-box"
                        >

                            <label>
                                New Category Name
                            </label>

                            <input
                                type="text"
                                name="new_category_name"
                                id="new_category_name"
                                class="form-control"
                                placeholder="Enter new category name"
                            >

                            <div class="new-note">

                                The new category will be created
                                when Admin approves the book.

                            </div>

                        </div>

                    </div>

                </div>



                <!-- BOOK NUMBER -->

                <div class="col-md-3">

                    <div class="form-group">

                        <label>
                            Book Number
                        </label>

                        <input
                            type="number"
                            name="book_no"
                            class="form-control"
                            placeholder="Book No."
                            min="1"
                            required
                        >

                    </div>

                </div>



                <!-- PRICE -->

                <div class="col-md-3">

                    <div class="form-group">

                        <label>
                            Price (৳)
                        </label>

                        <input
                            type="number"
                            name="book_price"
                            class="form-control"
                            placeholder="Price"
                            min="1"
                            required
                        >

                    </div>

                </div>



            </div>



            <!-- BOOK COVER -->

            <div class="form-section-title mt-4">

                <i
                    class="fa-solid fa-image"
                ></i>

                Book Cover

            </div>



            <div class="cover-box">

                <div class="cover-icon">

                    <i
                        class="fa-solid fa-image"
                    ></i>

                </div>



                <input
                    type="file"
                    name="book_cover"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >



                <br>



                <small>

                    JPG, JPEG, PNG or WEBP.
                    Maximum file size: 5 MB.
                    Optional.

                </small>

            </div>



            <!-- PDF -->

            <div class="form-section-title mt-4">

                <i
                    class="fa-solid fa-file-pdf"
                ></i>

                Book PDF

            </div>



            <div class="pdf-box">

                <div class="pdf-icon">

                    <i
                        class="fa-solid fa-file-pdf"
                    ></i>

                </div>



                <input
                    type="file"
                    name="book_file"
                    accept=".pdf,application/pdf"
                >



                <br>



                <small>

                    PDF only.
                    Maximum file size: 20 MB.
                    Optional.

                </small>

            </div>



            <!-- PDF COMMENT -->

            <div class="form-section-title mt-4">

                <i
                    class="fa-solid fa-circle-info"
                ></i>

                PDF Availability Note

            </div>



            <div class="form-group">

                <label>
                    PDF Comment / Availability Note
                </label>

                <textarea
                    name="pdf_comment"
                    class="form-control"
                    rows="4"
                    placeholder="Example: Physical book is available in the library, but PDF is not available."
                    style="height: auto;"
                ></textarea>

                <small class="text-muted">

                    Optional. Add a note if the PDF is not available
                    or if users need additional information.

                </small>

            </div>



            <!-- BUTTONS -->

            <div class="mt-4">



                <button
                    type="submit"
                    class="btn-submit"
                >

                    <i
                        class="fa-solid fa-paper-plane"
                    ></i>

                    Submit for Approval

                </button>



                <a
                    href="dashboard.php"
                    class="btn-cancel"
                >

                    Cancel

                </a>



            </div>



        </form>



    </div>



</div>



<script>

function toggleNewAuthor() {

    const select =
        document.getElementById(
            "author_option"
        );

    const box =
        document.getElementById(
            "newAuthorBox"
        );

    const input =
        document.getElementById(
            "new_author_name"
        );



    if (select.value === "new") {

        box.style.display = "block";

        input.required = true;

    } else {

        box.style.display = "none";

        input.required = false;

        input.value = "";
    }
}



function toggleNewCategory() {

    const select =
        document.getElementById(
            "category_option"
        );

    const box =
        document.getElementById(
            "newCategoryBox"
        );

    const input =
        document.getElementById(
            "new_category_name"
        );



    if (select.value === "new") {

        box.style.display = "block";

        input.required = true;

    } else {

        box.style.display = "none";

        input.required = false;

        input.value = "";
    }
}

</script>



</body>

</html>