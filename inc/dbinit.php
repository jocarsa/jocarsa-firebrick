<?php
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

// Create separate table for iteration creation dates
$db->exec("CREATE TABLE IF NOT EXISTS iteration_dates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    iteration_id INTEGER UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(iteration_id) REFERENCES iterations(id)
)");
?>

