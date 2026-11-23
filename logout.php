<?php
require_once 'config.php';
registrarLog("Logout");
session_unset();
session_destroy();
header('Location: index.php');
exit;
