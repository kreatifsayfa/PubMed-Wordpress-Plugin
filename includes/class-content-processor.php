<?php
/**
 * İçerik İşleme sınıfı
 * 
 * PubMed API'den alınan verileri WordPress içeriğine dönüştürür
 * 
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/includes
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * İçerik İşleme sınıfı
 */
class Content_Processor {

    /**
     * Constructor
     */
    public function __construct() {
        // Ayarları al
        $settings = get_option('pubmed_health_importer_settings');
    }

    /**
     * PubMed makalesini işler
     *
     * @param array $article PubMed makale verileri
     * @param bool $use_ai AI ile içerik oluştur (varsayılan: true)
     * @return array|WP_Error İşlenmiş içerik veya hata
     */
    public function process_article($article, $use_ai = true) {
        if (empty($article) || !isset($article['id'])) {
            return new WP_Error('invalid_article', __('Geçersiz makale verileri.', 'pubmed-health-importer'));
        }

        // Temel makale bilgilerini al
        $id = $article['id'];
        $title = isset($article['title']) ? $article['title'] : '';
        $authors = isset($article['authors']) ? $article['authors'] : array();
        $abstract = isset($article['abstract']) ? $article['abstract'] : '';
        $journal = isset($article['journal']) ? $article['journal'] : '';
        $publication_date = isset($article['publication_date']) ? $article['publication_date'] : '';
        $mesh_terms = isset($article['mesh_terms']) ? $article['mesh_terms'] : array();

        // Ayarları al
        $settings = get_option('pubmed_health_importer_settings');

        // Başlığı ve içeriği hazırla
        $final_title = $title;
        $final_content = '';
        $final_excerpt = '';
        $faq = array();
        $schema_markup = '';

        // AI ile içerik oluştur
        if ($use_ai && !empty($settings['gemini_api_key']) && $settings['content_enhancement'] === 'yes') {
            require_once PUBMED_HEALTH_IMPORTER_PATH . 'includes/class-gemini-ai.php';
            $gemini_ai = new Gemini_AI($settings['gemini_api_key']);

            // Blog yazısı oluştur
            $blog_post = $gemini_ai->generate_blog_post($article, true);

            if (!is_wp_error($blog_post)) {
                $final_title = $blog_post['title'];
                $final_content = $blog_post['content'];
                $final_excerpt = $blog_post['excerpt'];
                $faq = isset($blog_post['faq']) ? $blog_post['faq'] : array();
                $schema_markup = isset($blog_post['schema_markup']) ? $blog_post['schema_markup'] : '';
            }
        }

        // Eğer AI içerik oluşturmadıysa manuel içerik oluştur
        if (empty($final_content)) {
            // Başlığı temizle
            $final_title = $this->clean_title($title);

            // Yazarları formatlı metne dönüştür
            $authors_text = $this->format_authors($authors);

            // Özeti paragraflar halinde formatlı metne dönüştür
            $abstract_html = $this->format_abstract($abstract);

            // İçerik oluştur (placeholder olmadan)
            $final_content = $this->generate_manual_content($final_title, $authors_text, $abstract_html, $journal, $publication_date, $mesh_terms);

            // Özet oluştur
            $final_excerpt = $this->generate_excerpt($abstract);
        }

        // Kategorileri ve etiketleri belirle
        $categories = $this->determine_categories($mesh_terms);
        $tags = $this->determine_tags($mesh_terms);

        // İşlenmiş içeriği döndür
        return array(
            'title' => $final_title,
            'content' => $final_content,
            'excerpt' => $final_excerpt,
            'authors' => $authors,
            'publication_date' => $publication_date,
            'journal' => $journal,
            'mesh_terms' => $mesh_terms,
            'categories' => $categories,
            'tags' => $tags,
            'faq' => $faq,
            'schema_markup' => $schema_markup,
        );
    }

    /**
     * Başlığı temizler
     *
     * @param string $title Başlık
     * @return string Temizlenmiş başlık
     */
    private function clean_title($title) {
        // HTML etiketlerini kaldır
        $title = strip_tags($title);
        
        // Gereksiz boşlukları kaldır
        $title = trim($title);
        
        // Noktalama işaretlerini düzelt
        $title = str_replace(array('  ', '   '), ' ', $title);
        
        return $title;
    }

