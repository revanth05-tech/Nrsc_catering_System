<?php
require 'config/db.php';
$res = fetchAll('DESCRIBE catering_requests');
$cols = array_column($res, 'Field');
echo implode(', ', $cols);
?>
