<?php
session_start();
echo "SESSION:<br>";
var_dump($_SESSION);
echo "<br>REQUEST_URI: " . $_SERVER['REQUEST_URI'];
echo "<br>SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'];
?>