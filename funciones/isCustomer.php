<?php
function isCustomer() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'customer');
}
?>
