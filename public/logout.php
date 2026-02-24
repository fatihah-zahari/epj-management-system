<?php
session_start();
session_destroy();
header("Location: /epj/public/login.php");
exit;
