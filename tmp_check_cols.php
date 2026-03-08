<?php
require 'config/db.php';
$res = fetchAll('DESCRIBE catering_requests');
foreach ($res as $row) {
    echo $row['Field'] . " ";
}
?>
