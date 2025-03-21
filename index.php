<?php
/*******************************
 * projects.db + table creation
 *******************************/
$db = new SQLite3('../databases/firebrick.db');

// Create tables if they don't exist
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT,
    role TEXT  -- Usually 'admin'
)");

$db->exec("CREATE TABLE IF NOT EXISTS folders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    parent_id INTEGER
)");

$db->exec("CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    folder_id INTEGER,
    title TEXT,
    description TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS iterations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER,
    title TEXT,
    description TEXT,
    file_url TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT,
    name TEXT,
    email TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    iteration_id INTEGER,
    customer_id INTEGER,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Create table to store per-user folder states (0 = unfolded, 1 = folded)
$db->exec("CREATE TABLE IF NOT EXISTS user_folder_states (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    folder_id INTEGER,
    is_folded INTEGER DEFAULT 0,
    UNIQUE(user_id, folder_id)
)");

session_start();

/********************************
 * AUTH FUNCTIONS
 ********************************/
function login($username, $password) {
    global $db;

    // First check the 'users' table (admin)
    $stmt = $db->prepare("SELECT id, password, role FROM users WHERE username = :u");
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $res = $stmt->execute();
    $admin = $res->fetchArray(SQLITE3_ASSOC);

    if ($admin) {
        if (password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = $admin['role']; // 'admin'
            $_SESSION['user_table'] = 'users';
            return true;
        }
        return false;
    }

    // If not found in 'users', check 'customers'
    $stmt = $db->prepare("SELECT id, password FROM customers WHERE username = :u");
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $res = $stmt->execute();
    $customer = $res->fetchArray(SQLITE3_ASSOC);

    if ($customer && password_verify($password, $customer['password'])) {
        $_SESSION['user_id'] = $customer['id'];
        $_SESSION['role'] = 'customer';
        $_SESSION['user_table'] = 'customers';
        return true;
    }

    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

function isCustomer() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'customer');
}

function logout() {
    session_destroy();
}

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

/********************************
 * HANDLE FORM SUBMISSIONS
 ********************************/
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- LOGIN ----
    if (isset($_POST['login_form'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (login($username, $password)) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
    // ---- SIGNUP (ADMIN ONLY) ----
    elseif (isset($_POST['signup_form'])) {
        // Only creating admins from public signup
        $username = $_POST['username'] ?? '';
        $passwordPlain = $_POST['password'] ?? '';
        $hashed = password_hash($passwordPlain, PASSWORD_DEFAULT);

        // Insert into `users` table with role='admin'
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:u, :p, 'admin')");
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':p', $hashed, SQLITE3_TEXT);
        $res = $stmt->execute();
        if (!$res) {
            $error = "Error creating admin (username might already exist).";
        } else {
            // Optionally auto-login new admin
            login($username, $passwordPlain);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    // ---- CREATE FOLDER (ADMIN) ----
    elseif (isset($_POST['create_folder']) && isAdmin()) {
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
    }
    // ---- DELETE FOLDER (ADMIN) ----
    elseif (isset($_POST['delete_folder']) && isAdmin()) {
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
    }
    // ---- CREATE PROJECT (ADMIN) ----
    elseif (isset($_POST['create_project']) && isAdmin()) {
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
    }
    // ---- DELETE PROJECT (ADMIN) ----
    elseif (isset($_POST['delete_project']) && isAdmin()) {
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
    }
    // ---- CREATE ITERATION (ADMIN) ----
    elseif (isset($_POST['create_iteration']) && isAdmin()) {
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
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?project_id=' . $project_id);
                    exit;
                }
            } else {
                $error = "Error uploading file.";
            }
        } else {
            $error = "No file uploaded.";
        }
    }
    // ---- DELETE ITERATION (ADMIN) ----
    elseif (isset($_POST['delete_iteration']) && isAdmin()) {
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
    }
    // ---- UPDATE ITERATION (ADMIN) ----
    elseif (isset($_POST['update_iteration']) && isAdmin()) {
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
    }
    // ---- CREATE / UPDATE / DELETE CUSTOMER (ADMIN) ----
    elseif (isset($_POST['create_customer']) && isAdmin()) {
        $username = $_POST['username'] ?? '';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        $stmt = $db->prepare("INSERT INTO customers (username, password, name, email)
                              VALUES (:u, :p, :n, :e)");
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':p', $password, SQLITE3_TEXT);
        $stmt->bindValue(':n', $name, SQLITE3_TEXT);
        $stmt->bindValue(':e', $email, SQLITE3_TEXT);
        if (!$stmt->execute()) {
            $error = "Error creating customer (username might already exist).";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?customers');
            exit;
        }
    }
    elseif (isset($_POST['update_customer']) && isAdmin()) {
        $id = (int)($_POST['id'] ?? 0);
        $username = $_POST['username'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        $stmt = $db->prepare(
            "UPDATE customers SET username=:u, name=:n, email=:e WHERE id=:id"
        );
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':n', $name, SQLITE3_TEXT);
        $stmt->bindValue(':e', $email, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        if (!$stmt->execute()) {
            $error = "Error updating customer.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?customers');
            exit;
        }
    }
    elseif (isset($_POST['delete_customer']) && isAdmin()) {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $db->prepare("DELETE FROM customers WHERE id=:id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        if (!$stmt->execute()) {
            $error = "Error deleting customer.";
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?customers');
            exit;
        }
    }
    // ---- ADD COMMENT (CUSTOMER) ----
    elseif (isset($_POST['comment']) && isCustomer()) {
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
    }
}

