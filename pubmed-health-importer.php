<?php
/**
 * Plugin Name: PubMed Health Importer
 * Plugin URI: https://regl.net.tr
 * Description: Premium WordPress eklentisi - PubMed'den kadın ve bebek sağlığı içeriklerini otomatik olarak çeker, SEO odaklı içerik oluşturur ve Google featured snippet elde etmeyi optimize eder.
 * Version: 1.0.0
 * Author: regl.net.tr
 * Author URI: https://regl.net.tr
 * Text Domain: pubmed-health-importer
 * Domain Path: /languages
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Plugin sürümü
define('PUBMED_HEALTH_IMPORTER_VERSION', '1.0.0');

// Plugin yolu
define('PUBMED_HEALTH_IMPORTER_PATH', plugin_dir_path(__FILE__));

// Plugin URL'si
define('PUBMED_HEALTH_IMPORTER_URL', plugin_dir_url(__FILE__));

// Plugin ana sınıfı
class PubMed_Health_Importer {

    /**
     * Singleton instance
     *
     * @var PubMed_Health_Importer
     */
    private static $instance = null;

    /**
     * Singleton pattern için instance alma
     *
     * @return PubMed_Health_Importer
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Eklenti aktivasyon ve deaktivasyon kancaları
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Eklenti başlatma
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Eklenti aktivasyonu
     */
    public function activate() {
        // Veritabanı tablolarını oluştur
        $this->create_tables();

        // Varsayılan ayarları oluştur
        $this->create_default_settings();

        // Özel yazı tiplerini kaydet
        $this->register_post_types();
        flush_rewrite_rules();
    }

    /**
     * Eklenti deaktivasyonu
     */
    public function deactivate() {
        // Geçici verileri temizle
        $this->clear_transients();
        
        // Rewrite kurallarını temizle
        flush_rewrite_rules();
    }

    /**
     * Veritabanı tablolarını oluştur
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // PubMed önbellek tablosu
        $table_name = $wpdb->prefix . 'pubmed_cache';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
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
        
        // PubMed arama tablosu
        $table_name_searches = $wpdb->prefix . 'pubmed_searches';
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name_searches (
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
        
        // PubMed makale meta verileri tablosu
        $table_name_articles = $wpdb->prefix . 'pubmed_articles';
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name_articles (
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Varsayılan ayarları oluştur
     */
    private function create_default_settings() {
        $default_settings = array(
            'api_key' => '',
            'tool' => 'pubmed_health_importer',
            'email' => '',
            'gemini_api_key' => '',
            'cache_duration' => 86400, // 24 saat
            'auto_import' => 'no',
            'auto_publish' => 'no',
            'seo_optimization' => 'yes',
            'featured_snippet_optimization' => 'yes',
            'faq_generation' => 'yes',
            'content_enhancement' => 'yes',
            'default_category' => '',
            'default_author' => 1,
            'mesh_terms' => array(
                "Women's Health",
                "Pregnancy",
                "Pregnancy Complications",
                "Reproductive Health",
                "Maternal Health",
                "Female Genital Diseases",
                "Menstruation",
                "Menopause",
                "Infant Health",
                "Child Health",
                "Pediatrics",
                "Infant Care",
                "Child Development",
                "Infant Nutrition",
                "Infant, Newborn, Diseases"
            )
        );
        
        add_option('pubmed_health_importer_settings', $default_settings);
    }

    /**
     * Özel yazı tiplerini kaydet
     */
    private function register_post_types() {
        // Bu fonksiyon init kancasında çağrılacak
    }

    /**
     * Geçici verileri temizle
     */
    private function clear_transients() {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_pubmed_health_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_pubmed_health_%'");
    }

    /**
     * Eklentiyi başlat
     */
    public function init() {
        // Dil dosyalarını yükle
        load_plugin_textdomain('pubmed-health-importer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Gerekli dosyaları dahil et
        $this->includes();
        
        // Admin kancalarını ekle
        if (is_admin()) {
            $this->admin_hooks();
        }
        
        // Ön yüz kancalarını ekle
        $this->frontend_hooks();
        
        // Özel yazı tiplerini kaydet
        $this->register_custom_post_types();
        
        // Kısa kodları kaydet
        $this->register_shortcodes();
        
        // AJAX işleyicilerini kaydet
        $this->register_ajax_handlers();
    }

    /**
     * Gerekli dosyaları dahil et
     */
    private function includes() {
        // API sınıfı
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'includes/class-pubmed-api.php';
        
        // İçerik işleme sınıfı
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'includes/class-content-processor.php';
        
        // SEO optimizasyon sınıfı
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'includes/class-seo-optimizer.php';
        
        // Gemini AI entegrasyon sınıfı
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'includes/class-gemini-ai.php';
        
        // Yardımcı fonksiyonlar
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'includes/functions.php';
        
        // Admin sınıfı
        if (is_admin()) {
            require_once PUBMED_HEALTH_IMPORTER_PATH . 'admin/class-admin.php';
        }
        
        // Ön yüz sınıfı
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'public/class-public.php';
    }

    /**
     * Admin kancalarını ekle
     */
    private function admin_hooks() {
        // Admin menüsü
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin scriptleri ve stilleri
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Ayarlar bağlantısı
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    /**
     * Ön yüz kancalarını ekle
     */
    private function frontend_hooks() {
        // Ön yüz scriptleri ve stilleri
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    /**
     * Özel yazı tiplerini kaydet
     */
    private function register_custom_post_types() {
        // Özel yazı tipi: PubMed Makalesi
        $labels = array(
            'name'                  => _x('PubMed Makaleleri', 'Post type general name', 'pubmed-health-importer'),
            'singular_name'         => _x('PubMed Makalesi', 'Post type singular name', 'pubmed-health-importer'),
            'menu_name'             => _x('PubMed Makaleleri', 'Admin Menu text', 'pubmed-health-importer'),
            'name_admin_bar'        => _x('PubMed Makalesi', 'Add New on Toolbar', 'pubmed-health-importer'),
            'add_new'               => __('Yeni Ekle', 'pubmed-health-importer'),
            'add_new_item'          => __('Yeni PubMed Makalesi Ekle', 'pubmed-health-importer'),
            'new_item'              => __('Yeni PubMed Makalesi', 'pubmed-health-importer'),
            'edit_item'             => __('PubMed Makalesini Düzenle', 'pubmed-health-importer'),
            'view_item'             => __('PubMed Makalesini Görüntüle', 'pubmed-health-importer'),
            'all_items'             => __('Tüm PubMed Makaleleri', 'pubmed-health-importer'),
            'search_items'          => __('PubMed Makalelerini Ara', 'pubmed-health-importer'),
            'parent_item_colon'     => __('Üst PubMed Makaleleri:', 'pubmed-health-importer'),
            'not_found'             => __('PubMed Makalesi bulunamadı.', 'pubmed-health-importer'),
            'not_found_in_trash'    => __('Çöp kutusunda PubMed Makalesi bulunamadı.', 'pubmed-health-importer'),
            'featured_image'        => _x('PubMed Makalesi Öne Çıkan Görseli', 'Overrides the "Featured Image" phrase', 'pubmed-health-importer'),
            'set_featured_image'    => _x('Öne çıkan görsel ayarla', 'Overrides the "Set featured image" phrase', 'pubmed-health-importer'),
            'remove_featured_image' => _x('Öne çıkan görseli kaldır', 'Overrides the "Remove featured image" phrase', 'pubmed-health-importer'),
            'use_featured_image'    => _x('Öne çıkan görsel olarak kullan', 'Overrides the "Use as featured image" phrase', 'pubmed-health-importer'),
            'archives'              => _x('PubMed Makalesi Arşivleri', 'The post type archive label used in nav menus', 'pubmed-health-importer'),
            'insert_into_item'      => _x('PubMed Makalesine ekle', 'Overrides the "Insert into post" phrase', 'pubmed-health-importer'),
            'uploaded_to_this_item' => _x('Bu PubMed Makalesine yüklendi', 'Overrides the "Uploaded to this post" phrase', 'pubmed-health-importer'),
            'filter_items_list'     => _x('PubMed Makalelerini filtrele', 'Screen reader text for the filter links heading on the post type listing screen', 'pubmed-health-importer'),
            'items_list_navigation' => _x('PubMed Makaleleri navigasyonu', 'Screen reader text for the pagination heading on the post type listing screen', 'pubmed-health-importer'),
            'items_list'            => _x('PubMed Makaleleri listesi', 'Screen reader text for the items list heading on the post type listing screen', 'pubmed-health-importer'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'pubmed-article'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields'),
            'menu_icon'          => 'dashicons-book-alt',
            'show_in_rest'       => true,
        );
        
        register_post_type('pubmed_article', $args);
        
        // Özel taksonomi: PubMed Kategorileri
        $labels = array(
            'name'              => _x('PubMed Kategorileri', 'taxonomy general name', 'pubmed-health-importer'),
            'singular_name'     => _x('PubMed Kategorisi', 'taxonomy singular name', 'pubmed-health-importer'),
            'search_items'      => __('PubMed Kategorilerini Ara', 'pubmed-health-importer'),
            'all_items'         => __('Tüm PubMed Kategorileri', 'pubmed-health-importer'),
            'parent_item'       => __('Üst PubMed Kategorisi', 'pubmed-health-importer'),
            'parent_item_colon' => __('Üst PubMed Kategorisi:', 'pubmed-health-importer'),
            'edit_item'         => __('PubMed Kategorisini Düzenle', 'pubmed-health-importer'),
            'update_item'       => __('PubMed Kategorisini Güncelle', 'pubmed-health-importer'),
            'add_new_item'      => __('Yeni PubMed Kategorisi Ekle', 'pubmed-health-importer'),
            'new_item_name'     => __('Yeni PubMed Kategorisi Adı', 'pubmed-health-importer'),
            'menu_name'         => __('PubMed Kategorileri', 'pubmed-health-importer'),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'pubmed-category'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy('pubmed_category', array('pubmed_article'), $args);
        
        // Özel taksonomi: PubMed Etiketleri
        $labels = array(
            'name'              => _x('PubMed Etiketleri', 'taxonomy general name', 'pubmed-health-importer'),
            'singular_name'     => _x('PubMed Etiketi', 'taxonomy singular name', 'pubmed-health-importer'),
            'search_items'      => __('PubMed Etiketlerini Ara', 'pubmed-health-importer'),
            'all_items'         => __('Tüm PubMed Etiketleri', 'pubmed-health-importer'),
            'parent_item'       => __('Üst PubMed Etiketi', 'pubmed-health-importer'),
            'parent_item_colon' => __('Üst PubMed Etiketi:', 'pubmed-health-importer'),
            'edit_item'         => __('PubMed Etiketini Düzenle', 'pubmed-health-importer'),
            'update_item'       => __('PubMed Etiketini Güncelle', 'pubmed-health-importer'),
            'add_new_item'      => __('Yeni PubMed Etiketi Ekle', 'pubmed-health-importer'),
            'new_item_name'     => __('Yeni PubMed Etiketi Adı', 'pubmed-health-importer'),
            'menu_name'         => __('PubMed Etiketleri', 'pubmed-health-importer'),
        );
        
        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'pubmed-tag'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy('pubmed_tag', array('pubmed_article'), $args);
    }

    /**
     * Kısa kodları kaydet
     */
    private function register_shortcodes() {
        // PubMed arama formu kısa kodu
        add_shortcode('pubmed_search_form', array($this, 'shortcode_search_form'));
        
        // PubMed makale listesi kısa kodu
        add_shortcode('pubmed_article_list', array($this, 'shortcode_article_list'));
        
        // PubMed popüler makaleler kısa kodu
        add_shortcode('pubmed_popular_articles', array($this, 'shortcode_popular_articles'));
        
        // PubMed ilgili makaleler kısa kodu
        add_shortcode('pubmed_related_articles', array($this, 'shortcode_related_articles'));
        
        // PubMed FAQ görüntüleme kısa kodu
        add_shortcode('pubmed_faq', array($this, 'shortcode_faq'));
    }

    /**
     * AJAX işleyicilerini kaydet
     */
    private function register_ajax_handlers() {
        // PubMed arama AJAX işleyicisi
        add_action('wp_ajax_pubmed_search', array($this, 'ajax_pubmed_search'));
        add_action('wp_ajax_nopriv_pubmed_search', array($this, 'ajax_pubmed_search'));
        
        // PubMed içe aktarma AJAX işleyicisi
        add_action('wp_ajax_pubmed_import', array($this, 'ajax_pubmed_import'));
        
        // PubMed içerik zenginleştirme AJAX işleyicisi
        add_action('wp_ajax_pubmed_enhance_content', array($this, 'ajax_pubmed_enhance_content'));
    }

    /**
     * Admin menüsü ekle
     */
    public function add_admin_menu() {
        // Ana menü
        add_menu_page(
            __('PubMed Health Importer', 'pubmed-health-importer'),
            __('PubMed Health', 'pubmed-health-importer'),
            'manage_options',
            'pubmed-health-importer',
            array($this, 'admin_page'),
            'dashicons-book-alt',
            30
        );
        
        // Alt menüler
        add_submenu_page(
            'pubmed-health-importer',
            __('PubMed Arama', 'pubmed-health-importer'),
            __('Arama', 'pubmed-health-importer'),
            'manage_options',
            'pubmed-health-importer-search',
            array($this, 'admin_search_page')
        );
        
        add_submenu_page(
            'pubmed-health-importer',
            __('İçe Aktarılan Makaleler', 'pubmed-health-importer'),
            __('Makaleler', 'pubmed-health-importer'),
            'manage_options',
            'pubmed-health-importer-articles',
            array($this, 'admin_articles_page')
        );
        
        add_submenu_page(
            'pubmed-health-importer',
            __('Zamanlanmış Aramalar', 'pubmed-health-importer'),
            __('Zamanlanmış Aramalar', 'pubmed-health-importer'),
            'manage_options',
            'pubmed-health-importer-scheduled',
            array($this, 'admin_scheduled_page')
        );
        
        add_submenu_page(
            'pubmed-health-importer',
            __('Ayarlar', 'pubmed-health-importer'),
            __('Ayarlar', 'pubmed-health-importer'),
            'manage_options',
            'pubmed-health-importer-settings',
            array($this, 'admin_settings_page')
        );
    }

    /**
     * Admin scriptleri ve stillerini ekle
     */
    public function enqueue_admin_scripts($hook) {
        // Sadece eklenti sayfalarında yükle
        if (strpos($hook, 'pubmed-health-importer') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style('pubmed-health-importer-admin', PUBMED_HEALTH_IMPORTER_URL . 'admin/css/admin.css', array(), PUBMED_HEALTH_IMPORTER_VERSION);
        
        // JavaScript
        wp_enqueue_script('pubmed-health-importer-admin', PUBMED_HEALTH_IMPORTER_URL . 'admin/js/admin.js', array('jquery'), PUBMED_HEALTH_IMPORTER_VERSION, true);
        
        // AJAX için localize script
        wp_localize_script('pubmed-health-importer-admin', 'pubmed_health_importer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pubmed_health_importer_nonce'),
            'searching' => __('Aranıyor...', 'pubmed-health-importer'),
            'importing' => __('İçe Aktarılıyor...', 'pubmed-health-importer'),
            'enhancing' => __('İçerik Zenginleştiriliyor...', 'pubmed-health-importer'),
            'success' => __('Başarılı!', 'pubmed-health-importer'),
            'error' => __('Hata!', 'pubmed-health-importer')
        ));
    }

    /**
     * Ön yüz scriptleri ve stillerini ekle
     */
    public function enqueue_frontend_scripts() {
        // CSS
        wp_enqueue_style('pubmed-health-importer-public', PUBMED_HEALTH_IMPORTER_URL . 'public/css/public.css', array(), PUBMED_HEALTH_IMPORTER_VERSION);
        
        // JavaScript
        wp_enqueue_script('pubmed-health-importer-public', PUBMED_HEALTH_IMPORTER_URL . 'public/js/public.js', array('jquery'), PUBMED_HEALTH_IMPORTER_VERSION, true);
        
        // AJAX için localize script
        wp_localize_script('pubmed-health-importer-public', 'pubmed_health_importer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pubmed_health_importer_nonce'),
            'searching' => __('Aranıyor...', 'pubmed-health-importer'),
            'loading' => __('Yükleniyor...', 'pubmed-health-importer'),
            'success' => __('Başarılı!', 'pubmed-health-importer'),
            'error' => __('Hata!', 'pubmed-health-importer')
        ));
    }

    /**
     * Ayarlar bağlantısı ekle
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=pubmed-health-importer-settings') . '">' . __('Ayarlar', 'pubmed-health-importer') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Ana admin sayfası
     */
    public function admin_page() {
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'admin/partials/admin-display.php';
    }

    /**
     * Arama admin sayfası
     */
    public function admin_search_page() {
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'admin/partials/admin-search.php';
    }

    /**
     * Makaleler admin sayfası
     */
    public function admin_articles_page() {
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'admin/partials/admin-articles.php';
    }

    /**
     * Zamanlanmış aramalar admin sayfası
     */
    public function admin_scheduled_page() {
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'admin/partials/admin-scheduled.php';
    }

    /**
     * Ayarlar admin sayfası
     */
    public function admin_settings_page() {
        require_once PUBMED_HEALTH_IMPORTER_PATH . 'admin/partials/admin-settings.php';
    }

    /**
     * PubMed arama formu kısa kodu
     */
    public function shortcode_search_form($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('PubMed\'de ara...', 'pubmed-health-importer'),
            'button_text' => __('Ara', 'pubmed-health-importer'),
            'results_count' => 10,
        ), $atts, 'pubmed_search_form');
        
        ob_start();
        require PUBMED_HEALTH_IMPORTER_PATH . 'public/partials/shortcode-search-form.php';
        return ob_get_clean();
    }

    /**
     * PubMed makale listesi kısa kodu
     */
    public function shortcode_article_list($atts) {
        $atts = shortcode_atts(array(
            'count' => 10,
            'category' => '',
            'tag' => '',
            'orderby' => 'date',
            'order' => 'DESC',
        ), $atts, 'pubmed_article_list');
        
        ob_start();
        require PUBMED_HEALTH_IMPORTER_PATH . 'public/partials/shortcode-article-list.php';
        return ob_get_clean();
    }

    /**
     * PubMed popüler makaleler kısa kodu
     */
    public function shortcode_popular_articles($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'period' => 'month', // day, week, month, year, all
        ), $atts, 'pubmed_popular_articles');
        
        ob_start();
        require PUBMED_HEALTH_IMPORTER_PATH . 'public/partials/shortcode-popular-articles.php';
        return ob_get_clean();
    }

    /**
     * PubMed ilgili makaleler kısa kodu
     */
    public function shortcode_related_articles($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'post_id' => 0,
        ), $atts, 'pubmed_related_articles');
        
        if ($atts['post_id'] == 0 && is_singular()) {
            $atts['post_id'] = get_the_ID();
        }
        
        ob_start();
        require PUBMED_HEALTH_IMPORTER_PATH . 'public/partials/shortcode-related-articles.php';
        return ob_get_clean();
    }

    /**
     * PubMed FAQ görüntüleme kısa kodu
     */
    public function shortcode_faq($atts) {
        $atts = shortcode_atts(array(
            'post_id' => 0,
        ), $atts, 'pubmed_faq');
        
        if ($atts['post_id'] == 0 && is_singular()) {
            $atts['post_id'] = get_the_ID();
        }
        
        ob_start();
        require PUBMED_HEALTH_IMPORTER_PATH . 'public/partials/shortcode-faq.php';
        return ob_get_clean();
    }

    /**
     * PubMed arama AJAX işleyicisi
     */
    public function ajax_pubmed_search() {
        // Nonce kontrolü
        check_ajax_referer('pubmed_health_importer_nonce', 'nonce');
        
        // Parametreleri al
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $count = isset($_POST['count']) ? intval($_POST['count']) : 10;
        
        if (empty($query)) {
            wp_send_json_error(array('message' => __('Arama sorgusu boş olamaz.', 'pubmed-health-importer')));
        }
        
        // PubMed API sınıfını başlat
        $pubmed_api = new PubMed_API();
        
        // Arama yap
        $results = $pubmed_api->search($query, $count);
        
        if (is_wp_error($results)) {
            wp_send_json_error(array('message' => $results->get_error_message()));
        }
        
        wp_send_json_success(array('results' => $results));
    }

    /**
     * PubMed içe aktarma AJAX işleyicisi
     */
    public function ajax_pubmed_import() {
        // Nonce kontrolü
        check_ajax_referer('pubmed_health_importer_nonce', 'nonce');
        
        // Yönetici yetkisi kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'pubmed-health-importer')));
        }
        
        // Parametreleri al
        $pubmed_id = isset($_POST['pubmed_id']) ? sanitize_text_field($_POST['pubmed_id']) : '';
        
        if (empty($pubmed_id)) {
            wp_send_json_error(array('message' => __('PubMed ID boş olamaz.', 'pubmed-health-importer')));
        }
        
        // PubMed API sınıfını başlat
        $pubmed_api = new PubMed_API();
        
        // Makale detaylarını al
        $article = $pubmed_api->get_article($pubmed_id);
        
        if (is_wp_error($article)) {
            wp_send_json_error(array('message' => $article->get_error_message()));
        }
        
        // İçerik işleme sınıfını başlat
        $content_processor = new Content_Processor();
        
        // İçeriği işle
        $processed_content = $content_processor->process_article($article);
        
        if (is_wp_error($processed_content)) {
            wp_send_json_error(array('message' => $processed_content->get_error_message()));
        }
        
        // SEO optimizasyon sınıfını başlat
        $seo_optimizer = new SEO_Optimizer();
        
        // İçeriği optimize et
        $optimized_content = $seo_optimizer->optimize_content($processed_content);
        
        if (is_wp_error($optimized_content)) {
            wp_send_json_error(array('message' => $optimized_content->get_error_message()));
        }
        
        // Ayarları al
        $settings = get_option('pubmed_health_importer_settings');
        
        // Yazı tipini belirle
        $post_type = 'pubmed_article';
        
        // Yazı durumunu belirle
        $post_status = ($settings['auto_publish'] === 'yes') ? 'publish' : 'draft';
        
        // Yazıyı oluştur
        $post_data = array(
            'post_title'    => $optimized_content['title'],
            'post_content'  => $optimized_content['content'],
            'post_excerpt'  => $optimized_content['excerpt'],
            'post_status'   => $post_status,
            'post_type'     => $post_type,
            'post_author'   => $settings['default_author'],
            'meta_input'    => array(
                'pubmed_id' => $pubmed_id,
                'pubmed_authors' => $article['authors'],
                'pubmed_journal' => $article['journal'],
                'pubmed_publication_date' => $article['publication_date'],
                'pubmed_abstract' => $article['abstract'],
                'pubmed_mesh_terms' => $article['mesh_terms'],
                'pubmed_faq' => $optimized_content['faq'],
                'pubmed_schema_markup' => $optimized_content['schema_markup'],
                'pubmed_seo_title' => $optimized_content['seo_title'],
                'pubmed_seo_description' => $optimized_content['seo_description'],
                'pubmed_featured_snippet' => $optimized_content['featured_snippet'],
            ),
        );
        
        // Yazıyı ekle
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }
        
        // Kategorileri ekle
        if (!empty($optimized_content['categories'])) {
            wp_set_object_terms($post_id, $optimized_content['categories'], 'pubmed_category');
        }
        
        // Etiketleri ekle
        if (!empty($optimized_content['tags'])) {
            wp_set_object_terms($post_id, $optimized_content['tags'], 'pubmed_tag');
        }
        
        // Veritabanına kaydet
        global $wpdb;
        $table_name = $wpdb->prefix . 'pubmed_articles';
        
        $wpdb->insert(
            $table_name,
            array(
                'pubmed_id' => $pubmed_id,
                'post_id' => $post_id,
                'title' => $article['title'],
                'authors' => json_encode($article['authors']),
                'abstract' => $article['abstract'],
                'publication_date' => $article['publication_date'],
                'journal' => $article['journal'],
                'mesh_terms' => json_encode($article['mesh_terms']),
            )
        );
        
        wp_send_json_success(array(
            'message' => __('Makale başarıyla içe aktarıldı.', 'pubmed-health-importer'),
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'view_url' => get_permalink($post_id),
        ));
    }

    /**
     * PubMed içerik zenginleştirme AJAX işleyicisi
     */
    public function ajax_pubmed_enhance_content() {
        // Nonce kontrolü
        check_ajax_referer('pubmed_health_importer_nonce', 'nonce');
        
        // Yönetici yetkisi kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'pubmed-health-importer')));
        }
        
        // Parametreleri al
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if ($post_id === 0) {
            wp_send_json_error(array('message' => __('Geçersiz yazı ID.', 'pubmed-health-importer')));
        }
        
        // Yazıyı al
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(array('message' => __('Yazı bulunamadı.', 'pubmed-health-importer')));
        }
        
        // Ayarları al
        $settings = get_option('pubmed_health_importer_settings');
        
        // Gemini AI entegrasyonu etkin mi kontrol et
        if ($settings['content_enhancement'] !== 'yes' || empty($settings['gemini_api_key'])) {
            wp_send_json_error(array('message' => __('İçerik zenginleştirme özelliği etkin değil veya Gemini API anahtarı eksik.', 'pubmed-health-importer')));
        }
        
        // Gemini AI sınıfını başlat
        $gemini_ai = new Gemini_AI($settings['gemini_api_key']);
        
        // İçeriği zenginleştir
        $enhanced_content = $gemini_ai->enhance_content($post->post_content, $post->post_title);
        
        if (is_wp_error($enhanced_content)) {
            wp_send_json_error(array('message' => $enhanced_content->get_error_message()));
        }
        
        // Yazıyı güncelle
        $post_data = array(
            'ID' => $post_id,
            'post_content' => $enhanced_content['content'],
        );
        
        $updated = wp_update_post($post_data);
        
        if (is_wp_error($updated)) {
            wp_send_json_error(array('message' => $updated->get_error_message()));
        }
        
        // Meta verileri güncelle
        if (!empty($enhanced_content['faq'])) {
            update_post_meta($post_id, 'pubmed_faq', $enhanced_content['faq']);
        }
        
        if (!empty($enhanced_content['schema_markup'])) {
            update_post_meta($post_id, 'pubmed_schema_markup', $enhanced_content['schema_markup']);
        }
        
        if (!empty($enhanced_content['featured_snippet'])) {
            update_post_meta($post_id, 'pubmed_featured_snippet', $enhanced_content['featured_snippet']);
        }
        
        wp_send_json_success(array(
            'message' => __('İçerik başarıyla zenginleştirildi.', 'pubmed-health-importer'),
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'view_url' => get_permalink($post_id),
        ));
    }
}

// Eklentiyi başlat
function pubmed_health_importer() {
    return PubMed_Health_Importer::get_instance();
}

// Eklentiyi çalıştır
pubmed_health_importer();
