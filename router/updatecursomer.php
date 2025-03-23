<?php
$id = (int)($_POST['id'] ?? 0);
        $username = $_POST['username'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        $stmt = $db->prepare(
            "UPDATE customers SET username=:u, name=:n, email=:e WHERE id=:id"
        );
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':n', $name, SQLITE3_TEXT);
        $stmt->bindValue(':e', $email, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        if (!$stmt->execute()) {
            $error = "Error updating customer.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?customers');
            exit;
        }
?>
