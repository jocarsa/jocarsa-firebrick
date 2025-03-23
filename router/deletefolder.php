<?php
$folderId = (int)($_POST['folder_id'] ?? 0);

        // (Optional) If you'd like to also delete subfolders/projects/iterations, 
        // you'll need a recursive approach. For simplicity, we do a direct delete:
        $stmt = $db->prepare("DELETE FROM folders WHERE id = :id");
        $stmt->bindValue(':id', $folderId, SQLITE3_INTEGER);
        if (!$stmt->execute()) {
            $error = "Error deleting folder. Make sure it's empty or handle cascade manually.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
?>
