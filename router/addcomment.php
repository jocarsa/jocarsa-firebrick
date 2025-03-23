<?php
$iteration_id = (int)($_POST['iteration_id'] ?? 0);
        $commentText = $_POST['comment'] ?? '';
        $customer_id = $_SESSION['user_id'];

        $stmt = $db->prepare("INSERT INTO comments (iteration_id, customer_id, comment)
                              VALUES (:i, :c, :cm)");
        $stmt->bindValue(':i', $iteration_id, SQLITE3_INTEGER);
        $stmt->bindValue(':c', $customer_id, SQLITE3_INTEGER);
        $stmt->bindValue(':cm', $commentText, SQLITE3_TEXT);

        if (!$stmt->execute()) {
            $error = "Error submitting comment.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?project_id=' . ((int)$_GET['project_id']));
            exit;
        }
?>
