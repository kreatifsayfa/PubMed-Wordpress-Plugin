<?php
/**
 * Frontend sınıfı
 *
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/public
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend sınıfı
 */
class PubMed_Health_Importer_Public {

    /**
     * Constructor
     */
    public function __construct() {
        // Frontend stil ve script yükleme
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Frontend stillerini yükle
     */
    public function enqueue_styles() {
        // İhtiyaç duyulursa eklenebilir
        // wp_enqueue_style('pubmed-health-importer-public', PUBMED_HEALTH_IMPORTER_URL . 'public/css/public.css', array(), PUBMED_HEALTH_IMPORTER_VERSION);
    }

    /**
     * Frontend scriptlerini yükle
     */
    public function enqueue_scripts() {
        // İhtiyaç duyulursa eklenebilir
        // wp_enqueue_script('pubmed-health-importer-public', PUBMED_HEALTH_IMPORTER_URL . 'public/js/public.js', array('jquery'), PUBMED_HEALTH_IMPORTER_VERSION, true);
    }
}

// Sınıfı başlat
new PubMed_Health_Importer_Public();
