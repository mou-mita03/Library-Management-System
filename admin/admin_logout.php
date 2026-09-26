<?php

session_start();

/* Destroy admin session */
unset($_SESSION["admin_id"]);
unset($_SESSION["admin_name"]);
unset($_SESSION["admin_email"]);

/* Destroy complete session */
session_destroy();

/* Redirect to admin login */
header("Location: indexad.php");
exit();

?>