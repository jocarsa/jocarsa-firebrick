<?php
/********************************
 * HANDLE FOLDER TOGGLE (FOLD/UNFOLD)
 ********************************/
if (isset($_GET['toggle_folder']) && isLoggedIn()) {
    $folder_id = (int) $_GET['toggle_folder'];
    $new_state = (int) $_GET['state']; // 0 = unfolded, 1 = folded
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("INSERT OR REPLACE INTO user_folder_states (user_id, folder_id, is_folded)
                           VALUES (:user_id, :folder_id, :is_folded)");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':folder_id', $folder_id, SQLITE3_INTEGER);
    $stmt->bindValue(':is_folded', $new_state, SQLITE3_INTEGER);
    $stmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
