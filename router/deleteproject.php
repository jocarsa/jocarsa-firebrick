<?php
$projectId = (int)($_POST['project_id'] ?? 0);

        // Before deleting the project, optionally remove all iterations for that project:
        $db->exec("DELETE FROM iterations WHERE project_id = $projectId");

        // Now delete project
        $stmt = $db->prepare("DELETE FROM projects WHERE id = :id");
        $stmt->bindValue(':id', $projectId, SQLITE3_INTEGER);
        if (!$stmt->execute()) {
            $error = "Error deleting project.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
?>
