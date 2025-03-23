<?php
$folder_id = (int)($_POST['folder_id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';

        $stmt = $db->prepare("INSERT INTO projects (folder_id, title, description) VALUES (:f, :t, :d)");
        $stmt->bindValue(':f', $folder_id, SQLITE3_INTEGER);
        $stmt->bindValue(':t', $title, SQLITE3_TEXT);
        $stmt->bindValue(':d', $description, SQLITE3_TEXT);
        if (!$stmt->execute()) {
            $error = "Error creating project.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
?>
