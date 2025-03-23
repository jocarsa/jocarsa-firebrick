<?php
$username = $_POST['username'] ?? '';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        $stmt = $db->prepare("INSERT INTO customers (username, password, name, email)
                              VALUES (:u, :p, :n, :e)");
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':p', $password, SQLITE3_TEXT);
        $stmt->bindValue(':n', $name, SQLITE3_TEXT);
        $stmt->bindValue(':e', $email, SQLITE3_TEXT);
        if (!$stmt->execute()) {
            $error = "Error creating customer (username might already exist).";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?customers');
            exit;
        }
?>
