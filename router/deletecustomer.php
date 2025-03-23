<?php
$id = (int)($_POST['id'] ?? 0);

        $stmt = $db->prepare("DELETE FROM customers WHERE id=:id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        if (!$stmt->execute()) {
            $error = "Error deleting customer.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?customers');
            exit;
        }
?>
