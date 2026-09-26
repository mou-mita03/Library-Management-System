<?php
session_start();

unset($_SESSION["staff_id"]);
unset($_SESSION["staff_name"]);
unset($_SESSION["staff_email"]);

session_destroy();

header("Location: index.php");
exit();
?>