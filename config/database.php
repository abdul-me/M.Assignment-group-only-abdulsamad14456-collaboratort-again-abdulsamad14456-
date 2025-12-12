<?php
/**
 * Database Configuration & Connection
 * Secure database connection with error handling
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lms_db');
define('DB_PORT', 3306);

// Establish connection with MySQLi
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log error to file instead of displaying to user
    error_log("Database connection error: " . $e->getMessage(), 3, __DIR__ . "/../logs/error.log");
    die("Database connection error. Please contact administrator.");
}

/**
 * Prepared Statement Helper - Prevents SQL Injection
 * Usage: $result = executeQuery($conn, "SELECT * FROM users WHERE id = ?", "i", [$user_id]);
 */
function executeQuery($conn, $query, $types = "", $params = []) {
    try {
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt;
        
    } catch (Exception $e) {
        error_log("Query execution error: " . $e->getMessage(), 3, __DIR__ . "/../logs/error.log");
        return null;
    }
}

/**
 * Get single row result
 */
function getRow($conn, $query, $types = "", $params = []) {
    $stmt = executeQuery($conn, $query, $types, $params);
    
    if ($stmt) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }
    
    return null;
}

/**
 * Get multiple rows
 */
function getRows($conn, $query, $types = "", $params = []) {
    $stmt = executeQuery($conn, $query, $types, $params);
    
    if ($stmt) {
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
    
    return [];
}

/**
 * Insert/Update/Delete operations
 * Returns affected rows count
 */
function executeUpdate($conn, $query, $types = "", $params = []) {
    $stmt = executeQuery($conn, $query, $types, $params);
    
    if ($stmt) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }
    
    return 0;
}

/**
 * Get last inserted ID
 */
function getLastInsertId($conn) {
    return $conn->insert_id;
}
?>
