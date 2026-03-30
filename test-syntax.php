<?php
// WordPress ortamını yükle
define('ABSPATH', '/Applications/MAMP/htdocs/wordpress/');
require_once ABSPATH . 'wp-load.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing plugin files...\n\n";

// Ana dosya
echo "Loading main plugin file...\n";
try {
    require_once '/Applications/MAMP/htdocs/PubMed-Wordpress-Plugin/pubmed-health-importer.php';
    echo "✓ Main plugin file loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Error in main plugin file: " . $e->getMessage() . "\n";
}

echo "\nAll tests completed!\n";
