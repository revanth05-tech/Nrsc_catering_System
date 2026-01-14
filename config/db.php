<?php
/**
 * NRSC Catering System - Database Connection
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nrsc_catering');

// Create connection
$conn = null;

function getConnection() {
    global $conn;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
                throw new Exception("Database connection failed. Please check your configuration.");
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("<div style='padding:20px;background:#f8d7da;color:#721c24;border-radius:5px;margin:20px;'>
                    <h3>Database Connection Error</h3>
                    <p>{$e->getMessage()}</p>
                    <p>Please ensure:</p>
                    <ul>
                        <li>MySQL/MariaDB is running in XAMPP</li>
                        <li>Database 'nrsc_catering' exists</li>
                        <li>Import the SQL file from /database/nrsc_catering.sql</li>
                    </ul>
                </div>");
        }
    }
    
    return $conn;
}

/**
 * Execute a prepared statement
 */
function executeQuery($sql, $params = [], $types = '') {
    $conn = getConnection();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

/**
 * Fetch single row
 */
function fetchOne($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    if (!$stmt) return null;
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Fetch all rows
 */
function fetchAll($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    if (!$stmt) return [];
    
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Insert and get last ID
 */
function insertAndGetId($sql, $params = [], $types = '') {
    $conn = getConnection();
    $stmt = executeQuery($sql, $params, $types);
    if (!$stmt) return false;
    
    $lastId = $conn->insert_id;
    $stmt->close();
    return $lastId;
}

/**
 * Execute and get affected rows
 */
function executeAndGetAffected($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    if (!$stmt) return false;
    
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}
?>
