<?php
/**
 * SEO Optimizasyon sınıfı
 * 
 * İçeriği SEO için optimize eder, şema markup ekler ve featured snippet için yapılandırır
 * 
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/includes
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SEO Optimizasyon sınıfı
 */
class SEO_Optimizer {

    /**
     * SEO optimizasyonu etkin mi
     *
     * @var bool
     */
    private $seo_enabled = true;

    /**
     * Featured snippet optimizasyonu etkin mi
     *
     * @var bool
     */
    private $featured_snippet_enabled = true;

    /**
     * FAQ oluşturma etkin mi
     *
     * @var bool
     */
    private $faq_enabled = true;

    /**
     * Constructor
     */
    public function __construct() {
        // Ayarları al
        $settings = get_option('pubmed_health_importer_settings');
        
        if ($settings) {
            $this->seo_enabled = isset($settings['seo_optimization']) ? ($settings['seo_optimization'] === 'yes') : true;
            $this->featured_snippet_enabled = isset($settings['featured_snippet_optimization']) ? ($settings['featured_snippet_optimization'] === 'yes') : true;
            $this->faq_enabled = isset($settings['faq_generation']) ? ($settings['faq_generation'] === 'yes') : true;
        }
    }

    /**
     * İçeriği optimize eder
     *
     * @param array $processed_content İşlenmiş içerik
     * @return array|WP_Error Optimize edilmiş içerik veya hata
     */
    public function optimize_content($processed_content) {
        if (empty($processed_content) || !isset($processed_content['title'])) {
            return new WP_Error('invalid_content', __('Geçersiz içerik verileri.', 'pubmed-health-importer'));
        }

        // Temel içerik bilgilerini al
        $title = isset($processed_content['title']) ? $processed_content['title'] : '';
        $content = isset($processed_content['content']) ? $processed_content['content'] : '';
        $excerpt = isset($processed_content['excerpt']) ? $processed_content['excerpt'] : '';
        $authors = isset($processed_content['authors']) ? $processed_content['authors'] : array();
        $publication_date = isset($processed_content['publication_date']) ? $processed_content['publication_date'] : '';
        $journal = isset($processed_content['journal']) ? $processed_content['journal'] : '';
        $mesh_terms = isset($processed_content['mesh_terms']) ? $processed_content['mesh_terms'] : array();
        $categories = isset($processed_content['categories']) ? $processed_content['categories'] : array();
        $tags = isset($processed_content['tags']) ? $processed_content['tags'] : array();

        // SEO başlığı ve açıklaması oluştur
        $seo_title = $this->generate_seo_title($title, $categories);
        $seo_description = $this->generate_seo_description($excerpt, $categories);

        // İçeriği SEO için optimize et
        $optimized_content = $this->optimize_content_structure($content, $title);

        // FAQ bölümü oluştur
        $faq = $this->generate_faq($title, $content, $excerpt, $mesh_terms);

        // Şema markup oluştur
        $schema_markup = $this->generate_schema_markup($title, $optimized_content, $excerpt, $authors, $publication_date, $journal, $faq);

        // Featured snippet için içerik oluştur
        $featured_snippet = $this->generate_featured_snippet($title, $excerpt, $mesh_terms);

        // Optimize edilmiş içeriği döndür
        return array(
            'title' => $title,
            'content' => $optimized_content,
            'excerpt' => $excerpt,
            'authors' => $authors,
            'publication_date' => $publication_date,
            'journal' => $journal,
            'mesh_terms' => $mesh_terms,
            'categories' => $categories,
            'tags' => $tags,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'faq' => $faq,
            'schema_markup' => $schema_markup,
            'featured_snippet' => $featured_snippet,
        );
    }

    /**
     * SEO başlığı oluşturur
     *
     * @param string $title Başlık
     * @param array $categories Kategoriler
     * @return string SEO başlığı
     */
    private function generate_seo_title($title, $categories) {
        if (!$this->seo_enabled) {
            return $title;
        }
        
        // Başlık zaten yeterince uzunsa değiştirme
        if (strlen($title) > 40) {
            return $title;
        }
        
        // Ana kategoriyi al
        $main_category = !empty($categories) ? reset($categories) : '';
        
        // Kategori varsa başlığa ekle
        if (!empty($main_category)) {
            return $title . ' - ' . $main_category . ' Rehberi';
        }
        
        return $title;
    }