    /**
     * Yazarları formatlı metne dönüştürür
     *
     * @param array $authors Yazarlar
     * @return string Formatlı yazarlar metni
     */
    private function format_authors($authors) {
        if (empty($authors)) {
            return '';
        }
        
        $count = count($authors);
        
        if ($count === 1) {
            return $authors[0];
        }
        
        if ($count === 2) {
            return $authors[0] . ' ve ' . $authors[1];
        }
        
        $last_author = array_pop($authors);
        return implode(', ', $authors) . ' ve ' . $last_author;
    }

    /**
     * Özeti paragraflar halinde formatlı metne dönüştürür
     *
     * @param string $abstract Özet
     * @return string Formatlı özet HTML
     */
    private function format_abstract($abstract) {
        if (empty($abstract)) {
            return '';
        }
        
        // Paragrafları böl
        $paragraphs = explode("\n\n", $abstract);
        
        // HTML paragrafları oluştur
        $html = '';
        foreach ($paragraphs as $paragraph) {
            if (!empty(trim($paragraph))) {
                $html .= '<p>' . esc_html($paragraph) . '</p>';
            }
        }
        
        return $html;
    }

    /**
     * MeSH terimlerine göre kategorileri belirler
     *
     * @param array $mesh_terms MeSH terimleri
     * @return array Kategoriler
     */
    private function determine_categories($mesh_terms) {
        if (empty($mesh_terms)) {
            return array('Genel Sağlık');
        }
        
        $category_mapping = array(
            // Kadın Sağlığı
            'Women\'s Health' => 'Kadın Sağlığı',
            'Pregnancy' => 'Hamilelik',
            'Pregnancy Complications' => 'Hamilelik Komplikasyonları',
            'Reproductive Health' => 'Üreme Sağlığı',
            'Maternal Health' => 'Anne Sağlığı',
            'Female Genital Diseases' => 'Kadın Genital Hastalıkları',
            'Menstruation' => 'Menstrüasyon',
            'Menopause' => 'Menopoz',
            
            // Bebek Sağlığı
            'Infant Health' => 'Bebek Sağlığı',
            'Child Health' => 'Çocuk Sağlığı',
            'Pediatrics' => 'Pediatri',
            'Infant Care' => 'Bebek Bakımı',
            'Child Development' => 'Çocuk Gelişimi',
            'Infant Nutrition' => 'Bebek Beslenmesi',
            'Infant, Newborn, Diseases' => 'Yenidoğan Hastalıkları',
        );
        
        $categories = array();
        
        foreach ($mesh_terms as $term) {
            if (isset($category_mapping[$term])) {
                $categories[] = $category_mapping[$term];
            }
        }
        
        // En az bir kategori olmalı
        if (empty($categories)) {
            $categories[] = 'Genel Sağlık';
        }
        
        return array_unique($categories);
    }

    /**
     * MeSH terimlerine göre etiketleri belirler
     *
     * @param array $mesh_terms MeSH terimleri
     * @return array Etiketler
     */
    private function determine_tags($mesh_terms) {
        if (empty($mesh_terms)) {
            return array();
        }
        
        $tags = array();
        
        foreach ($mesh_terms as $term) {
            // MeSH terimlerini doğrudan etiket olarak kullan
            $tags[] = $term;
            
            // Alt terimleri de ekle
            $sub_terms = $this->get_sub_terms($term);
            if (!empty($sub_terms)) {
                $tags = array_merge($tags, $sub_terms);
            }
        }
        
        // Etiket sayısını sınırla (en fazla 10)
        $tags = array_slice(array_unique($tags), 0, 10);
        
        return $tags;
    }

    /**
     * MeSH terimi için alt terimleri alır
     *
     * @param string $term MeSH terimi
     * @return array Alt terimler
     */
    private function get_sub_terms($term) {
        // Bu fonksiyon daha sonra genişletilebilir
        // Şimdilik boş dizi döndürüyoruz
        return array();
    }

