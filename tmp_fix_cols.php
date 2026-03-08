<?php
require 'config/db.php';
$sqls = [
    "ALTER TABLE catering_requests ADD COLUMN IF NOT EXISTS guest_count INT DEFAULT 0 AFTER area",
    "ALTER TABLE catering_requests ADD COLUMN IF NOT EXISTS purpose TEXT AFTER guest_count",
    "ALTER TABLE catering_requests ADD COLUMN IF NOT EXISTS special_instructions TEXT AFTER purpose"
];

foreach ($sqls as $sql) {
    if (executeQuery($sql)) {
        echo "Executed: $sql\n";
    } else {
        echo "Failed: $sql\n";
    }
}
?>
