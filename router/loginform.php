<?php
$username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (login($username, $password)) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Invalid username or password.";
        }
?>
