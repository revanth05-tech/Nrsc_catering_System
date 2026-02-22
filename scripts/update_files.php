<?php
$files = [
    'c:\xampp\htdocs\catering_system\sections\officer\profile.php',
    'c:\xampp\htdocs\catering_system\sections\canteen\profile.php',
    'c:\xampp\htdocs\catering_system\sections\admin\profile.php',
    'c:\xampp\htdocs\catering_system\officer\update_status.php',
    'c:\xampp\htdocs\catering_system\officer\dashboard.php',
    'c:\xampp\htdocs\catering_system\officer\completed_orders.php',
    'c:\xampp\htdocs\catering_system\officer\approved_orders.php',
    'c:\xampp\htdocs\catering_system\canteen\dashboard.php',
    'c:\xampp\htdocs\catering_system\admin\manage_users.php',
    'c:\xampp\htdocs\catering_system\admin\dashboard.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "Processing $file...\n";
        $content = file_get_contents($file);
        $newContent = str_replace('full_name', 'name', $content);
        if ($content !== $newContent) {
            file_put_contents($file, $newContent);
            echo "Updated $file.\n";
        } else {
            echo "No changes needed for $file.\n";
        }
    } else {
        echo "File not found: $file\n";
    }
}
?>
