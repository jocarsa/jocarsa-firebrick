<?php
/*******************************
 * projects.db + table creation
 *******************************/
$db = new SQLite3('../databases/firebrick.db');

include "inc/dbinit.php";

session_start();

/********************************
 * AUTH FUNCTIONS
 ********************************/
include "funciones/login.php";
include "funciones/isLoggedIn.php";
include "funciones/isAdmin.php";
include "funciones/isCustomer.php";
include "funciones/logout.php";

include "inc/foldunfold.php";

/********************************
 * HANDLE FORM SUBMISSIONS
 ********************************/
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- LOGIN ----
    if (isset($_POST['login_form'])) {
        include "router/loginform.php";
    }
    // ---- SIGNUP (ADMIN ONLY) ----
    elseif (isset($_POST['signup_form'])) {
        include "router/signupform.php";
    }
    // ---- CREATE FOLDER (ADMIN) ----
    elseif (isset($_POST['create_folder']) && isAdmin()) {
        include "router/createfolder.php";
    }
    // ---- DELETE FOLDER (ADMIN) ----
    elseif (isset($_POST['delete_folder']) && isAdmin()) {
        include "router/deletefolder.php";
    }
    // ---- CREATE PROJECT (ADMIN) ----
    elseif (isset($_POST['create_project']) && isAdmin()) {
        include "router/createproject.php";
    }
    // ---- DELETE PROJECT (ADMIN) ----
    elseif (isset($_POST['delete_project']) && isAdmin()) {
        include "router/deleteproject.php";
    }
    // ---- CREATE ITERATION (ADMIN) ----
    elseif (isset($_POST['create_iteration']) && isAdmin()) {
        include "router/createiteration.php";
    }
    // ---- DELETE ITERATION (ADMIN) ----
    elseif (isset($_POST['delete_iteration']) && isAdmin()) {
        include "router/deleteiteration.php";
    }
    // ---- UPDATE ITERATION (ADMIN) ----
    elseif (isset($_POST['update_iteration']) && isAdmin()) {
       include "router/updateiteration.php";
    }
    // ---- CREATE / UPDATE / DELETE CUSTOMER (ADMIN) ----
    elseif (isset($_POST['create_customer']) && isAdmin()) {
        include "router/createcustomer.php";
    }
    elseif (isset($_POST['update_customer']) && isAdmin()) {
        include "router/updatecustomer.php";
    }
    elseif (isset($_POST['delete_customer']) && isAdmin()) {
        include "router/deletecustomer.php";
    }
    // ---- ADD COMMENT (CUSTOMER) ----
    elseif (isset($_POST['comment']) && isCustomer()) {
        include "router/addcomment.php";
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
        $iterations = $db->query("
            SELECT iterations.*, iteration_dates.created_at 
            FROM iterations 
            LEFT JOIN iteration_dates ON iterations.id = iteration_dates.iteration_id 
            WHERE project_id = $pid
        ");
    }
}

include "bloques/foldertree.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>jocarsa | firebrick</title>
    <link rel="icon" type="image/svg+xml" href="https://jocarsa.com/static/logo/firebrick.png" />
    <link rel="stylesheet" href="css/estilo.css">
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
    <?php include "bloques/loginbox.php";?>

<!-- If logged in, show the dashboard -->
<?php else: ?>
    <?php include "bloques/escritorio.php";?>

    <!-- CREATE FOLDER MODAL (ADMIN) -->
    <?php if (isAdmin()): ?>
    <?php include "bloques/modal.php";?>
    <?php endif; ?>
<?php endif; ?>

<div class="footer">
    <p>&copy; <?php echo date('Y'); ?> jocarsa | firebrick</p>
</div>
</body>
</html>