    /**
     * SEO açıklaması oluşturur
     *
     * @param string $excerpt Özet
     * @param array $categories Kategoriler
     * @return string SEO açıklaması
     */
    private function generate_seo_description($excerpt, $categories) {
        if (!$this->seo_enabled || empty($excerpt)) {
            return $excerpt;
        }
        
        // Ana kategoriyi al
        $main_category = !empty($categories) ? reset($categories) : '';
        
        // Özet yeterince uzunsa değiştirme
        if (strlen($excerpt) > 150) {
            return $excerpt;
        }
        
        // Kategori varsa özete ekle
        if (!empty($main_category)) {
            return $excerpt . ' Bu rehber, ' . $main_category . ' hakkında önemli bilgiler içermektedir.';
        }
        
        return $excerpt;
    }

    /**
     * İçerik yapısını SEO için optimize eder
     *
     * @param string $content İçerik
     * @param string $title Başlık
     * @return string Optimize edilmiş içerik
     */
    private function optimize_content_structure($content, $title) {
        if (!$this->seo_enabled) {
            return $content;
        }
        
        // İçeriğin başına H1 başlık ekle
        $h1_title = '<h1>' . esc_html($title) . '</h1>';
        
        // İçeriğin başında zaten H1 varsa ekleme
        if (strpos($content, '<h1>') === false) {
            $content = $h1_title . $content;
        }
        
        // İçerik bölümlerini belirle ve H2, H3 başlıkları ekle
        $content = $this->add_heading_structure($content);
        
        // İç bağlantılar ekle
        $content = $this->add_internal_links($content);
        
        // Alt etiketleri ekle
        $content = $this->add_image_alt_tags($content, $title);
        
        return $content;
    }

    /**
     * İçeriğe başlık yapısı ekler
     *
     * @param string $content İçerik
     * @return string Başlık yapısı eklenmiş içerik
     */
    private function add_heading_structure($content) {
        // Zaten yeterli başlık yapısı varsa değiştirme
        if (substr_count($content, '<h2>') >= 2 && substr_count($content, '<h3>') >= 2) {
            return $content;
        }
        
        // Özet bölümünü H2 yap
        $content = str_replace('<div class="pubmed-article-abstract">', '<div class="pubmed-article-abstract"><h2>Özet</h2>', $content);
        
        // Detaylı bilgi bölümünü H2 yap
        $content = str_replace('<div class="pubmed-article-content">', '<div class="pubmed-article-content"><h2>Detaylı Bilgi</h2>', $content);
        
        // Anahtar kelimeler bölümünü H3 yap
        $content = str_replace('<div class="pubmed-article-keywords">', '<div class="pubmed-article-keywords"><h3>Anahtar Kelimeler</h3>', $content);
        
        // Kaynak bölümünü H3 yap
        $content = str_replace('<div class="pubmed-article-source-info">', '<div class="pubmed-article-source-info"><h3>Kaynak</h3>', $content);
        
        return $content;
    }

