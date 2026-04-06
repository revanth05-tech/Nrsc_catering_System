<?php
require_once __DIR__ . '/config/db.php';
$notifs = fetchAll("SELECT * FROM notifications");
file_put_contents('tmp_notifs.json', json_encode($notifs, JSON_PRETTY_PRINT));
