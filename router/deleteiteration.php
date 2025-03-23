<?php
$iterationId = (int)($_POST['iteration_id'] ?? 0);
        // Optionally delete comments for this iteration:
        $db->exec("DELETE FROM comments WHERE iteration_id = $iterationId");
        
        // Now delete iteration
        $stmt = $db->prepare("DELETE FROM iterations WHERE id = :id");
        $stmt->bindValue(':id', $iterationId, SQLITE3_INTEGER);
        if (!$stmt->execute()) {
            $error = "Error deleting iteration.";
        } else {
            // Redirect back to the same project page
            $project_id = (int)$_POST['parent_project_id'];
            header('Location: ' . $_SERVER['PHP_SELF'] . '?project_id=' . $project_id);
            exit;
        }
?>
