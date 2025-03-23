<?php
$name = $_POST['name'] ?? '';
        $parent_id = ($_POST['parent_id'] !== '') ? (int)$_POST['parent_id'] : null;

        $stmt = $db->prepare("INSERT INTO folders (name, parent_id) VALUES (:n, :p)");
        $stmt->bindValue(':n', $name, SQLITE3_TEXT);
        if ($parent_id !== null) {
            $stmt->bindValue(':p', $parent_id, SQLITE3_INTEGER);
        } else {
            $stmt->bindValue(':p', null, SQLITE3_NULL);
        }
        if (!$stmt->execute()) {
            $error = "Error creating folder.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
?>