// Logout
if (isset($_GET['logout'])) {
    logout();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/********************************
 * FETCH DATA FOR DISPLAY
 ********************************/
$customersResult = $db->query("SELECT * FROM customers ORDER BY username ASC");

$selected_project = null;
$iterations = null;
if (isset($_GET['project_id'])) {
    $pid = (int)$_GET['project_id'];
    $selected_project = $db->querySingle("SELECT * FROM projects WHERE id=$pid", true);
    if ($selected_project) {
        $iterations = $db->query("SELECT * FROM iterations WHERE project_id=$pid");
    }
}

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>jocarsa | firebrick</title>
    <link rel="icon" type="image/svg+xml" href="https://jocarsa.com/static/logo/firebrick.png" />
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');

        body {
            margin: 0; padding: 0;
            font-family: Ubuntu,Arial, sans-serif;
            background-color: #f4f4f4;
        }
        header {
            background-color: firebrick;
            color: white;
            padding: 10px 20px;
            display: flex; align-items: center; justify-content: space-between;
        }
        header h1 {
            margin: 0;
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: center;
            align-content: stretch;
        }
        header h1 img{
            width:50px;
            margin-right:20px;
        }
        nav a {
            color: white; text-decoration: none; margin-left: 20px;
        }
        nav a:hover {
            text-decoration: underline;
        }

        .footer {
            background-color: firebrick;
            color: white;
            text-align: center;
            padding: 10px;
            position: fixed; bottom: 0; width: 100%;
        }

        /* Landing page login container */
        .landing-container {
            max-width: 500px; margin: 50px auto; background-color: #fff;
            padding: 20px; border-radius: 6px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .landing-container h2 {
            margin-top: 0;
        }
        .error {
            color: red; margin-top: 10px; font-weight: bold;
        }
        label {
            display: block; margin: 8px 0 4px; font-weight: bold;
        }
        input[type="text"], input[type="password"], input[type="email"], textarea,select {
            width: 100%; padding: 8px; margin-bottom: 10px;
            border: 1px solid #ccc; border-radius: 4px;
            box-sizing:border-box;
        }
        button {
            background-color: firebrick; color: #fff;
            border: none; border-radius: 4px;
            padding: 10px 15px; cursor: pointer;
        }
        button:hover {
            background-color: darkred;
        }
        a.toggle-link {
            color: firebrick; cursor: pointer; text-decoration: underline; display: inline-block;
            margin-top: 10px;
        }

        /* Dashboard layout */
        .container {
            display: flex; height: calc(100vh - 60px); /* minus header ~60px */
        }
        .left-pane {
            width: 500px; background-color: #333; color: #fff; padding: 20px; overflow-y: auto;
        }
        .left-pane h3 {
            margin-top: 0;
        }
        .left-pane ul {
            list-style: none; padding-left: 0; margin: 0;
        }
        .left-pane li {
            margin-bottom: 5px;
            margin-left:20px;
        }
        .left-pane a {
            color: #fff; text-decoration: none;
        }
        .left-pane a:hover {
            text-decoration: underline;
        }
        .buttons {
            margin-top: 20px;
        }
        .buttons button {
            width: 100%; margin: 10px 0; padding: 10px;
            border: none; border-radius: 4px; background-color: firebrick; color: #fff;
            text-align: left; cursor: pointer;
        }
        .buttons button:hover {
            background-color: darkred;
        }

        .main-pane {
            flex-grow: 1; background: #fff; padding: 20px; overflow-y: auto;
        }
        table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ccc; padding: 8px; text-align: left;
        }
        video {
            display: block; margin: 10px 0;
            max-width: 66%;
        }

        /* Modals */
        .modal {
            display: none; /* shown via JS */
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center; justify-content: center;
        }
        .modal-content {
            background: #fff; padding: 20px; border-radius: 6px;
            width: 500px; max-width: 90%;
            position: relative;
        }
        .modal-content h2 {
            margin-top: 0;
        }
        .close-btn {
            position: absolute; top: 10px; right: 10px;
            background: #aaa; color: #fff; border: none; border-radius: 50%;
            width: 30px; height: 30px; cursor: pointer; font-weight: bold;
        }
        .close-btn:hover {
            background: #888;
        }
        .footer p{
            margin:0px;
        }
        .left-pane ul li button{
        	width:20px;
        	height:20px;
        	padding:0px;
        	float:right;background:none !important;
        }
        .left-pane ul li button:hover{
        	background:red !important;
        }
        .left-pane li span{
        display:block;
        	margin-bottom:10px;
        }
        .botondescarga{
        	background:green;
        	color:white;
        	padding:10px 20px;
        	border-radius:5px;
        	text-decoration:none;
        }
        .main-pane ul li{
        	display: flex;
	        flex-direction: row;
	        flex-wrap: nowrap;
	        justify-content: space-between;
	        align-items: stretch;
	        align-content: stretch;
	        gap:40px;
        }
    </style>
    <script>
    // Toggle between login & signup forms on the landing page
    function showSignup() {
        document.getElementById('login-box').style.display = 'none';
        document.getElementById('signup-box').style.display = 'block';
    }
    function showLogin() {
        document.getElementById('login-box').style.display = 'block';
        document.getElementById('signup-box').style.display = 'none';
    }
    // Show a modal by ID
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }
    // Hide a modal by ID
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    </script>
</head>
<body>
<header>
    <h1><img src="https://jocarsa.com/static/logo/firebrick.png">jocarsa | firebrick</h1>
    <nav>
        <?php if (isLoggedIn()): ?>
            <a href="?logout">Logout</a>
        <?php endif; ?>
    </nav>
