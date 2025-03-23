<?php
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
?>
