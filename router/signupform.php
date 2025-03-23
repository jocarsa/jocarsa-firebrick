<?php
// Only creating admins from public signup
        $username = $_POST['username'] ?? '';
        $passwordPlain = $_POST['password'] ?? '';
        $hashed = password_hash($passwordPlain, PASSWORD_DEFAULT);

        // Insert into `users` table with role='admin'
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:u, :p, 'admin')");
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':p', $hashed, SQLITE3_TEXT);
        $res = $stmt->execute();
        if (!$res) {
            $error = "Error creating admin (username might already exist).";
        } else {
            // Optionally auto-login new admin
            login($username, $passwordPlain);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
?>