</header>

<!-- If not logged in, show landing page with login (and optional signup link) -->
<?php if (!isLoggedIn()): ?>
    <div class="landing-container">
        <!-- LOGIN BOX -->
        <div id="login-box">
            <h2>Login</h2>
            <form method="post">
                <input type="hidden" name="login_form" value="1">
                <label>Username:</label>
                <input type="text" name="username" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit">Login</button>
            </form>
            <?php if ($error && isset($_POST['login_form'])): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <a class="toggle-link" onclick="showSignup()">Sign Up as Admin</a>
        </div>

        <!-- SIGNUP BOX (admins only) -->
        <div id="signup-box" style="display:none;">
            <h2>Sign Up (Admin)</h2>
            <form method="post">
                <input type="hidden" name="signup_form" value="1">
                <label>Username:</label>
                <input type="text" name="username" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit">Sign Up</button>
            </form>
            <?php if ($error && isset($_POST['signup_form'])): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <a class="toggle-link" onclick="showLogin()">Back to Login</a>
        </div>
    </div>

<!-- If logged in, show the dashboard -->
<?php else: ?>
    <div class="container">
        <!-- LEFT PANE: Folder Tree & Buttons -->
        <div class="left-pane">
            <h3>Carpetas y proyectos</h3>
            <ul><?php renderFolderTree(); ?></ul>
            <?php if (isAdmin()): ?>
            <div class="buttons">
                <button onclick="openModal('create-folder-modal')">+ Nueva carpeta</button>
                <button onclick="openModal('create-project-modal')">+ New Project</button>
                <button onclick="window.location='?customers'">Clientes</button>
            </div>
            <?php endif; ?>
        </div>
        <!-- MAIN PANE -->
        <div class="main-pane">
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['customers']) && isAdmin()): ?>
                <h2>Gestionar clientes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th width="200">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($cust = $customersResult->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cust['username']); ?></td>
                            <td><?php echo htmlspecialchars($cust['name']); ?></td>
                            <td><?php echo htmlspecialchars($cust['email']); ?></td>
                            <td>
                                <button onclick="openModal('edit-customer-<?php echo $cust['id']; ?>')">Editar</button>
                                <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this customer?');">
                                    <input type="hidden" name="delete_customer" value="1">
                                    <input type="hidden" name="id" value="<?php echo $cust['id']; ?>">
                                    <button type="submit">Borrar</button>
                                </form>
                            </td>
                        </tr>
                        <!-- EDIT CUSTOMER MODAL -->
                        <div class="modal" id="edit-customer-<?php echo $cust['id']; ?>">
                            <div class="modal-content">
                                <button class="close-btn" onclick="closeModal('edit-customer-<?php echo $cust['id']; ?>')">&times;</button>
                                <h2>Editar cliente</h2>
                                <form method="post">
                                    <input type="hidden" name="update_customer" value="1">
                                    <input type="hidden" name="id" value="<?php echo $cust['id']; ?>">
                                    <label>Usuario:</label>
                                    <input type="text" name="username" value="<?php echo htmlspecialchars($cust['username']); ?>" required>
                                    <label>Nombre:</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($cust['name']); ?>" required>
                                    <label>Email:</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($cust['email']); ?>" required>
                                    <button type="submit">Actualizar</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- CREATE CUSTOMER (ADMIN) -->
                <div style="margin-top: 30px;">
                    <h3>Crear nuevo cliente</h3>
                    <form method="post">
                        <input type="hidden" name="create_customer" value="1">
                        <label>Usuario:</label>
                        <input type="text" name="username" required>
                        <label>Contraseña:</label>
                        <input type="password" name="password" required>
                        <label>Nombre:</label>
                        <input type="text" name="name" required>
                        <label>Email:</label>
                        <input type="email" name="email" required>
                        <button type="submit">Create</button>
                    </form>
                </div>
            <?php elseif ($selected_project): ?>
                <!-- SHOW SELECTED PROJECT -->
                <h2><?php echo htmlspecialchars($selected_project['title']); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($selected_project['description'])); ?></p>
                <h3>Iteraciones</h3>
                <?php if ($iterations): ?>
                    <ul style="list-style:none;padding:0;">
                    <?php while ($iteration = $iterations->fetchArray(SQLITE3_ASSOC)): ?>
                        <li style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
                        	 <video controls>
                                <source src="<?php echo htmlspecialchars($iteration['file_url']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                            <div class="texto">
                            <h4><?php echo htmlspecialchars($iteration['title']); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars($iteration['description'])); ?></p>
                            <?php if ($iteration['file_url']): ?>
                            <!-- Direct Download Link -->
                            <p>
                                <a href="<?php echo htmlspecialchars($iteration['file_url']); ?>" 
                                   download
                                   class="botondescarga">
                                   Descargar video
                                </a>
                            </p>
                            <?php endif; ?>

                            <!-- If customer, allow comment -->
                            <?php if (isCustomer()): ?>
                            <form method="post">
                                <input type="hidden" name="comment" value="1">
                                <input type="hidden" name="iteration_id" value="<?php echo $iteration['id']; ?>">
                                <label>Comentario:</label>
                                <textarea name="comment" required></textarea>
                                <button type="submit">Enviar</button>
                            </form>
                            <?php endif; ?>

                            <!-- Comments -->
                            <ul>
                            <?php
                            $commentQ = $db->query("SELECT * FROM comments WHERE iteration_id=" . (int)$iteration['id'] . " ORDER BY created_at ASC");
                            while ($com = $commentQ->fetchArray(SQLITE3_ASSOC)):
                            ?>
                                <li>
                                    <p><?php echo nl2br(htmlspecialchars($com['comment'])); ?></p>
                                    <small>
                                        Comentario del cliente: #<?php echo $com['customer_id']; ?> 
                                        en <?php echo $com['created_at']; ?>
                                    </small>
                                </li>
                            <?php endwhile; ?>
                            </ul>

                            <!-- If Admin, can Edit or Delete iteration -->
                            <?php if (isAdmin()): ?>
                                <div style="margin-top:10px;">
                                    <button onclick="openModal('edit-iteration-<?php echo $iteration['id']; ?>')">Edit</button>
                                    <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this iteration?');">
                                        <input type="hidden" name="delete_iteration" value="1">
                                        <input type="hidden" name="iteration_id" value="<?php echo $iteration['id']; ?>">
                                        <input type="hidden" name="parent_project_id" value="<?php echo $selected_project['id']; ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>

                                <!-- EDIT ITERATION MODAL -->
                                <div class="modal" id="edit-iteration-<?php echo $iteration['id']; ?>">
                                    <div class="modal-content">
                                        <button class="close-btn" onclick="closeModal('edit-iteration-<?php echo $iteration['id']; ?>')">&times;</button>
                                        <h2>Edit Iteration</h2>
                                        <form method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="update_iteration" value="1">
                                            <input type="hidden" name="iteration_id" value="<?php echo $iteration['id']; ?>">
                                            <input type="hidden" name="parent_project_id" value="<?php echo $selected_project['id']; ?>">
                                            <label>Title:</label>
                                            <input type="text" name="title" value="<?php echo htmlspecialchars($iteration['title']); ?>" required>
                                            <label>Description:</label>
                                            <textarea name="description" required><?php echo htmlspecialchars($iteration['description']); ?></textarea>
                                            <label>Replace file (optional):</label>
                                            <input type="file" name="file">
                                            <button type="submit">Update Iteration</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>Todavía no hay iteraciones.</p>
                <?php endif; ?>

                <!-- Admin can create iteration -->
                <?php if (isAdmin()): ?>
                    <hr>
                    <h3>Crear nueva iteración</h3>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="create_iteration" value="1">
                        <input type="hidden" name="project_id" value="<?php echo $selected_project['id']; ?>">
                        <label>Título:</label>
                        <input type="text" name="title" required>
                        <label>Descripción:</label>
                        <textarea name="description" required></textarea>
                        <label>Archivo:</label>
                        <input type="file" name="file" required>
                        <button type="submit">Crear iteración</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <!-- If no specific project or customers requested, you can show a default message -->
                <h2>Bienvenidos al escritorio</h2>
                <p>Selecciona contenido de la izquierda</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- CREATE FOLDER MODAL (ADMIN) -->
    <?php if (isAdmin()): ?>
    <div class="modal" id="create-folder-modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal('create-folder-modal')">&times;</button>
            <h2>Crear carpeta</h2>
            <form method="post">
                <input type="hidden" name="create_folder" value="1">
                <label>Nombre de la carpeta:</label>
                <input type="text" name="name" required>
                <label>Carpeta superior:</label>
                <select name="parent_id">
                    <option value="">(Sin superior)</option>
                    <?php
                    // For the dropdown
                    $allFolders = $db->query("SELECT * FROM folders ORDER BY name ASC");
                    while ($fo = $allFolders->fetchArray(SQLITE3_ASSOC)) {
                        echo "<option value='{$fo['id']}'>" . htmlspecialchars($fo['name']) . "</option>";
                    }
                    ?>
                </select>
                <button type="submit">Create Folder</button>
            </form>
        </div>
    </div>

    <!-- CREATE PROJECT MODAL (ADMIN) -->
    <div class="modal" id="create-project-modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal('create-project-modal')">&times;</button>
            <h2>Crear proyecto</h2>
            <form method="post">
                <input type="hidden" name="create_project" value="1">
                <label>Carpeta:</label>
                <select name="folder_id" required>
                    <option value="">-- Selecciona una carpeta --</option>
                    <?php
                    $allFolders2 = $db->query("SELECT * FROM folders ORDER BY name ASC");
                    while ($fo2 = $allFolders2->fetchArray(SQLITE3_ASSOC)) {
                        echo "<option value='{$fo2['id']}'>" . htmlspecialchars($fo2['name']) . "</option>";
                    }
                    ?>
                </select>
                <label>Título del proyecto:</label>
                <input type="text" name="title" required>
                <label>Descripción:</label>
                <textarea name="description" required></textarea>
                <button type="submit">Crear proyecto</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<div class="footer">
    <p>&copy; <?php echo date('Y'); ?> jocarsa | firebrick</p>
</div>
</body>
</html>