    /**
     * İçeriğe iç bağlantılar ekler
     *
     * @param string $content İçerik
     * @return string İç bağlantılar eklenmi�� içerik
     */
    private function add_internal_links($content) {
        global $wpdb;

        // İçerik boşsa veya çok kısaysa işlem yapma
        if (empty($content) || strlen($content) < 200) {
            return $content;
        }

        // Mevcut postlardan link eklenmiş kelimeleri takip et (aynı kelimeye birden fazla link eklememek için)
        $linked_keywords = array();

        // Mevcut post ID'yi al (import sırasında olabilir, bu yüzden 0 kabul et)
        $current_post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        if ($current_post_id === 0) {
            $exclude_clause = '';
        } else {
            $exclude_clause = 'AND p.ID != ' . intval($current_post_id);
        }

        // Mevcut pubmed_article postlarını al (son 50 makale)
        $articles = $wpdb->get_results(
            "SELECT p.ID, p.post_title
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'pubmed_article'
            AND p.post_status = 'publish'
            $exclude_clause
            ORDER BY p.post_date DESC
            LIMIT 50",
            ARRAY_A
        );

        if (empty($articles)) {
            return $content;
        }

        // Her makale için anahtar kelimeleri çıkar ve link ekle
        foreach ($articles as $article) {
            $post_id = $article['ID'];
            $post_title = $article['post_title'];
            $post_url = get_permalink($post_id);

            // Başlıktan anahtar kelimeler çıkar (en az 3 kelimelik ifadeler)
            $keywords = $this->extract_keywords_from_title($post_title);

            foreach ($keywords as $keyword) {
                // Bu kelime zaten link edildiyse devam et
                if (in_array(strtolower($keyword), $linked_keywords)) {
                    continue;
                }

                // Keyword'ü içerikte ara (büyük/küçük harf duyarsız, kelime sınırlarına dikkat et)
                $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/iu';
                $match_count = preg_match($pattern, $content);

                // Eğer keyword içerikte varsa, ilk bulunduğu yere link ekle
                if ($match_count) {
                    $replacement = '<a href="' . esc_url($post_url) . '" title="' . esc_attr($post_title) . '">$1</a>';
                    $content = preg_replace($pattern, $replacement, $content, 1); // Sadece ilk eşleşmeyi değiştir

                    // Bu kelimeyi link edilmiş olarak işaretle
                    $linked_keywords[] = strtolower($keyword);

                    // Maksimum 5 iç link ekle (çok fazla link SEO için zararlı)
                    if (count($linked_keywords) >= 5) {
                        break 2; // İki döngüden de çık
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Başlıktan anahtar kelimeleri çıkarır
     *
     * @param string $title Başlık
     * @return array Anahtar kelimeler
     */
    private function extract_keywords_from_title($title) {
        $keywords = array();

        // Başlığı temizle (özel karakterleri kaldır)
        $clean_title = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $title);

        // Tam başlığı anahtar kelime olarak ekle
        $keywords[] = $clean_title;

        // Başlığı kelimelere böl
        $words = preg_split('/\s+/u', $clean_title);

        // 3 kelimelik kombinasyonlar oluştur
        for ($i = 0; $i < count($words) - 2; $i++) {
            $three_word_phrase = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
            if (mb_strlen($three_word_phrase, 'UTF-8') > 10) { // En az 10 karakter
                $keywords[] = $three_word_phrase;
            }
        }

        // 2 kelimelik kombinasyonlar oluştur
        for ($i = 0; $i < count($words) - 1; $i++) {
            $two_word_phrase = $words[$i] . ' ' . $words[$i + 1];
            if (mb_strlen($two_word_phrase, 'UTF-8') > 8) { // En az 8 karakter
                $keywords[] = $two_word_phrase;
            }
        }

        // Tekrar edenleri kaldır ve uzunluğa göre sırala (uzun olanlar önce)
        $keywords = array_unique($keywords);
        usort($keywords, function($a, $b) {
            return mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8');
        });

        return $keywords;
    }

    /**
     * Görsellere alt etiketleri ekler
     *
     * @param string $content İçerik
     * @param string $title Başlık
     * @return string Alt etiketleri eklenmiş içerik
     */
    private function add_image_alt_tags($content, $title) {
        // Alt etiketi olmayan görselleri bul
        $content = preg_replace_callback('/<img((?!alt=)[^>])*>/i', function($matches) use ($title) {
            // Görsel zaten alt etiketi içeriyorsa değiştirme
            if (strpos($matches[0], 'alt=') !== false) {
                return $matches[0];
            }
            
            // Alt etiketi ekle
            return str_replace('<img', '<img alt="' . esc_attr($title) . '"', $matches[0]);
        }, $content);
        
        return $content;
    }

    /**
     * FAQ bölümü oluşturur
     *
     * @param string $title Başlık
     * @param string $content İçerik
     * @param string $excerpt Özet
     * @param array $mesh_terms MeSH terimleri
     * @return array FAQ verileri
     */
    private function generate_faq($title, $content, $excerpt, $mesh_terms) {
        if (!$this->faq_enabled) {
            return array();
        }
        
        $faq = array();
        
        // Başlıktan soru oluştur
        $faq[] = array(
            'question' => $this->title_to_question($title),
            'answer' => $excerpt,
        );
        
        // Temel sorular ekle
        $faq[] = array(
            'question' => 'Bu makale hangi konuları içeriyor?',
            'answer' => 'Bu makale, ' . implode(', ', $mesh_terms) . ' konularını içermektedir.',
        );
        
        // Kategoriye göre özel sorular ekle
        $category_questions = $this->get_category_questions($mesh_terms);
        if (!empty($category_questions)) {
            $faq = array_merge($faq, $category_questions);
        }
        
        // İçerikten sorular çıkar
        $content_questions = $this->extract_questions_from_content($content);
        if (!empty($content_questions)) {
            $faq = array_merge($faq, $content_questions);
        }
        
        return $faq;
    }

    /**
     * Başlığı soruya dönüştürür
     *
     * @param string $title Başlık
     * @return string Soru
     */
    private function title_to_question($title) {
        // Başlık zaten soru mu kontrol et
        if (substr($title, -1) === '?') {
            return $title;
        }
        
        // Başlığı soruya dönüştür
        if (strpos(strtolower($title), 'nedir') !== false) {
            return $title . '?';
        }
        
        if (strpos(strtolower($title), 'nasıl') !== false) {
            return $title . '?';
        }
        
        // Genel soru formatı
        return $title . ' nedir?';
    }

    /**
     * Kategoriye göre özel sorular alır
     *
     * @param array $mesh_terms MeSH terimleri
     * @return array Sorular
     */
    private function get_category_questions($mesh_terms) {
        $questions = array();
        
        // Hamilelik ile ilgili sorular
        if (in_array('Pregnancy', $mesh_terms)) {
            $questions[] = array(
                'question' => 'Hamilelik döneminde nelere dikkat edilmelidir?',
                'answer' => 'Hamilelik döneminde dengeli beslenme, düzenli egzersiz, yeterli uyku ve stres yönetimi önemlidir. Ayrıca, düzenli doktor kontrollerine gitmek ve zararlı maddelerden uzak durmak gerekir.',
            );
            
            $questions[] = array(
                'question' => 'Hamilelikte hangi besinler tüketilmelidir?',
                'answer' => 'Hamilelikte protein, kalsiyum, demir, folik asit ve diğer vitaminler açısından zengin besinler tüketilmelidir. Bunlar arasında süt ürünleri, et, balık, yumurta, kurubaklagiller, yeşil yapraklı sebzeler, meyveler ve tam tahıllı ürünler yer alır.',
            );
        }
        
        // Bebek sağlığı ile ilgili sorular
        if (in_array('Infant Health', $mesh_terms) || in_array('Child Health', $mesh_terms)) {
            $questions[] = array(
                'question' => 'Bebeklerde sağlıklı gelişim nasıl desteklenir?',
                'answer' => 'Bebeklerde sağlıklı gelişim için anne sütü ile beslenme, düzenli uyku düzeni, sevgi dolu bir ortam, düzenli doktor kontrolleri ve aşılar, uygun uyaranlar ve oyunlar önemlidir.',
            );
            
            $questions[] = array(
                'question' => 'Bebeklerde en sık görülen sağlık sorunları nelerdir?',
                'answer' => 'Bebeklerde en sık görülen sağlık sorunları arasında kolik, pişik, üst solunum yolu enfeksiyonları, orta kulak iltihabı, ishal, kabızlık ve ateş yer alır.',
            );
        }
        
        // Kadın sağlığı ile ilgili sorular
        if (in_array('Women\'s Health', $mesh_terms)) {
            $questions[] = array(
                'question' => 'Kadınlar için düzenli sağlık kontrolleri nelerdir?',
                'answer' => 'Kadınlar için düzenli sağlık kontrolleri arasında yıllık jinekolojik muayene, Pap smear testi, meme muayenesi, kemik yoğunluğu ölçümü, kan basıncı kontrolü, kolesterol testi ve genel sağlık taramaları yer alır.',
            );
            
            $questions[] = array(
                'question' => 'Kadınlarda en sık görülen sağlık sorunları nelerdir?',
                'answer' => 'Kadınlarda en sık görülen sağlık sorunları arasında meme kanseri, over kanseri, osteoporoz, kalp hastalıkları, depresyon, anemi, tiroid hastalıkları ve üriner sistem enfeksiyonları yer alır.',
            );
        }
        
        return $questions;
    }

    /**
     * İçerikten sorular çıkarır
     *
     * @param string $content İçerik
     * @return array Sorular
     */
    private function extract_questions_from_content($content) {
        $questions = array();

        // HTML etiketlerini temizleyip düz metin olarak işle
        // Önce H2 ve H3 başlıklarını çıkar
        preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/is', $content, $headings);
        $headings_list = isset($headings[1]) ? $headings[1] : array();

        // Başlıklardan soru türet
        foreach ($headings_list as $heading) {
            $heading_text = strip_tags($heading);
            $heading_text = trim($heading_text);

            if (mb_strlen($heading_text, 'UTF-8') < 5 || mb_strlen($heading_text, 'UTF-8') > 100) {
                continue;
            }

            // Zaten soru formatında mı
            if (substr(trim($heading_text), -1) === '?') {
                $question = $heading_text;
                $answer = $this->find_answer_for_question($content, $heading_text);
            } else {
                // Başlığı soruya dönüştür
                $question = $this->convert_to_question($heading_text);
                $answer = $this->find_answer_for_question($content, $heading_text);
            }

            // Cevap bulunduysa ekle
            if (!empty($answer) && mb_strlen($answer, 'UTF-8') > 20) {
                $questions[] = array(
                    'question' => $question,
                    'answer' => $answer,
                );
            }

            // Maksimum 5 soru çıkar
            if (count($questions) >= 5) {
                break;
            }
        }

        // Eğer başlıklardan yeterli soru bulunamadıysa, paragraflardan da çıkar
        if (count($questions) < 5) {
            preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $paragraphs);
            $paragraphs_list = isset($paragraphs[1]) ? $paragraphs[1] : array();

            foreach ($paragraphs_list as $paragraph) {
                $para_text = strip_tags($paragraph);
                $para_text = trim($para_text);

                // Soru ile başlayan cümleleri bul
                if (preg_match('/^[A-ZİĞÜŞÖÇ][^.;:!?]*\?/u', $para_text, $match)) {
                    $question = $match[0];

                    // Sorudan sonraki cümleyi cevap olarak al
                    $answer = trim(str_replace($question, '', $para_text));
                    $answer = preg_replace('/^[.:\s]+/u', '', $answer);

                    if (mb_strlen($answer, 'UTF-8') > 30) {
                        // Cevabı kısalt (maksimum 300 karakter)
                        $answer = $this->truncate_answer($answer, 300);

                        $questions[] = array(
                            'question' => $question,
                            'answer' => $answer,
                        );
                    }
                }

                if (count($questions) >= 8) {
                    break;
                }
            }
        }

        return $questions;
    }

    /**
     * Metni soruya dönüştürür
     *
     * @param string $text Metin
     * @return string Soru
     */
    private function convert_to_question($text) {
        $text_lower = mb_strtolower($text, 'UTF-8');

        // Zaten soru kelimesi içeriyor mu
        if (preg_match('/^(nedir|nasıl|neden|ne zaman|kim|hangi|kaç|nerede)/ui', $text)) {
            return $text . '?';
        }

        // "Nedir" ile biten kelimeler için
        if (preg_match('/\s(nedir|nelerdir)$/ui', $text)) {
            return $text . '?';
        }

        // Suffix ekleyerek soruya dönüştür
        $suffixes = array(
            'nedir?',
            'nasıl yapılır?',
            'nelerdir?',
            'neden önemlidir?'
        );

        // Uygun soneki seç
        foreach ($suffixes as $suffix) {
            if (mb_strlen($text . ' ' . $suffix, 'UTF-8') < 80) {
                return $text . ' ' . $suffix;
            }
        }

        return $text . ' nedir?';
    }

    /**
     * Soru için cevap bulur
     *
     * @param string $content İçerik
     * @param string $heading_text Başlık metni
     * @return string Cevap
     */
    private function find_answer_for_question($content, $heading_text) {
        // Başlıktan sonraki paragrafı bul
        $pattern = '/<h[23][^>]*>' . preg_quote($heading_text, '/') . '<\/h[23]>\s*<p[^>]*>(.*?)<\/p>/is';
        preg_match($pattern, $content, $match);

        if (isset($match[1])) {
            $answer = strip_tags($match[1]);
            $answer = trim($answer);

            // Cevabı uygun uzunlukta kırp
            return $this->truncate_answer($answer, 300);
        }

        return '';
    }

    /**
     * Cevabı belirli uzunlukta kırp
     *
     * @param string $answer Cevap
     * @param int $max_length Maksimum uzunluk
     * @return string Kırpılmış cevap
     */
    private function truncate_answer($answer, $max_length = 300) {
        if (mb_strlen($answer, 'UTF-8') <= $max_length) {
            return $answer;
        }

        $truncated = mb_substr($answer, 0, $max_length, 'UTF-8');

        // Son boşlukta kes
        $last_space = mb_strrpos($truncated, ' ', 0, 'UTF-8');
        if ($last_space !== false) {
            $truncated = mb_substr($truncated, 0, $last_space, 'UTF-8');
        }

        return $truncated . '...';
    }

    /**
     * Şema markup oluşturur (Google Featured Snippet için optimize)
     *
     * @param string $title Başlık
     * @param string $content İçerik
     * @param string $excerpt Özet
     * @param array $authors Yazarlar
     * @param string $publication_date Yayın tarihi
     * @param string $journal Dergi
     * @param array $faq FAQ verileri
     * @return string Şema markup JSON
     */
    private function generate_schema_markup($title, $content, $excerpt, $authors, $publication_date, $journal, $faq) {
        if (!$this->seo_enabled) {
            return '';
        }

        $current_date = current_time('Y-m-d');

        // Google Featured Snippet için @graph yapısı
        $graph = array();

        // WebPage şeması (Temel)
        $graph[] = array(
            '@type' => 'WebPage',
            '@id' => get_permalink() . '#webpage',
            'url' => get_permalink(),
            'name' => $title,
            'description' => $this->create_featured_snippet_description($excerpt),
            'inLanguage' => 'tr-TR',
            'isPartOf' => array(
                '@id' => get_home_url() . '#website'
            ),
        );

        // MedicalArticle şeması (Ana içerik)
        $article_data = array(
            '@type' => 'MedicalScholarlyArticle',
            '@id' => get_permalink() . '#article',
            'headline' => $title,
            'description' => $this->create_meta_description($excerpt, $title),
            'image' => $this->get_article_image_url(),
            'datePublished' => $publication_date ? $publication_date : $current_date,
            'dateModified' => $current_date,
            'author' => $this->create_author_schema($authors),
            'publisher' => array(
                '@type' => 'Organization',
                '@id' => get_home_url() . '#organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->get_site_logo_url(),
                ),
            ),
            'isPartOf' => array(
                '@id' => get_permalink() . '#webpage'
            ),
            'mainEntity' => array(
                '@type' => 'MedicalEntity',
                'name' => $title,
            ),
        );

        // MeSH terimlerini medical code olarak ekle
        $mesh_terms = $this->extract_mesh_terms_from_content($content);
        if (!empty($mesh_terms)) {
            $article_data['about'] = array();
            foreach ($mesh_terms as $term) {
                $article_data['about'][] = array(
                    '@type' => 'MedicalEntity',
                    'name' => $term,
                );
            }
        }

        $graph[] = $article_data;

        // Dergi bilgisi
        if (!empty($journal)) {
            $graph[] = array(
                '@type' => 'Periodical',
                '@id' => get_permalink() . '#periodical',
                'name' => $journal,
            );
        }

        // FAQ şeması (Google Featured Snippet için kritik)
        if (!empty($faq)) {
            $faq_entities = array();
            foreach ($faq as $item) {
                if (!empty($item['question']) && !empty($item['answer'])) {
                    $faq_entities[] = array(
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text' => $this->format_answer_for_snippet($item['answer']),
                        ),
                    );
                }
            }

            if (!empty($faq_entities)) {
                $graph[] = array(
                    '@type' => 'FAQPage',
                    '@id' => get_permalink() . '#faq',
                    'mainEntity' => $faq_entities,
                );
            }
        }

        // Breadcrumb şeması
        $graph[] = $this->create_breadcrumb_schema();

        // Final JSON-LD
        $schema = array(
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        );

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Featured snippet için açıklama oluşturur (40-60 kelime)
     */
    private function create_featured_snippet_description($excerpt) {
        $words = explode(' ', strip_tags($excerpt));
        $snippet = array_slice($words, 0, 50);
        $text = implode(' ', $snippet);

        if (mb_strlen($text, 'UTF-8') > 300) {
            $text = mb_substr($text, 0, 297, 'UTF-8') . '...';
        }

        return $text;
    }

    /**
     * Meta description oluşturur (150-160 karakter)
     */
    private function create_meta_description($excerpt, $title) {
        $description = strip_tags($excerpt);

        // Başlıkla birleştir
        $combined = $title . ' - ' . $description;

        // 160 karakter ile sınırlandır
        if (mb_strlen($combined, 'UTF-8') > 160) {
            $combined = mb_substr($combined, 0, 157, 'UTF-8') . '...';
        }

        return $combined;
    }

    /**
     * Yazar şeması oluşturur
     */
    private function create_author_schema($authors) {
        if (empty($authors)) {
            return array(
                '@type' => 'Person',
                'name' => get_bloginfo('name'),
            );
        }

        $author_list = array();
        foreach ($authors as $author) {
            $author_list[] = array(
                '@type' => 'Person',
                'name' => $author,
            );
        }

        // İlk yazarı ana yazar olarak döndür
        return count($author_list) === 1 ? $author_list[0] : $author_list;
    }

    /**
     * Makale görsel URL'sini alır
     */
    private function get_article_image_url() {
        // Eğer post varsa featured image'ı kullan
        if (function_exists('get_the_ID') && has_post_thumbnail(get_the_ID())) {
            return get_the_post_thumbnail_url(get_the_ID(), 'large');
        }

        // Varsayılan logo
        return $this->get_site_logo_url();
    }

    /**
     * İçerikten MeSH terimlerini çıkarır
     */
    private function extract_mesh_terms_from_content($content) {
        preg_match_all('/<span[^>]*class="[^"]*mesh-term[^"]*"[^>]*>(.*?)<\/span>/is', $content, $matches);
        return isset($matches[1]) ? array_map('strip_tags', $matches[1]) : array();
    }

    /**
     * Cevabı snippet formatında hazırlar
     */
    private function format_answer_for_snippet($answer) {
        // HTML etiketlerini temizle
        $answer = strip_tags($answer);

        // Maksimum 100 kelime (Google snippet limiti)
        $words = explode(' ', $answer);
        if (count($words) > 100) {
            $answer = implode(' ', array_slice($words, 0, 100)) . '...';
        }

        return $answer;
    }

    /**
     * Breadcrumb şeması oluşturur
     */
    private function create_breadcrumb_schema() {
        $breadcrumbs = array(
            array(
                '@type' => 'ListItem',
                'position' => 1,
                'name' => __('Ana Sayfa', 'pubmed-health-importer'),
                'item' => get_home_url(),
            ),
            array(
                '@type' => 'ListItem',
                'position' => 2,
                'name' => __('Sağlık Rehberi', 'pubmed-health-importer'),
                'item' => get_post_type_archive_link('pubmed_article'),
            ),
        );

        return array(
            '@type' => 'BreadcrumbList',
            '@id' => get_permalink() . '#breadcrumb',
            'itemListElement' => $breadcrumbs,
        );
    }

    /**
     * Site logosu URL'sini alır
     *
     * @return string Logo URL
     */
    private function get_site_logo_url() {
        $custom_logo_id = get_theme_mod('custom_logo');
        
        if ($custom_logo_id) {
            $logo_image = wp_get_attachment_image_src($custom_logo_id, 'full');
            
            if ($logo_image) {
                return $logo_image[0];
            }
        }
        
        // Varsayılan logo
        return get_site_icon_url();
    }

    /**
     * Featured snippet için içerik oluşturur
     *
     * @param string $title Başlık
     * @param string $excerpt Özet
     * @param array $mesh_terms MeSH terimleri
     * @return array Featured snippet verileri
     */
    private function generate_featured_snippet($title, $excerpt, $mesh_terms) {
        if (!$this->featured_snippet_enabled) {
            return array();
        }
        
        $featured_snippet = array();
        
        // Tanım snippet'i
        $featured_snippet['definition'] = array(
            'title' => $title,
            'content' => $this->format_for_featured_snippet($excerpt, 50),
        );
        
        // Liste snippet'i
        $list_items = $this->generate_list_items($mesh_terms);
        
        if (!empty($list_items)) {
            $featured_snippet['list'] = array(
                'title' => 'Bu makale aşağıdaki konuları içermektedir:',
                'items' => $list_items,
            );
        }
        
        // Tablo snippet'i
        $table_data = $this->generate_table_data($mesh_terms);
        
        if (!empty($table_data)) {
            $featured_snippet['table'] = $table_data;
        }
        
        return $featured_snippet;
    }

    /**
     * Featured snippet için metni formatlar
     *
     * @param string $text Metin
     * @param int $word_count Kelime sayısı
     * @return string Formatlanmış metin
     */
    private function format_for_featured_snippet($text, $word_count = 50) {
        // HTML etiketlerini kaldır
        $text = strip_tags($text);
        
        // Kelime sayısını sınırla
        $words = explode(' ', $text);
        
        if (count($words) > $word_count) {
            $words = array_slice($words, 0, $word_count);
            $text = implode(' ', $words) . '...';
        }
        
        return $text;
    }

    /**
     * Liste öğeleri oluşturur
     *
     * @param array $mesh_terms MeSH terimleri
     * @return array Liste öğeleri
     */
    private function generate_list_items($mesh_terms) {
        if (empty($mesh_terms)) {
            return array();
        }
        
        // En fazla 5 öğe
        return array_slice($mesh_terms, 0, 5);
    }

    /**
     * Tablo verileri oluşturur
     *
     * @param array $mesh_terms MeSH terimleri
     * @return array Tablo verileri
     */
    private function generate_table_data($mesh_terms) {
        if (empty($mesh_terms)) {
            return array();
        }
        
        $table = array(
            'headers' => array('Konu', 'Açıklama'),
            'rows' => array(),
        );
        
        // MeSH terimlerine göre tablo satırları oluştur
        $term_descriptions = $this->get_term_descriptions();
        
        foreach ($mesh_terms as $term) {
            if (isset($term_descriptions[$term])) {
                $table['rows'][] = array($term, $term_descriptions[$term]);
            }
        }
        
        // En az 3 satır yoksa boş döndür
        if (count($table['rows']) < 3) {
            return array();
        }
        
        return $table;
    }

    /**
     * MeSH terimleri için açıklamalar alır
     *
     * @return array Terim açıklamaları
     */
    private function get_term_descriptions() {
        return array(
            'Women\'s Health' => 'Kadın sağlığı, kadınların fiziksel, zihinsel ve sosyal refahını içeren sağlık konularını kapsar.',
            'Pregnancy' => 'Hamilelik, döllenme ile doğum arasındaki süreçtir ve yaklaşık 40 hafta sürer.',
            'Pregnancy Complications' => 'Hamilelik komplikasyonları, hamilelik sırasında ortaya çıkan ve anne veya bebeğin sağlığını etkileyebilen sorunlardır.',
            'Reproductive Health' => 'Üreme sağlığı, üreme sistemi, işlevleri ve süreçleri ile ilgili sağlık konularını kapsar.',
            'Maternal Health' => 'Anne sağlığı, hamilelik, doğum ve doğum sonrası dönemde kadının sağlığını kapsar.',
            'Female Genital Diseases' => 'Kadın genital hastalıkları, kadın üreme sistemini etkileyen hastalıkları kapsar.',
            'Menstruation' => 'Menstrüasyon, kadınlarda aylık olarak gerçekleşen ve rahim iç zarının dökülmesini içeren fizyolojik bir süreçtir.',
            'Menopause' => 'Menopoz, kadınlarda üreme döneminin sona ermesi ve adet döngüsünün durmasıdır.',
            'Infant Health' => 'Bebek sağlığı, doğumdan 1 yaşına kadar olan bebeklerin sağlığını kapsar.',
            'Child Health' => 'Çocuk sağlığı, 1-18 yaş arası çocukların sağlığını kapsar.',
            'Pediatrics' => 'Pediatri, çocuk sağlığı ve hastalıkları ile ilgilenen tıp dalıdır.',
            'Infant Care' => 'Bebek bakımı, bebeklerin beslenme, uyku, hijyen ve genel bakım ihtiyaçlarını karşılamayı içerir.',
            'Child Development' => 'Çocuk gelişimi, çocukların fiziksel, bilişsel, duygusal ve sosyal gelişimini kapsar.',
            'Infant Nutrition' => 'Bebek beslenmesi, bebeklerin büyüme ve gelişme için ihtiyaç duydukları besinleri kapsar.',
            'Infant, Newborn, Diseases' => 'Yenidoğan hastalıkları, doğumdan sonraki ilk 28 gün içinde ortaya çıkan hastalıkları kapsar.',
        );
    }
}
