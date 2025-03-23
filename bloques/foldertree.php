<?php
/********************************
 * FOLDER TREE RENDERING
 ********************************/
function renderFolderTree($parent_id = null) {
    global $db;
    $parent_condition = ($parent_id === null) ? "IS NULL" : "= $parent_id";
    $folderQ = $db->query("SELECT * FROM folders WHERE parent_id $parent_condition ORDER BY name ASC");

    while ($folder = $folderQ->fetchArray(SQLITE3_ASSOC)) {
        // Determine folder fold state for the current user (default is unfolded, i.e. 0)
        $is_folded = 0;
        if (isLoggedIn()) {
            $stmt = $db->prepare("SELECT is_folded FROM user_folder_states WHERE user_id = :user_id AND folder_id = :folder_id");
            $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':folder_id', $folder['id'], SQLITE3_INTEGER);
            $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if ($result) {
                $is_folded = $result['is_folded'];
            }
        }

        echo "<li><span style='font-weight:bold;'>";
        // Render toggle icon if user is logged in
        if (isLoggedIn()) {
            if ($is_folded == 0) {
                // Unfolded: show minus icon to allow folding
                echo "<a href='?toggle_folder={$folder['id']}&state=1' title='Fold'>➖</a> ";
            } else {
                // Folded: show plus icon to allow unfolding
                echo "<a href='?toggle_folder={$folder['id']}&state=0' title='Unfold'>➕</a> ";
            }
        }
        echo "📁 " . htmlspecialchars($folder['name']) . "</span>";

        // Render children only if folder is unfolded
        if ($is_folded == 0) {
            // Get projects inside this folder
            $projQ = $db->query("SELECT * FROM projects WHERE folder_id=" . $folder['id'] . " ORDER BY title ASC");
            $projectsArr = [];
            while ($p = $projQ->fetchArray(SQLITE3_ASSOC)) {
                $projectsArr[] = $p;
            }

            // Check for subfolders
            $subFolderQ = $db->query("SELECT id FROM folders WHERE parent_id=" . (int)$folder['id']);
            $subfoldersArr = [];
            while ($sf = $subFolderQ->fetchArray(SQLITE3_ASSOC)) {
                $subfoldersArr[] = $sf;
            }

            if (count($projectsArr) || count($subfoldersArr)) {
                echo "<ul>";
                // Render projects
                foreach ($projectsArr as $proj) {
                    echo "<li>";
                    echo "<a href='?project_id=" . $proj['id'] . "'>🗎 " . htmlspecialchars($proj['title']) . "</a>";
                    // Only admins see delete button for project:
                    if (isAdmin()) {
                        echo "<form method='post' style='display:inline;' onsubmit='return confirm(\"Delete this project?\");'>
                                <input type='hidden' name='delete_project' value='1'>
                                <input type='hidden' name='project_id' value='{$proj['id']}'>
                                <button type='submit' style='margin-left:10px;background:red;'>X</button>
                              </form>";
                    }
                    echo "</li>";
                }
                // Recurse to render subfolders
                renderFolderTree($folder['id']);
                echo "</ul>";
            }
        }
        echo "</li>";
    }
}
?>
