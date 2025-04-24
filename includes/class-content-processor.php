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
     * @return array|WP_Error İşlenmiş içerik veya hata
     */
    public function process_article($article) {
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

        // Başlığı temizle
        $title = $this->clean_title($title);

        // Yazarları formatlı metne dönüştür
        $authors_text = $this->format_authors($authors);

        // Özeti paragraflar halinde formatlı metne dönüştür
        $abstract_html = $this->format_abstract($abstract);

        // Kategorileri ve etiketleri belirle
        $categories = $this->determine_categories($mesh_terms);
        $tags = $this->determine_tags($mesh_terms);

        // İçerik oluştur
        $content = $this->generate_content($title, $authors_text, $abstract_html, $journal, $publication_date, $mesh_terms);

        // Özet oluştur
        $excerpt = $this->generate_excerpt($abstract);

        // İşlenmiş içeriği döndür
        return array(
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'authors' => $authors,
            'publication_date' => $publication_date,
            'journal' => $journal,
            'mesh_terms' => $mesh_terms,
            'categories' => $categories,
            'tags' => $tags,
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
     * İçerik oluşturur
     *
     * @param string $title Başlık
     * @param string $authors_text Yazarlar metni
     * @param string $abstract_html Özet HTML
     * @param string $journal Dergi
     * @param string $publication_date Yayın tarihi
     * @param array $mesh_terms MeSH terimleri
     * @return string İçerik HTML
     */
    private function generate_content($title, $authors_text, $abstract_html, $journal, $publication_date, $mesh_terms) {
        $content = '';
        
        // Giriş bölümü
        $content .= '<div class="pubmed-article-intro">';
        
        // Yazarlar
        if (!empty($authors_text)) {
            $content .= '<p class="pubmed-article-authors"><strong>' . __('Yazarlar:', 'pubmed-health-importer') . '</strong> ' . esc_html($authors_text) . '</p>';
        }
        
        // Dergi ve yayın tarihi
        if (!empty($journal) || !empty($publication_date)) {
            $content .= '<p class="pubmed-article-source">';
            
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
        
        // Özet
        if (!empty($abstract_html)) {
            $content .= '<div class="pubmed-article-abstract">';
            $content .= '<h2>' . __('Özet', 'pubmed-health-importer') . '</h2>';
            $content .= $abstract_html;
            $content .= '</div>';
        }
        
        // İçerik bölümü (Gemini AI ile zenginleştirilecek)
        $content .= '<div class="pubmed-article-content">';
        $content .= '<h2>' . __('Detaylı Bilgi', 'pubmed-health-importer') . '</h2>';
        $content .= '<p>' . __('Bu bölüm, makalenin detaylı içeriğini içerecektir. İçerik, Gemini AI ile zenginleştirilecektir.', 'pubmed-health-importer') . '</p>';
        $content .= '</div>';
        
        // Anahtar kelimeler
        if (!empty($mesh_terms)) {
            $content .= '<div class="pubmed-article-keywords">';
            $content .= '<h3>' . __('Anahtar Kelimeler', 'pubmed-health-importer') . '</h3>';
            $content .= '<p>' . esc_html(implode(', ', $mesh_terms)) . '</p>';
            $content .= '</div>';
        }
        
        // Kaynak
        $content .= '<div class="pubmed-article-source-info">';
        $content .= '<h3>' . __('Kaynak', 'pubmed-health-importer') . '</h3>';
        $content .= '<p>' . sprintf(__('Bu içerik, PubMed\'den alınan bilgiler kullanılarak oluşturulmuştur. Orijinal makaleye <a href="%s" target="_blank" rel="noopener noreferrer">buradan</a> ulaşabilirsiniz.', 'pubmed-health-importer'), esc_url('https://pubmed.ncbi.nlm.nih.gov/' . esc_attr($title))) . '</p>';
        $content .= '</div>';
        
        return $content;
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
