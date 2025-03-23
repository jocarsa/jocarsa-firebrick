<?php
 $iterationId = (int)($_POST['iteration_id'] ?? 0);
        $projectId   = (int)($_POST['parent_project_id'] ?? 0);
        $title       = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $file        = $_FILES['file'] ?? null;

        // Get old file_url if needed
        $oldFileStmt = $db->prepare("SELECT file_url FROM iterations WHERE id = :iid");
        $oldFileStmt->bindValue(':iid', $iterationId, SQLITE3_INTEGER);
        $oldFileRes = $oldFileStmt->execute();
        $oldFile    = $oldFileRes->fetchArray(SQLITE3_ASSOC);
        $oldFileUrl = $oldFile['file_url'] ?? '';

        $file_url = $oldFileUrl; // Default is the old file
        $upload_dir = 'uploads/';

        // If user uploaded a new file
        if ($file && $file['tmp_name']) {
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $newFileUrl = $upload_dir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $newFileUrl)) {
                $file_url = $newFileUrl;
            } else {
                $error = "Error uploading new file.";
            }
        }

        // Update iteration
        $stmt = $db->prepare("
            UPDATE iterations
            SET title = :t, description = :d, file_url = :f
            WHERE id = :id
        ");
        $stmt->bindValue(':t', $title, SQLITE3_TEXT);
        $stmt->bindValue(':d', $description, SQLITE3_TEXT);
        $stmt->bindValue(':f', $file_url, SQLITE3_TEXT);
        $stmt->bindValue(':id', $iterationId, SQLITE3_INTEGER);

        if (!$stmt->execute()) {
            $error = "Error updating iteration.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?project_id=' . $projectId);
            exit;
        }
?>
