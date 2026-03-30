<?php
/**
 * Görsel Arama ve Ekleme Sınıfı
 *
 * Unsplash API kullanarak konuya uygun görseller bulur ve WordPress'e ekler
 *
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/includes
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image_Fetcher sınıfı
 */
class Image_Fetcher {

    /**
     * Unsplash API anahtarı
     *
     * @var string
     */
    private $api_key;

    /**
     * API base URL
     *
     * @var string
     */
    private $api_url = 'https://api.unsplash.com';

    /**
     * Constructor
     *
     * @param string $api_key Unsplash API anahtarı
     */
    public function __construct($api_key = '') {
        $this->api_key = $api_key;

        // Eğer API anahtarı verilmediyse ayarlardan al
        if (empty($this->api_key)) {
            $settings = get_option('pubmed_health_importer_settings');
            $this->api_key = isset($settings['unsplash_api_key']) ? $settings['unsplash_api_key'] : '';
        }
    }

    /**
     * Konuya uygun görsel bulur ve WordPress medya kütüphanesine ekler
     *
     * @param string $title Makale başlığı
     * @param array $mesh_terms MeSH terimleri
     * @param int $post_id WordPress post ID
     * @return int|false Eklenen görsel ID veya false
     */
    public function fetch_and_attach_image($title, $mesh_terms, $post_id) {
        // Unsplash API anahtarı yoksa çık
        if (empty($this->api_key)) {
            return false;
        }

        // Anahtar kelimeler oluştur
        $keywords = $this->generate_search_keywords($title, $mesh_terms);

        // Görseli ara
        $image_url = $this->search_unsplash($keywords);

        if (empty($image_url)) {
            return false;
        }

        // Görseli WordPress'e yükle
        $attachment_id = $this->upload_to_wordpress($image_url, $title, $post_id);

        if ($attachment_id) {
            // Öne çıkan görsel olarak ayarla
            set_post_thumbnail($post_id, $attachment_id);
            return $attachment_id;
        }

        return false;
    }

    /**
     * Başlık ve MeSH terimlerinden arama kelimeleri oluşturur
     *
     * @param string $title Başlık
     * @param array $mesh_terms MeSH terimleri
     * @return string Arama kelimesi
     */
    private function generate_search_keywords($title, $mesh_terms) {
        $keywords = array();

        // İngilizce health terimleri ekle (Unsplash İngilizce)
        $health_keywords = array(
            'health', 'medical', 'healthcare', 'wellness', 'care'
        );

        // MeSH terimlerini İngilizceye çevir (basit mapping)
        $mesh_to_english = array(
            "Women's Health" => 'women health',
            'Pregnancy' => 'pregnancy',
            'Pregnancy Complications' => 'pregnancy care',
            'Reproductive Health' => 'reproductive health',
            'Maternal Health' => 'maternal care',
            'Female Genital Diseases' => 'women health',
            'Menstruation' => 'menstruation',
            'Menopause' => 'menopause',
            'Infant Health' => 'baby health',
            'Child Health' => 'child health',
            'Pediatrics' => 'pediatric care',
            'Infant Care' => 'baby care',
            'Child Development' => 'child development',
            'Infant Nutrition' => 'baby nutrition',
            'Infant, Newborn, Diseases' => 'newborn care',
        );

        // İlk MeSH terimini kullan
        if (!empty($mesh_terms) && isset($mesh_terms[0])) {
            $first_mesh = $mesh_terms[0];
            if (isset($mesh_to_english[$first_mesh])) {
                $keywords[] = $mesh_to_english[$first_mesh];
            }
        }

        // Eğer keyword bulunamadıysa varsayılan kullan
        if (empty($keywords)) {
            $keywords[] = 'health care medical';
        }

        return implode(' ', $keywords);
    }

    /**
     * Unsplash'ta görsel arar
     *
     * @param string $query Arama sorgusu
     * @return string|false Görsel URL veya false
     */
    private function search_unsplash($query) {
        $url = $this->api_url . '/search/photos';

        $params = array(
            'query' => $query,
            'per_page' => 1,
            'orientation' => 'landscape',
            'content_filter' => 'high',
        );

        $request_url = add_query_arg($params, $url);

        $response = wp_remote_get($request_url, array(
            'headers' => array(
                'Authorization' => 'Client-ID ' . $this->api_key,
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['results'][0]['urls']['regular'])) {
            return $data['results'][0]['urls']['regular'];
        }

        return false;
    }

    /**
     * Görseli WordPress medya kütüphanesine yükler
     *
     * @param string $image_url Görsel URL
     * @param string $title Görsel başlığı
     * @param int $post_id Post ID
     * @return int|false Attachment ID veya false
     */
    private function upload_to_wordpress($image_url, $title, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Geçici dosya indir
        $tmp_file = download_url($image_url);

        if (is_wp_error($tmp_file)) {
            return false;
        }

        // Dosya adı oluştur
        $filename = sanitize_file_name($title) . '.jpg';

        // Dosya array'i oluştur
        $file_array = array(
            'name' => $filename,
            'tmp_name' => $tmp_file,
        );

        // WordPress medya kütüphanesine yükle
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);

        // Geçici dosyayı sil
        if (file_exists($tmp_file)) {
            @unlink($tmp_file);
        }

        if (is_wp_error($attachment_id)) {
            return false;
        }

        // Alt text ve açıklama ekle
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $title);
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_excerpt' => $title, // Caption
            'post_content' => $title, // Description
        ));

        return $attachment_id;
    }

    /**
     * API anahtarının geçerli olup olmadığını test eder
     *
     * @return bool
     */
    public function test_api_key() {
        if (empty($this->api_key)) {
            return false;
        }

        $url = $this->api_url . '/photos/random';

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Client-ID ' . $this->api_key,
            ),
            'timeout' => 10,
        ));

        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}