    /**
     * Manuel içerik oluşturur (AI olmadan)
     *
     * @param string $title Başlık
     * @param string $authors_text Yazarlar metni
     * @param string $abstract_html Özet HTML
     * @param string $journal Dergi
     * @param string $publication_date Yayın tarihi
     * @param array $mesh_terms MeSH terimleri
     * @return string İçerik HTML
     */
    private function generate_manual_content($title, $authors_text, $abstract_html, $journal, $publication_date, $mesh_terms) {
        $content = '';

        // Giriş bölümü
        $content .= '<div class="pubmed-article-intro" style="background: #f9f9f9; padding: 20px; border-left: 4px solid #0073aa; margin-bottom: 30px; border-radius: 4px;">';

        // Yazarlar
        if (!empty($authors_text)) {
            $content .= '<p class="pubmed-article-authors" style="margin: 5px 0;"><strong>' . __('Yazarlar:', 'pubmed-health-importer') . '</strong> ' . esc_html($authors_text) . '</p>';
        }

        // Dergi ve yayın tarihi
        if (!empty($journal) || !empty($publication_date)) {
            $content .= '<p class="pubmed-article-source" style="margin: 5px 0;">';

            if (!empty($journal)) {
                $content .= '<strong>' . __('Dergi:', 'pubmed-health-importer') . '</strong> ' . esc_html($journal);
            }

            if (!empty($publication_date)) {
                if (!empty($journal)) {
                    $content .= ' | ';
                }

                $content .= '<strong>' . __('Yayın Tarihi:', 'pubmed-health-importer') . '</strong> ' . esc_html($publication_date);
            }

            $content .= '</p>';
        }

        $content .= '</div>';

        // MeSH terimleri açıklamalarını ekle
        if (!empty($mesh_terms)) {
            $content .= '<div class="pubmed-article-topics" style="margin-bottom: 30px;">';
            $content .= '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">' . __('Bu Makale Hangi Konuları İçeriyor?', 'pubmed-health-importer') . '</h3>';
            $content .= '<ul style="list-style: none; padding: 0;">';

            foreach ($mesh_terms as $term) {
                $description = $this->get_mesh_description($term);
                $content .= '<li style="margin-bottom: 15px; padding: 15px; background: #f5f5f5; border-radius: 4px;">';
                $content .= '<strong style="color: #0073aa;">' . esc_html($term) . ':</strong> ' . esc_html($description);
                $content .= '</li>';
            }

            $content .= '</ul>';
            $content .= '</div>';
        }

        // Özet bölümü
        if (!empty($abstract_html)) {
            $content .= '<div class="pubmed-article-abstract" style="margin-bottom: 30px; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">';
            $content .= '<h2 style="color: #333; margin-top: 0;">' . __('Özet', 'pubmed-health-importer') . '</h2>';
            $content .= $abstract_html;
            $content .= '</div>';
        }

        // Bilgi notu
        $content .= '<div class="pubmed-article-note" style="margin-bottom: 30px; padding: 20px; background: #e8f4f8; border-left: 4px solid #0073aa; border-radius: 4px;">';
        $content .= '<p style="margin: 0; font-size: 15px; line-height: 1.6;">';
        $content .= '<strong>' . __('Not:', 'pubmed-health-importer') . '</strong> ';
        $content .= __('Bu makale PubMed\'den içe aktarılmıştır. Daha detaylı ve Türkçe içerik için ayarlardan "İçerik Zenginleştirme" özelliğini etkinleştirin ve Gemini AI API anahtarınızı girin.', 'pubmed-health-importer');
        $content .= '</p>';
        $content .= '</div>';

        // Anahtar kelimeler
        if (!empty($mesh_terms)) {
            $content .= '<div class="pubmed-article-keywords" style="margin-bottom: 30px; padding: 15px; background: #f9f9f9; border-radius: 4px;">';
            $content .= '<h3 style="margin-top: 0;">' . __('Anahtar Kelimeler', 'pubmed-health-importer') . '</h3>';
            $content .= '<div class="keywords-cloud" style="display: flex; flex-wrap: wrap; gap: 10px;">';

            foreach ($mesh_terms as $term) {
                $content .= '<span style="background: #0073aa; color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 14px;">' . esc_html($term) . '</span>';
            }

            $content .= '</div>';
            $content .= '</div>';
        }

        // Kaynak ve Disclaimer
        $content .= '<div class="pubmed-article-source-info" style="padding: 20px; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; margin-bottom: 30px;">';
        $content .= '<h3 style="margin-top: 0; color: #92400e;">' . __('Kaynak ve Bilimsel Dayanak', 'pubmed-health-importer') . '</h3>';
        $content .= '<p>' . sprintf(__('Bu içerik, %s tarihinde %s dergisinde yayınlanan bilimsel çalışma baz alınarak hazırlanmıştır. Orijinal makaleye <a href="%s" target="_blank" rel="noopener noreferrer">buradan</a> ulaşabilirsiniz.', 'pubmed-health-importer'),
            !empty($publication_date) ? esc_html($publication_date) : __('belirsiz bir tarihte'),
            !empty($journal) ? esc_html($journal) : __('bilimsel bir dergide'),
            esc_url('https://pubmed.ncbi.nlm.nih.gov/')
        ) . '</p>';
        $content .= '<p style="font-size: 14px; color: #666; margin-top: 10px;"><strong>' . __('Tıbbi Uyarı:', 'pubmed-health-importer') . '</strong> ' . __('Bu içerik bilgilendirme amaçlıdır ve profesyonel tıbbi tavsiye yerine geçmez. Herhangi bir sağlık sorunu için mutlaka bir sağlık uzmanına başvurun.', 'pubmed-health-importer') . '</p>';
        $content .= '</div>';

        return $content;
    }

