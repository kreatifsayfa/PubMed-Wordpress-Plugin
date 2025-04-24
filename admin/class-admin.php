<?php
/**
 * Admin sınıfı
 * 
 * WordPress admin panelinde eklenti ayarlarını ve arayüzünü yönetir
 * 
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/admin
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin sınıfı
 */
class PubMed_Health_Importer_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        // Admin menüsü
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin scriptleri ve stilleri
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Ayarlar sayfası
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX işleyicileri
        add_action('wp_ajax_pubmed_search', array($this, 'ajax_pubmed_search'));
        add_action('wp_ajax_pubmed_import', array($this, 'ajax_pubmed_import'));
        add_action('wp_ajax_pubmed_enhance_content', array($this, 'ajax_pubmed_enhance_content'));
        add_action('wp_ajax_pubmed_save_scheduled_search', array($this, 'ajax_save_scheduled_search'));
        add_action('wp_ajax_pubmed_delete_scheduled_search', array($this, 'ajax_delete_scheduled_search'));
        add_action('wp_ajax_pubmed_run_scheduled_search', array($this, 'ajax_run_scheduled_search'));
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
            'saving' => __('Kaydediliyor...', 'pubmed-health-importer'),
            'deleting' => __('Siliniyor...', 'pubmed-health-importer'),
            'running' => __('Çalıştırılıyor...', 'pubmed-health-importer'),
            'success' => __('Başarılı!', 'pubmed-health-importer'),
            'error' => __('Hata!', 'pubmed-health-importer'),
            'confirm_delete' => __('Bu zamanlanmış aramayı silmek istediğinizden emin misiniz?', 'pubmed-health-importer'),
        ));
    }

    /**
     * Ayarları kaydet
     */
    public function register_settings() {
        register_setting('pubmed_health_importer_settings', 'pubmed_health_importer_settings');
        
        // Genel ayarlar bölümü
        add_settings_section(
            'pubmed_health_importer_general_section',
            __('Genel Ayarlar', 'pubmed-health-importer'),
            array($this, 'general_section_callback'),
            'pubmed_health_importer_settings'
        );
        
        // API ayarları bölümü
        add_settings_section(
            'pubmed_health_importer_api_section',
            __('API Ayarları', 'pubmed-health-importer'),
            array($this, 'api_section_callback'),
            'pubmed_health_importer_settings'
        );
        
        // İçerik ayarları bölümü
        add_settings_section(
            'pubmed_health_importer_content_section',
            __('İçerik Ayarları', 'pubmed-health-importer'),
            array($this, 'content_section_callback'),
            'pubmed_health_importer_settings'
        );
        
        // SEO ayarları bölümü
        add_settings_section(
            'pubmed_health_importer_seo_section',
            __('SEO Ayarları', 'pubmed-health-importer'),
            array($this, 'seo_section_callback'),
            'pubmed_health_importer_settings'
        );
        
        // Genel ayarlar alanları
        add_settings_field(
            'cache_duration',
            __('Önbellek Süresi (saniye)', 'pubmed-health-importer'),
            array($this, 'cache_duration_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_general_section'
        );
        
        add_settings_field(
            'default_author',
            __('Varsayılan Yazar', 'pubmed-health-importer'),
            array($this, 'default_author_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_general_section'
        );
        
        add_settings_field(
            'default_category',
            __('Varsayılan Kategori', 'pubmed-health-importer'),
            array($this, 'default_category_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_general_section'
        );
        
        // API ayarları alanları
        add_settings_field(
            'api_key',
            __('PubMed API Anahtarı', 'pubmed-health-importer'),
            array($this, 'api_key_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_api_section'
        );
        
        add_settings_field(
            'tool',
            __('Tool Parametresi', 'pubmed-health-importer'),
            array($this, 'tool_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_api_section'
        );
        
        add_settings_field(
            'email',
            __('Email Parametresi', 'pubmed-health-importer'),
            array($this, 'email_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_api_section'
        );
        
        add_settings_field(
            'gemini_api_key',
            __('Gemini AI API Anahtarı', 'pubmed-health-importer'),
            array($this, 'gemini_api_key_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_api_section'
        );
        
        // İçerik ayarları alanları
        add_settings_field(
            'auto_import',
            __('Otomatik İçe Aktarma', 'pubmed-health-importer'),
            array($this, 'auto_import_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_content_section'
        );
        
        add_settings_field(
            'auto_publish',
            __('Otomatik Yayınlama', 'pubmed-health-importer'),
            array($this, 'auto_publish_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_content_section'
        );
        
        add_settings_field(
            'content_enhancement',
            __('İçerik Zenginleştirme', 'pubmed-health-importer'),
            array($this, 'content_enhancement_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_content_section'
        );
        
        add_settings_field(
            'mesh_terms',
            __('MeSH Terimleri', 'pubmed-health-importer'),
            array($this, 'mesh_terms_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_content_section'
        );
        
        // SEO ayarları alanları
        add_settings_field(
            'seo_optimization',
            __('SEO Optimizasyonu', 'pubmed-health-importer'),
            array($this, 'seo_optimization_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_seo_section'
        );
        
        add_settings_field(
            'featured_snippet_optimization',
            __('Featured Snippet Optimizasyonu', 'pubmed-health-importer'),
            array($this, 'featured_snippet_optimization_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_seo_section'
        );
        
        add_settings_field(
            'faq_generation',
            __('FAQ Oluşturma', 'pubmed-health-importer'),
            array($this, 'faq_generation_callback'),
            'pubmed_health_importer_settings',
            'pubmed_health_importer_seo_section'
        );
    }

    /**
     * Genel ayarlar bölümü callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Eklentinin genel ayarlarını yapılandırın.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * API ayarları bölümü callback
     */
    public function api_section_callback() {
        echo '<p>' . __('PubMed API ve Gemini AI API ayarlarını yapılandırın.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * İçerik ayarları bölümü callback
     */
    public function content_section_callback() {
        echo '<p>' . __('İçerik işleme ve içe aktarma ayarlarını yapılandırın.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * SEO ayarları bölümü callback
     */
    public function seo_section_callback() {
        echo '<p>' . __('SEO optimizasyonu ayarlarını yapılandırın.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * Önbellek süresi callback
     */
    public function cache_duration_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $cache_duration = isset($options['cache_duration']) ? $options['cache_duration'] : 86400;
        
        echo '<input type="number" id="cache_duration" name="pubmed_health_importer_settings[cache_duration]" value="' . esc_attr($cache_duration) . '" min="0" step="1" />';
        echo '<p class="description">' . __('API yanıtlarının önbellekte saklanma süresi (saniye cinsinden). Varsayılan: 86400 (24 saat)', 'pubmed-health-importer') . '</p>';
    }

    /**
     * Varsayılan yazar callback
     */
    public function default_author_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $default_author = isset($options['default_author']) ? $options['default_author'] : 1;
        
        $users = get_users(array(
            'role__in' => array('administrator', 'editor', 'author'),
            'orderby' => 'display_name',
        ));
        
        echo '<select id="default_author" name="pubmed_health_importer_settings[default_author]">';
        
        foreach ($users as $user) {
            echo '<option value="' . esc_attr($user->ID) . '" ' . selected($default_author, $user->ID, false) . '>' . esc_html($user->display_name) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . __('İçe aktarılan makalelerin varsayılan yazarı.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * Varsayılan kategori callback
     */
    public function default_category_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $default_category = isset($options['default_category']) ? $options['default_category'] : '';
        
        $categories = get_terms(array(
            'taxonomy' => 'category',
            'hide_empty' => false,
        ));
        
        echo '<select id="default_category" name="pubmed_health_importer_settings[default_category]">';
        echo '<option value="">' . __('Otomatik Belirle', 'pubmed-health-importer') . '</option>';
        
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '" ' . selected($default_category, $category->term_id, false) . '>' . esc_html($category->name) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . __('İçe aktarılan makalelerin varsayılan kategorisi. "Otomatik Belirle" seçeneği, MeSH terimlerine göre kategori belirler.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * API anahtarı callback
     */
    public function api_key_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        
        echo '<input type="text" id="api_key" name="pubmed_health_importer_settings[api_key]" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('PubMed E-utilities API anahtarı. <a href="https://www.ncbi.nlm.nih.gov/account/" target="_blank">NCBI hesabınızdan</a> alabilirsiniz.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * Tool parametresi callback
     */
    public function tool_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $tool = isset($options['tool']) ? $options['tool'] : 'pubmed_health_importer';
        
        echo '<input type="text" id="tool" name="pubmed_health_importer_settings[tool]" value="' . esc_attr($tool) . '" class="regular-text" />';
        echo '<p class="description">' . __('PubMed API isteklerinde kullanılacak tool parametresi. Boşluk içermemelidir.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * Email parametresi callback
     */
    public function email_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $email = isset($options['email']) ? $options['email'] : '';
        
        echo '<input type="email" id="email" name="pubmed_health_importer_settings[email]" value="' . esc_attr($email) . '" class="regular-text" />';
        echo '<p class="description">' . __('PubMed API isteklerinde kullanılacak email parametresi. Geçerli bir e-posta adresi olmalıdır.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * Gemini API anahtarı callback
     */
    public function gemini_api_key_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $gemini_api_key = isset($options['gemini_api_key']) ? $options['gemini_api_key'] : '';
        
        echo '<input type="text" id="gemini_api_key" name="pubmed_health_importer_settings[gemini_api_key]" value="' . esc_attr($gemini_api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Gemini AI API anahtarı. İçerik zenginleştirme ve çeviri için kullanılır.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * Otomatik içe aktarma callback
     */
    public function auto_import_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $auto_import = isset($options['auto_import']) ? $options['auto_import'] : 'no';
        
        echo '<select id="auto_import" name="pubmed_health_importer_settings[auto_import]">';
        echo '<option value="yes" ' . selected($auto_import, 'yes', false) . '>' . __('Evet', 'pubmed-health-importer') . '</option>';
        echo '<option value="no" ' . selected($auto_import, 'no', false) . '>' . __('Hayır', 'pubmed-health-importer') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Zamanlanmış aramalarda bulunan makaleleri otomatik olarak içe aktar.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * Otomatik yayınlama callback
     */
    public function auto_publish_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $auto_publish = isset($options['auto_publish']) ? $options['auto_publish'] : 'no';
        
        echo '<select id="auto_publish" name="pubmed_health_importer_settings[auto_publish]">';
        echo '<option value="yes" ' . selected($auto_publish, 'yes', false) . '>' . __('Evet', 'pubmed-health-importer') . '</option>';
        echo '<option value="no" ' . selected($auto_publish, 'no', false) . '>' . __('Hayır', 'pubmed-health-importer') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('İçe aktarılan makaleleri otomatik olarak yayınla. "Hayır" seçilirse, makaleler taslak olarak kaydedilir.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * İçerik zenginleştirme callback
     */
    public function content_enhancement_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $content_enhancement = isset($options['content_enhancement']) ? $options['content_enhancement'] : 'yes';
        
        echo '<select id="content_enhancement" name="pubmed_health_importer_settings[content_enhancement]">';
        echo '<option value="yes" ' . selected($content_enhancement, 'yes', false) . '>' . __('Evet', 'pubmed-health-importer') . '</option>';
        echo '<option value="no" ' . selected($content_enhancement, 'no', false) . '>' . __('Hayır', 'pubmed-health-importer') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Gemini AI ile içerik zenginleştirme. Bu özellik için Gemini AI API anahtarı gereklidir.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * MeSH terimleri callback
     */
    public function mesh_terms_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $mesh_terms = isset($options['mesh_terms']) ? $options['mesh_terms'] : array(
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
        );
        
        echo '<textarea id="mesh_terms" name="pubmed_health_importer_settings[mesh_terms]" rows="10" cols="50" class="large-text">' . esc_textarea(implode("\n", $mesh_terms)) . '</textarea>';
        echo '<p class="description">' . __('Aramalarda kullanılacak MeSH terimleri. Her satıra bir terim yazın.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * SEO optimizasyonu callback
     */
    public function seo_optimization_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $seo_optimization = isset($options['seo_optimization']) ? $options['seo_optimization'] : 'yes';
        
        echo '<select id="seo_optimization" name="pubmed_health_importer_settings[seo_optimization]">';
        echo '<option value="yes" ' . selected($seo_optimization, 'yes', false) . '>' . __('Evet', 'pubmed-health-importer') . '</option>';
        echo '<option value="no" ' . selected($seo_optimization, 'no', false) . '>' . __('Hayır', 'pubmed-health-importer') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('İçeriği SEO için optimize et ve şema markup ekle.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * Featured snippet optimizasyonu callback
     */
    public function featured_snippet_optimization_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $featured_snippet_optimization = isset($options['featured_snippet_optimization']) ? $options['featured_snippet_optimization'] : 'yes';
        
        echo '<select id="featured_snippet_optimization" name="pubmed_health_importer_settings[featured_snippet_optimization]">';
        echo '<option value="yes" ' . selected($featured_snippet_optimization, 'yes', false) . '>' . __('Evet', 'pubmed-health-importer') . '</option>';
        echo '<option value="no" ' . selected($featured_snippet_optimization, 'no', false) . '>' . __('Hayır', 'pubmed-health-importer') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('İçeriği Google featured snippet (sıfır snippet) için optimize et.', 'pubmed-health-importer') . '</p>';
    }

    /**
     * FAQ oluşturma callback
     */
    public function faq_generation_callback() {
        $options = get_option('pubmed_health_importer_settings');
        $faq_generation = isset($options['faq_generation']) ? $options['faq_generation'] : 'yes';
        
        echo '<select id="faq_generation" name="pubmed_health_importer_settings[faq_generation]">';
        echo '<option value="yes" ' . selected($faq_generation, 'yes', false) . '>' . __('Evet', 'pubmed-health-importer') . '</option>';
        echo '<option value="no" ' . selected($faq_generation, 'no', false) . '>' . __('Hayır', 'pubmed-health-importer') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('İçerik için otomatik FAQ bölümü oluştur.', 'pubmed-health-importer') . '</p>';
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
        
        // İçerik zenginleştirme etkinse ve Gemini API anahtarı varsa
        if ($settings['content_enhancement'] === 'yes' && !empty($settings['gemini_api_key'])) {
            // Gemini AI sınıfını başlat
            $gemini_ai = new Gemini_AI($settings['gemini_api_key']);
            
            // İçeriği zenginleştir
            $enhanced_content = $gemini_ai->enhance_content($optimized_content['content'], $optimized_content['title']);
            
            if (!is_wp_error($enhanced_content)) {
                // Yazıyı güncelle
                $post_data = array(
                    'ID' => $post_id,
                    'post_content' => $enhanced_content['content'],
                );
                
                wp_update_post($post_data);
                
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
            }
        }
        
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

    /**
     * Zamanlanmış arama kaydetme AJAX işleyicisi
     */
    public function ajax_save_scheduled_search() {
        // Nonce kontrolü
        check_ajax_referer('pubmed_health_importer_nonce', 'nonce');
        
        // Yönetici yetkisi kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'pubmed-health-importer')));
        }
        
        // Parametreleri al
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $count = isset($_POST['count']) ? intval($_POST['count']) : 10;
        $schedule = isset($_POST['schedule']) ? sanitize_text_field($_POST['schedule']) : 'daily';
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Arama adı boş olamaz.', 'pubmed-health-importer')));
        }
        
        if (empty($query)) {
            wp_send_json_error(array('message' => __('Arama sorgusu boş olamaz.', 'pubmed-health-importer')));
        }
        
        // Arama parametrelerini oluştur
        $search_params = array(
            'query' => $query,
            'count' => $count,
        );
        
        // Veritabanına kaydet
        global $wpdb;
        $table_name = $wpdb->prefix . 'pubmed_searches';
        
        if ($id > 0) {
            // Güncelle
            $wpdb->update(
                $table_name,
                array(
                    'name' => $name,
                    'description' => $description,
                    'search_params' => json_encode($search_params),
                    'schedule' => $schedule,
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => $id)
            );
            
            $message = __('Zamanlanmış arama başarıyla güncellendi.', 'pubmed-health-importer');
        } else {
            // Ekle
            $wpdb->insert(
                $table_name,
                array(
                    'name' => $name,
                    'description' => $description,
                    'search_params' => json_encode($search_params),
                    'schedule' => $schedule,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                )
            );
            
            $id = $wpdb->insert_id;
            $message = __('Zamanlanmış arama başarıyla eklendi.', 'pubmed-health-importer');
        }
        
        // Zamanlanmış görevi ayarla
        $this->schedule_search_task($id, $schedule);
        
        wp_send_json_success(array(
            'message' => $message,
            'id' => $id,
        ));
    }

    /**
     * Zamanlanmış arama silme AJAX işleyicisi
     */
    public function ajax_delete_scheduled_search() {
        // Nonce kontrolü
        check_ajax_referer('pubmed_health_importer_nonce', 'nonce');
        
        // Yönetici yetkisi kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'pubmed-health-importer')));
        }
        
        // Parametreleri al
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id === 0) {
            wp_send_json_error(array('message' => __('Geçersiz arama ID.', 'pubmed-health-importer')));
        }
        
        // Veritabanından sil
        global $wpdb;
        $table_name = $wpdb->prefix . 'pubmed_searches';
        
        $wpdb->delete(
            $table_name,
            array('id' => $id)
        );
        
        // Zamanlanmış görevi kaldır
        $this->unschedule_search_task($id);
        
        wp_send_json_success(array(
            'message' => __('Zamanlanmış arama başarıyla silindi.', 'pubmed-health-importer'),
        ));
    }

    /**
     * Zamanlanmış arama çalıştırma AJAX işleyicisi
     */
    public function ajax_run_scheduled_search() {
        // Nonce kontrolü
        check_ajax_referer('pubmed_health_importer_nonce', 'nonce');
        
        // Yönetici yetkisi kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'pubmed-health-importer')));
        }
        
        // Parametreleri al
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id === 0) {
            wp_send_json_error(array('message' => __('Geçersiz arama ID.', 'pubmed-health-importer')));
        }
        
        // Zamanlanmış aramayı al
        global $wpdb;
        $table_name = $wpdb->prefix . 'pubmed_searches';
        
        $search = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
        
        if (!$search) {
            wp_send_json_error(array('message' => __('Zamanlanmış arama bulunamadı.', 'pubmed-health-importer')));
        }
        
        // Arama parametrelerini al
        $search_params = json_decode($search->search_params, true);
        
        if (!$search_params) {
            wp_send_json_error(array('message' => __('Geçersiz arama parametreleri.', 'pubmed-health-importer')));
        }
        
        // PubMed API sınıfını başlat
        $pubmed_api = new PubMed_API();
        
        // Arama yap
        $results = $pubmed_api->scheduled_search($search_params);
        
        if (is_wp_error($results)) {
            wp_send_json_error(array('message' => $results->get_error_message()));
        }
        
        // Son çalışma zamanını güncelle
        $wpdb->update(
            $table_name,
            array(
                'last_run' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $id)
        );
        
        // Ayarları al
        $settings = get_option('pubmed_health_importer_settings');
        
        // Otomatik içe aktarma etkinse
        if ($settings['auto_import'] === 'yes' && !empty($results['articles'])) {
            $imported_count = 0;
            
            foreach ($results['articles'] as $article) {
                // Makale zaten içe aktarılmış mı kontrol et
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}pubmed_articles WHERE pubmed_id = %s",
                    $article['id']
                ));
                
                if ($existing) {
                    continue;
                }
                
                // Makale detaylarını al
                $article_data = $pubmed_api->get_article($article['id']);
                
                if (is_wp_error($article_data)) {
                    continue;
                }
                
                // İçerik işleme sınıfını başlat
                $content_processor = new Content_Processor();
                
                // İçeriği işle
                $processed_content = $content_processor->process_article($article_data);
                
                if (is_wp_error($processed_content)) {
                    continue;
                }
                
                // SEO optimizasyon sınıfını başlat
                $seo_optimizer = new SEO_Optimizer();
                
                // İçeriği optimize et
                $optimized_content = $seo_optimizer->optimize_content($processed_content);
                
                if (is_wp_error($optimized_content)) {
                    continue;
                }
                
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
                        'pubmed_id' => $article_data['id'],
                        'pubmed_authors' => $article_data['authors'],
                        'pubmed_journal' => $article_data['journal'],
                        'pubmed_publication_date' => $article_data['publication_date'],
                        'pubmed_abstract' => $article_data['abstract'],
                        'pubmed_mesh_terms' => $article_data['mesh_terms'],
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
                    continue;
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
                $wpdb->insert(
                    $wpdb->prefix . 'pubmed_articles',
                    array(
                        'pubmed_id' => $article_data['id'],
                        'post_id' => $post_id,
                        'title' => $article_data['title'],
                        'authors' => json_encode($article_data['authors']),
                        'abstract' => $article_data['abstract'],
                        'publication_date' => $article_data['publication_date'],
                        'journal' => $article_data['journal'],
                        'mesh_terms' => json_encode($article_data['mesh_terms']),
                    )
                );
                
                // İçerik zenginleştirme etkinse ve Gemini API anahtarı varsa
                if ($settings['content_enhancement'] === 'yes' && !empty($settings['gemini_api_key'])) {
                    // Gemini AI sınıfını başlat
                    $gemini_ai = new Gemini_AI($settings['gemini_api_key']);
                    
                    // İçeriği zenginleştir
                    $enhanced_content = $gemini_ai->enhance_content($optimized_content['content'], $optimized_content['title']);
                    
                    if (!is_wp_error($enhanced_content)) {
                        // Yazıyı güncelle
                        $post_data = array(
                            'ID' => $post_id,
                            'post_content' => $enhanced_content['content'],
                        );
                        
                        wp_update_post($post_data);
                        
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
                    }
                }
                
                $imported_count++;
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Zamanlanmış arama başarıyla çalıştırıldı. %d makale bulundu, %d makale içe aktarıldı.', 'pubmed-health-importer'), count($results['articles']), $imported_count),
                'results' => $results,
            ));
        } else {
            wp_send_json_success(array(
                'message' => sprintf(__('Zamanlanmış arama başarıyla çalıştırıldı. %d makale bulundu.', 'pubmed-health-importer'), count($results['articles'])),
                'results' => $results,
            ));
        }
    }

    /**
     * Zamanlanmış arama görevi ayarla
     *
     * @param int $id Arama ID
     * @param string $schedule Zamanlama (hourly, daily, weekly)
     */
    private function schedule_search_task($id, $schedule) {
        // Eski görevi kaldır
        $this->unschedule_search_task($id);
        
        // Yeni görevi ayarla
        $hook = 'pubmed_health_importer_scheduled_search_' . $id;
        
        if (!wp_next_scheduled($hook)) {
            wp_schedule_event(time(), $schedule, $hook);
        }
        
        // Görevi çalıştıracak kancayı ekle
        add_action($hook, array($this, 'run_scheduled_search'), 10, 1);
    }

    /**
     * Zamanlanmış arama görevini kaldır
     *
     * @param int $id Arama ID
     */
    private function unschedule_search_task($id) {
        $hook = 'pubmed_health_importer_scheduled_search_' . $id;
        $timestamp = wp_next_scheduled($hook);
        
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook);
        }
    }

    /**
     * Zamanlanmış aramayı çalıştır
     *
     * @param int $id Arama ID
     */
    public function run_scheduled_search($id) {
        // Zamanlanmış aramayı al
        global $wpdb;
        $table_name = $wpdb->prefix . 'pubmed_searches';
        
        $search = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
        
        if (!$search) {
            return;
        }
        
        // Arama parametrelerini al
        $search_params = json_decode($search->search_params, true);
        
        if (!$search_params) {
            return;
        }
        
        // PubMed API sınıfını başlat
        $pubmed_api = new PubMed_API();
        
        // Arama yap
        $results = $pubmed_api->scheduled_search($search_params);
        
        if (is_wp_error($results)) {
            return;
        }
        
        // Son çalışma zamanını güncelle
        $wpdb->update(
            $table_name,
            array(
                'last_run' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $id)
        );
        
        // Ayarları al
        $settings = get_option('pubmed_health_importer_settings');
        
        // Otomatik içe aktarma etkinse
        if ($settings['auto_import'] === 'yes' && !empty($results['articles'])) {
            foreach ($results['articles'] as $article) {
                // Makale zaten içe aktarılmış mı kontrol et
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}pubmed_articles WHERE pubmed_id = %s",
                    $article['id']
                ));
                
                if ($existing) {
                    continue;
                }
                
                // Makale detaylarını al
                $article_data = $pubmed_api->get_article($article['id']);
                
                if (is_wp_error($article_data)) {
                    continue;
                }
                
                // İçerik işleme sınıfını başlat
                $content_processor = new Content_Processor();
                
                // İçeriği işle
                $processed_content = $content_processor->process_article($article_data);
                
                if (is_wp_error($processed_content)) {
                    continue;
                }
                
                // SEO optimizasyon sınıfını başlat
                $seo_optimizer = new SEO_Optimizer();
                
                // İçeriği optimize et
                $optimized_content = $seo_optimizer->optimize_content($processed_content);
                
                if (is_wp_error($optimized_content)) {
                    continue;
                }
                
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
                        'pubmed_id' => $article_data['id'],
                        'pubmed_authors' => $article_data['authors'],
                        'pubmed_journal' => $article_data['journal'],
                        'pubmed_publication_date' => $article_data['publication_date'],
                        'pubmed_abstract' => $article_data['abstract'],
                        'pubmed_mesh_terms' => $article_data['mesh_terms'],
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
                    continue;
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
                $wpdb->insert(
                    $wpdb->prefix . 'pubmed_articles',
                    array(
                        'pubmed_id' => $article_data['id'],
                        'post_id' => $post_id,
                        'title' => $article_data['title'],
                        'authors' => json_encode($article_data['authors']),
                        'abstract' => $article_data['abstract'],
                        'publication_date' => $article_data['publication_date'],
                        'journal' => $article_data['journal'],
                        'mesh_terms' => json_encode($article_data['mesh_terms']),
                    )
                );
                
                // İçerik zenginleştirme etkinse ve Gemini API anahtarı varsa
                if ($settings['content_enhancement'] === 'yes' && !empty($settings['gemini_api_key'])) {
                    // Gemini AI sınıfını başlat
                    $gemini_ai = new Gemini_AI($settings['gemini_api_key']);
                    
                    // İçeriği zenginleştir
                    $enhanced_content = $gemini_ai->enhance_content($optimized_content['content'], $optimized_content['title']);
                    
                    if (!is_wp_error($enhanced_content)) {
                        // Yazıyı güncelle
                        $post_data = array(
                            'ID' => $post_id,
                            'post_content' => $enhanced_content['content'],
                        );
                        
                        wp_update_post($post_data);
                        
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
                    }
                }
            }
        }
    }
}
