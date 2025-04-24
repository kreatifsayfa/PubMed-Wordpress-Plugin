<?php
/**
 * Gemini AI entegrasyon sınıfı
 * 
 * İçerik zenginleştirme ve çeviri için Gemini AI API'sini kullanır
 * 
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/includes
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gemini AI entegrasyon sınıfı
 */
class Gemini_AI {

    /**
     * API anahtarı
     *
     * @var string
     */
    private $api_key = '';

    /**
     * API temel URL'si
     *
     * @var string
     */
    private $base_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    /**
     * Constructor
     *
     * @param string $api_key API anahtarı
     */
    public function __construct($api_key = '') {
        $this->api_key = $api_key;
        
        if (empty($this->api_key)) {
            // Ayarlardan API anahtarını al
            $settings = get_option('pubmed_health_importer_settings');
            
            if ($settings && isset($settings['gemini_api_key'])) {
                $this->api_key = $settings['gemini_api_key'];
            }
        }
    }

    /**
     * İçeriği zenginleştirir
     *
     * @param string $content İçerik
     * @param string $title Başlık
     * @return array|WP_Error Zenginleştirilmiş içerik veya hata
     */
    public function enhance_content($content, $title) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('Gemini AI API anahtarı eksik.', 'pubmed-health-importer'));
        }
        
        // HTML etiketlerini kaldır
        $plain_content = strip_tags($content);
        
        // İçeriği zenginleştir
        $enhanced_content = $this->generate_enhanced_content($plain_content, $title);
        
        if (is_wp_error($enhanced_content)) {
            return $enhanced_content;
        }
        
        // FAQ oluştur
        $faq = $this->generate_faq_content($plain_content, $title);
        
        if (is_wp_error($faq)) {
            $faq = array();
        }
        
        // Featured snippet için içerik oluştur
        $featured_snippet = $this->generate_featured_snippet_content($plain_content, $title);
        
        if (is_wp_error($featured_snippet)) {
            $featured_snippet = array();
        }
        
        // Şema markup oluştur
        $schema_markup = $this->generate_schema_markup($enhanced_content, $title, $faq);
        
        if (is_wp_error($schema_markup)) {
            $schema_markup = '';
        }
        
        return array(
            'content' => $enhanced_content,
            'faq' => $faq,
            'featured_snippet' => $featured_snippet,
            'schema_markup' => $schema_markup,
        );
    }

    /**
     * İçeriği çevirir
     *
     * @param string $content İçerik
     * @param string $source_lang Kaynak dil
     * @param string $target_lang Hedef dil
     * @return string|WP_Error Çevrilmiş içerik veya hata
     */
    public function translate_content($content, $source_lang = 'en', $target_lang = 'tr') {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('Gemini AI API anahtarı eksik.', 'pubmed-health-importer'));
        }
        
        // Çeviri istemi oluştur
        $prompt = "Aşağıdaki metni $source_lang dilinden $target_lang diline çevir. Çevirinin doğal ve akıcı olmasına dikkat et. Tıbbi terimleri doğru çevirdiğinden emin ol. Sadece çeviriyi döndür, başka bir şey ekleme.\n\n$content";
        
        // API isteği gönder
        $response = $this->send_api_request($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }

    /**
     * Zenginleştirilmiş içerik oluşturur
     *
     * @param string $content İçerik
     * @param string $title Başlık
     * @return string|WP_Error Zenginleştirilmiş içerik veya hata
     */
    private function generate_enhanced_content($content, $title) {
        // İçerik zenginleştirme istemi oluştur
        $prompt = "Aşağıdaki tıbbi makaleyi, kadın ve bebek sağlığı konusunda bilgi arayan okuyucular için daha kapsamlı ve SEO dostu bir içeriğe dönüştür. İçeriği HTML formatında oluştur, başlıklar için h2 ve h3 etiketlerini kullan. İçeriği bölümlere ayır, her bölüm için açıklayıcı başlıklar kullan. Önemli bilgileri vurgula, listeler ve tablolar ekle. İçeriği en az 1500 kelime olacak şekilde genişlet. Google'da featured snippet (sıfır snippet) elde etmek için optimize et. Sadece içeriği döndür, başka bir şey ekleme.\n\nBaşlık: $title\n\nİçerik: $content";
        
        // API isteği gönder
        $response = $this->send_api_request($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // HTML içeriğini temizle ve düzenle
        $enhanced_content = $this->clean_html_content($response);
        
        return $enhanced_content;
    }

    /**
     * FAQ içeriği oluşturur
     *
     * @param string $content İçerik
     * @param string $title Başlık
     * @return array|WP_Error FAQ verileri veya hata
     */
    private function generate_faq_content($content, $title) {
        // FAQ oluşturma istemi
        $prompt = "Aşağıdaki tıbbi makale için 10 adet sık sorulan soru ve cevap oluştur. Sorular, Google'da featured snippet (sıfır snippet) elde etmek için optimize edilmiş olmalı. Her soru 1-2 cümle, her cevap 2-3 cümle olmalı. Cevaplar bilgilendirici ve doğru olmalı. Yanıtı JSON formatında döndür, her soru-cevap çifti için 'question' ve 'answer' alanları içermeli. Sadece JSON'ı döndür, başka bir şey ekleme.\n\nBaşlık: $title\n\nİçerik: $content";
        
        // API isteği gönder
        $response = $this->send_api_request($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // JSON'ı çözümle
        $faq_data = $this->parse_json_response($response);
        
        if (is_wp_error($faq_data)) {
            return $faq_data;
        }
        
        return $faq_data;
    }

    /**
     * Featured snippet için içerik oluşturur
     *
     * @param string $content İçerik
     * @param string $title Başlık
     * @return array|WP_Error Featured snippet verileri veya hata
     */
    private function generate_featured_snippet_content($content, $title) {
        // Featured snippet istemi
        $prompt = "Aşağıdaki tıbbi makale için Google'da featured snippet (sıfır snippet) elde etmek üzere optimize edilmiş içerik oluştur. Üç farklı snippet türü için içerik oluştur: 1) Tanım snippet'i (50-60 kelimelik özet), 2) Liste snippet'i (5-7 maddelik liste), 3) Tablo snippet'i (3-4 satırlık tablo). Yanıtı JSON formatında döndür, her snippet türü için ayrı bir alan içermeli. Sadece JSON'ı döndür, başka bir şey ekleme.\n\nBaşlık: $title\n\nİçerik: $content";
        
        // API isteği gönder
        $response = $this->send_api_request($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // JSON'ı çözümle
        $snippet_data = $this->parse_json_response($response);
        
        if (is_wp_error($snippet_data)) {
            return $snippet_data;
        }
        
        return $snippet_data;
    }

    /**
     * Şema markup oluşturur
     *
     * @param string $content İçerik
     * @param string $title Başlık
     * @param array $faq FAQ verileri
     * @return string|WP_Error Şema markup JSON veya hata
     */
    private function generate_schema_markup($content, $title, $faq) {
        // Şema markup istemi
        $prompt = "Aşağıdaki tıbbi makale için schema.org şema markup'ı oluştur. MedicalWebPage, Article ve FAQPage şemalarını içermeli. Yanıtı JSON-LD formatında döndür. Sadece JSON-LD'yi döndür, başka bir şey ekleme.\n\nBaşlık: $title\n\nİçerik: $content";
        
        // FAQ verileri varsa ekle
        if (!empty($faq)) {
            $prompt .= "\n\nFAQ: " . json_encode($faq);
        }
        
        // API isteği gönder
        $response = $this->send_api_request($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // JSON-LD'yi temizle
        $schema_markup = $this->clean_json_ld($response);
        
        return $schema_markup;
    }

    /**
     * API isteği gönderir
     *
     * @param string $prompt İstem
     * @return string|WP_Error API yanıtı veya hata
     */
    private function send_api_request($prompt) {
        // API URL'si
        $url = $this->base_url . '?key=' . $this->api_key;
        
        // İstek verileri
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.4,
                'topK' => 32,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
            )
        );
        
        // İstek gönder
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 60,
        ));
        
        // Hata kontrolü
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            return new WP_Error('api_error', sprintf(__('API hatası: %s', 'pubmed-health-importer'), $error_message));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('JSON çözümleme hatası', 'pubmed-health-importer'));
        }
        
        // Yanıt metnini al
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
        
        return new WP_Error('invalid_response', __('Geçersiz API yanıtı', 'pubmed-health-importer'));
    }

    /**
     * HTML içeriğini temizler ve düzenler
     *
     * @param string $html HTML içeriği
     * @return string Temizlenmiş HTML
     */
    private function clean_html_content($html) {
        // Markdown formatını HTML'e dönüştür
        if (strpos($html, '#') !== false || strpos($html, '```') !== false) {
            // Markdown içeriği tespit edildi, dönüştürme işlemi yapılabilir
            // Bu örnekte basit bir dönüşüm yapıyoruz, daha kapsamlı bir dönüşüm için Parsedown gibi kütüphaneler kullanılabilir
            
            // Kod bloklarını temizle
            $html = preg_replace('/```html\s*(.*?)\s*```/s', '$1', $html);
            $html = preg_replace('/```\s*(.*?)\s*```/s', '<pre><code>$1</code></pre>', $html);
            
            // Başlıkları dönüştür
            $html = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $html);
            $html = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $html);
            $html = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $html);
            $html = preg_replace('/^#### (.*?)$/m', '<h4>$1</h4>', $html);
            
            // Listeleri dönüştür
            $html = preg_replace('/^\* (.*?)$/m', '<li>$1</li>', $html);
            $html = preg_replace('/^\d+\. (.*?)$/m', '<li>$1</li>', $html);
            $html = preg_replace('/<li>(.*?)<\/li>\s*<li>/s', '<li>$1</li><li>', $html);
            $html = preg_replace('/(<li>.*?<\/li>)+/s', '<ul>$0</ul>', $html);
            
            // Paragrafları dönüştür
            $html = preg_replace('/^([^<].*?)$/m', '<p>$1</p>', $html);
            $html = preg_replace('/<p><\/p>/', '', $html);
        }
        
        // İzin verilen HTML etiketleri
        $allowed_html = array(
            'h1' => array(),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'h6' => array(),
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'table' => array(),
            'thead' => array(),
            'tbody' => array(),
            'tr' => array(),
            'th' => array(),
            'td' => array(),
            'blockquote' => array(),
            'pre' => array(),
            'code' => array(),
            'div' => array('class' => array()),
            'span' => array('class' => array()),
        );
        
        // HTML'i temizle
        $clean_html = wp_kses($html, $allowed_html);
        
        // Boş paragrafları kaldır
        $clean_html = preg_replace('/<p>\s*<\/p>/', '', $clean_html);
        
        return $clean_html;
    }

    /**
     * JSON yanıtını çözümler
     *
     * @param string $response JSON yanıtı
     * @return array|WP_Error Çözümlenmiş veri veya hata
     */
    private function parse_json_response($response) {
        // JSON bloğunu çıkar
        preg_match('/```json\s*(.*?)\s*```/s', $response, $matches);
        
        if (!empty($matches[1])) {
            $json = $matches[1];
        } else {
            // JSON bloğu yoksa, tüm yanıtı JSON olarak kabul et
            $json = $response;
        }
        
        // JSON'ı çözümle
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('JSON çözümleme hatası', 'pubmed-health-importer'));
        }
        
        return $data;
    }

    /**
     * JSON-LD'yi temizler
     *
     * @param string $json_ld JSON-LD içeriği
     * @return string Temizlenmiş JSON-LD
     */
    private function clean_json_ld($json_ld) {
        // JSON-LD bloğunu çıkar
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $json_ld, $matches);
        
        if (!empty($matches[1])) {
            return $matches[1];
        }
        
        // JSON bloğunu çıkar
        preg_match('/```json\s*(.*?)\s*```/s', $json_ld, $matches);
        
        if (!empty($matches[1])) {
            return $matches[1];
        }
        
        // Blok yoksa, tüm yanıtı JSON-LD olarak kabul et
        return $json_ld;
    }
}
