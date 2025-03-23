<?php
$project_id = (int)($_POST['project_id'] ?? 0);
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$file = $_FILES['file'] ?? null;

$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($file && $file['tmp_name']) {
    $file_url = $upload_dir . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $file_url)) {
        // Insert new iteration record
        $stmt = $db->prepare(
            "INSERT INTO iterations (project_id, title, description, file_url)
             VALUES (:pid, :t, :d, :f)"
        );
        $stmt->bindValue(':pid', $project_id, SQLITE3_INTEGER);
        $stmt->bindValue(':t', $title, SQLITE3_TEXT);
        $stmt->bindValue(':d', $description, SQLITE3_TEXT);
        $stmt->bindValue(':f', $file_url, SQLITE3_TEXT);
        if (!$stmt->execute()) {
            $error = "Error saving iteration in DB.";
        } else {
            // Retrieve the new iteration ID
            $iteration_id = $db->lastInsertRowID();
            // Insert creation date into the separate table (created_at is auto-set)
            $stmt2 = $db->prepare("INSERT INTO iteration_dates (iteration_id) VALUES (:iid)");
            $stmt2->bindValue(':iid', $iteration_id, SQLITE3_INTEGER);
            $stmt2->execute();
            
            header('Location: ' . $_SERVER['PHP_SELF'] . '?project_id=' . $project_id);
            exit;
        }
    } else {
        $error = "Error uploading file.";
    }
} else {
    $error = "No file uploaded.";
}
?>

