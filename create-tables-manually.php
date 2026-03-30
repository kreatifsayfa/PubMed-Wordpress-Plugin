<?php
/**
 * Manuel tablo oluşturma scripti
 *
 * Bu dosyayı tarayıcıdan bir kez çalıştırın: http://siteniz.com/wp-content/plugins/PubMed-Wordpress-Plugin/create-tables-manually.php
 * Sonra bu dosyayı silebilirsiniz.
 */

// WordPress'i yükle
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>PubMed Health Importer - Tablo Oluşturma</h1>";
echo "<p>Tablolar oluşturuluyor...</p>";

$charset_collate = $wpdb->get_charset_collate();

// Tablo 1: pubmed_cache
$table_name_cache = $wpdb->prefix . 'pubmed_cache';
$sql_cache = "CREATE TABLE IF NOT EXISTS $table_name_cache (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    query_hash varchar(32) NOT NULL,
    query_data longtext NOT NULL,
    response_data longtext NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at datetime NOT NULL,
    PRIMARY KEY  (id),
    KEY query_hash (query_hash),
    KEY expires_at (expires_at)
) $charset_collate;";

$result1 = $wpdb->query($sql_cache);
echo $result1 !== false ? "✓ Tablo '$table_name_cache' oluşturuldu<br>" : "✗ Hata: '$table_name_cache' oluşturulamadı<br>";

// Tablo 2: pubmed_searches
$table_name_searches = $wpdb->prefix . 'pubmed_searches';
$sql_searches = "CREATE TABLE IF NOT EXISTS $table_name_searches (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    description text,
    search_params longtext NOT NULL,
    schedule varchar(50),
    last_run datetime,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id)
) $charset_collate;";

$result2 = $wpdb->query($sql_searches);
echo $result2 !== false ? "✓ Tablo '$table_name_searches' oluşturuldu<br>" : "✗ Hata: '$table_name_searches' oluşturulamadı<br>";

// Tablo 3: pubmed_articles
$table_name_articles = $wpdb->prefix . 'pubmed_articles';
$sql_articles = "CREATE TABLE IF NOT EXISTS $table_name_articles (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    pubmed_id varchar(20) NOT NULL,
    post_id bigint(20),
    title text NOT NULL,
    authors text,
    abstract longtext,
    publication_date date,
    journal varchar(255),
    mesh_terms text,
    imported_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY pubmed_id (pubmed_id),
    KEY post_id (post_id)
) $charset_collate;";

$result3 = $wpdb->query($sql_articles);
echo $result3 !== false ? "✓ Tablo '$table_name_articles' oluşturuldu<br>" : "✗ Hata: '$table_name_articles' oluşturulamadı<br>";

echo "<br><h3>Tamamlandı!</h3>";
echo "<p>Tabloları kontrol etmek için:</p>";
echo "<pre>";
echo "Tablo 1: " . $table_name_cache . " - " . ($wpdb->get_var("SHOW TABLES LIKE '$table_name_cache'") ? "Var ✓" : "Yok ✗") . "\n";
echo "Tablo 2: " . $table_name_searches . " - " . ($wpdb->get_var("SHOW TABLES LIKE '$table_name_searches'") ? "Var ✓" : "Yok ✗") . "\n";
echo "Tablo 3: " . $table_name_articles . " - " . ($wpdb->get_var("SHOW TABLES LIKE '$table_name_articles'") ? "Var ✓" : "Yok ✗") . "\n";
echo "</pre>";

echo "<p><strong>NOT:</strong> Bu dosyayı artık silebilirsiniz.</p>";