    /**
     * MeSH terimi için açıklama döndürür
     *
     * @param string $term MeSH terimi
     * @return string Terim açıklaması
     */
    private function get_mesh_description($term) {
        $descriptions = array(
            "Women's Health" => 'Kadınların fiziksel, zihinsel ve sosyal sağlığını içeren kapsamlı sağlık konuları',
            "Pregnancy" => 'Döllenme ile doğum arasındaki yaklaşık 40 haftalık süreç ve bu dönemde yaşanan değişiklikler',
            "Pregnancy Complications" => 'Hamilelik sırasında ortaya çıkabilen ve anne ile bebek sağlığını etkileyebilen tıbbi durumlar',
            "Reproductive Health" => 'Üreme sistemi, işlevleri ve üreme süreciyle ilgili sağlık konuları',
            "Maternal Health" => 'Hamilelik, doğum ve doğum sonrası dönemde kadının sağlığı ve iyi oluşu',
            "Female Genital Diseases" => 'Kadın üreme sistemini etkileyen hastalıklar ve durumlar',
            "Menstruation" => 'Kadınlarda aylık olarak gerçekleşen adet döngüsü ve menstrüasyon süreci',
            "Menopause" => 'Kadınlarda üreme döneminin sona ermesi ve bu süreçte yaşanan değişiklikler',
            "Infant Health" => 'Doğumdan 1 yaşına kadar olan bebeklerin sağlığı ve gelişimi',
            "Child Health" => '1-18 yaş arası çocukların fiziksel ve zihinsel gelişimi',
            "Pediatrics" => 'Çocuk sağlığı ve hastalıkları ile ilgilenen tıp dalı',
            "Infant Care" => 'Bebeklerin beslenme, uyku, hijyen ve bakım ihtiyaçları',
            "Child Development" => 'Çocukların fiziksel, bilişsel, duygusal ve sosyal gelişim süreçleri',
            "Infant Nutrition" => 'Bebeklerin sağlıklı büyüme ve gelişme için beslenme gereksinimleri',
            "Infant, Newborn, Diseases" => 'Doğumdan sonraki ilk 28 gün içinde ortaya çıkan hastalıklar',
        );

        if (isset($descriptions[$term])) {
            return $descriptions[$term];
        }

        // Terimin kendisini açıklama olarak döndür
        return $term . ' ile ilgili tıbbi ve bilimsel konular';
    }

    /**
     * Özet oluşturur
     *
     * @param string $abstract Özet
     * @return string Özet metni
     */
    private function generate_excerpt($abstract) {
        if (empty($abstract)) {
            return '';
        }
        
        // HTML etiketlerini kaldır
        $excerpt = strip_tags($abstract);
        
        // İlk 250 karakteri al
        $excerpt = substr($excerpt, 0, 250);
        
        // Son kelimeyi tamamla
        $excerpt = substr($excerpt, 0, strrpos($excerpt, ' '));
        
        // Üç nokta ekle
        $excerpt .= '...';
        
        return $excerpt;
    }
}
