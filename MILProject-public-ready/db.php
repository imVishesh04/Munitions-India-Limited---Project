<?php
// Local-development defaults are intended for XAMPP/WAMP only.
// Configure DB_HOST, DB_USER, DB_PASS and DB_NAME in the server environment for other setups.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'document_portal_demo');

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('Document Portal Demo database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die('<div style="font-family:Arial;padding:30px;color:#8b1a1a;">'
        . '<h3>Database connection failed</h3>'
        . '<p>Check your local database setup and configuration.</p>'
        . '</div>');
}
$conn->set_charset('utf8mb4');
?>
