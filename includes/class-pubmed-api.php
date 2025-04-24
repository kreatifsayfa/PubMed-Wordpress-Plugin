<?php
/**
 * PubMed API sınıfı
 * 
 * PubMed E-utilities API ile iletişim kurmak için kullanılır
 * 
 * @link https://www.ncbi.nlm.nih.gov/books/NBK25500/
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/includes
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PubMed API sınıfı
 */
class PubMed_API {

    /**
     * API temel URL'si
     *
     * @var string
     */
    private $base_url = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/';

    /**
     * API anahtarı
     *
     * @var string
     */
    private $api_key = '';

    /**
     * Tool parametresi
     *
     * @var string
     */
    private $tool = '';

    /**
     * Email parametresi
     *
     * @var string
     */
    private $email = '';

    /**
     * Önbellek süresi (saniye)
     *
     * @var int
     */
    private $cache_duration = 86400; // 24 saat

    /**
     * Constructor
     */
    public function __construct() {
        // Ayarları al
        $settings = get_option('pubmed_health_importer_settings');
        
        if ($settings) {
            $this->api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
            $this->tool = isset($settings['tool']) ? $settings['tool'] : 'pubmed_health_importer';
            $this->email = isset($settings['email']) ? $settings['email'] : '';
            $this->cache_duration = isset($settings['cache_duration']) ? intval($settings['cache_duration']) : 86400;
        }
    }

    /**
     * PubMed'de arama yapar
     *
     * @param string $query Arama sorgusu
     * @param int $count Sonuç sayısı
     * @param int $start Başlangıç indeksi
     * @return array|WP_Error Arama sonuçları veya hata
     */
    public function search($query, $count = 10, $start = 0) {
        // Kadın ve bebek sağlığı ile ilgili MeSH terimlerini ekle
        $query = $this->add_health_terms($query);
        
        // Önbellekte var mı kontrol et
        $cache_key = 'pubmed_search_' . md5($query . $count . $start);
        $cached_results = get_transient($cache_key);
        
        if ($cached_results !== false) {
            return $cached_results;
        }
        
        // ESearch parametreleri
        $params = array(
            'db' => 'pubmed',
            'term' => $query,
            'retmax' => $count,
            'retstart' => $start,
            'usehistory' => 'y',
            'retmode' => 'json',
            'sort' => 'relevance',
            'tool' => $this->tool,
            'email' => $this->email,
        );
        
        // API anahtarı ekle
        if (!empty($this->api_key)) {
            $params['api_key'] = $this->api_key;
        }
        
        // ESearch isteği gönder
        $esearch_url = $this->base_url . 'esearch.fcgi?' . http_build_query($params);
        $response = wp_remote_get($esearch_url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('JSON çözümleme hatası', 'pubmed-health-importer'));
        }
        
        if (!isset($data['esearchresult']['idlist']) || empty($data['esearchresult']['idlist'])) {
            return array(
                'total' => 0,
                'articles' => array(),
            );
        }
        
        // ID'leri al
        $ids = $data['esearchresult']['idlist'];
        
        // ESummary parametreleri
        $summary_params = array(
            'db' => 'pubmed',
            'id' => implode(',', $ids),
            'retmode' => 'json',
            'tool' => $this->tool,
            'email' => $this->email,
        );
        
        // API anahtarı ekle
        if (!empty($this->api_key)) {
            $summary_params['api_key'] = $this->api_key;
        }
        
        // ESummary isteği gönder
        $esummary_url = $this->base_url . 'esummary.fcgi?' . http_build_query($summary_params);
        $summary_response = wp_remote_get($esummary_url);
        
        if (is_wp_error($summary_response)) {
            return $summary_response;
        }
        
        $summary_body = wp_remote_retrieve_body($summary_response);
        $summary_data = json_decode($summary_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('JSON çözümleme hatası', 'pubmed-health-importer'));
        }
        
        // Sonuçları işle
        $articles = array();
        
        foreach ($ids as $id) {
            if (isset($summary_data['result'][$id])) {
                $article = $summary_data['result'][$id];
                
                // Yazarları işle
                $authors = array();
                if (isset($article['authors']) && is_array($article['authors'])) {
                    foreach ($article['authors'] as $author) {
                        if (isset($author['name'])) {
                            $authors[] = $author['name'];
                        }
                    }
                }
                
                // Yayın tarihini işle
                $pub_date = '';
                if (isset($article['pubdate'])) {
                    $pub_date = $article['pubdate'];
                }
                
                // Özeti işle
                $abstract = '';
                if (isset($article['title'])) {
                    // Özet için EFetch kullanılacak, şimdilik boş bırakıyoruz
                }
                
                $articles[] = array(
                    'id' => $id,
                    'title' => isset($article['title']) ? $article['title'] : '',
                    'authors' => $authors,
                    'journal' => isset($article['fulljournalname']) ? $article['fulljournalname'] : '',
                    'publication_date' => $pub_date,
                    'abstract' => $abstract,
                );
            }
        }
        
        $results = array(
            'total' => isset($data['esearchresult']['count']) ? intval($data['esearchresult']['count']) : count($articles),
            'articles' => $articles,
        );
        
        // Önbelleğe kaydet
        set_transient($cache_key, $results, $this->cache_duration);
        
        return $results;
    }

    /**
     * PubMed makale detaylarını alır
     *
     * @param string $id PubMed ID
     * @return array|WP_Error Makale detayları veya hata
     */
    public function get_article($id) {
        // Önbellekte var mı kontrol et
        $cache_key = 'pubmed_article_' . $id;
        $cached_article = get_transient($cache_key);
        
        if ($cached_article !== false) {
            return $cached_article;
        }
        
        // EFetch parametreleri
        $params = array(
            'db' => 'pubmed',
            'id' => $id,
            'retmode' => 'xml',
            'rettype' => 'abstract',
            'tool' => $this->tool,
            'email' => $this->email,
        );
        
        // API anahtarı ekle
        if (!empty($this->api_key)) {
            $params['api_key'] = $this->api_key;
        }
        
        // EFetch isteği gönder
        $efetch_url = $this->base_url . 'efetch.fcgi?' . http_build_query($params);
        $response = wp_remote_get($efetch_url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // XML'i işle
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            return new WP_Error('xml_error', __('XML çözümleme hatası', 'pubmed-health-importer'));
        }
        
        // Makale bilgilerini çıkar
        $article = array(
            'id' => $id,
            'title' => '',
            'authors' => array(),
            'abstract' => '',
            'journal' => '',
            'publication_date' => '',
            'mesh_terms' => array(),
        );
        
        // Başlık
        if (isset($xml->PubmedArticle->MedlineCitation->Article->ArticleTitle)) {
            $article['title'] = (string) $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;
        }
        
        // Yazarlar
        if (isset($xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author)) {
            foreach ($xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author as $author) {
                $name = '';
                
                if (isset($author->LastName) && isset($author->ForeName)) {
                    $name = (string) $author->LastName . ' ' . (string) $author->ForeName;
                } elseif (isset($author->LastName)) {
                    $name = (string) $author->LastName;
                } elseif (isset($author->CollectiveName)) {
                    $name = (string) $author->CollectiveName;
                }
                
                if (!empty($name)) {
                    $article['authors'][] = $name;
                }
            }
        }
        
        // Özet
        if (isset($xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText)) {
            $abstract = '';
            
            foreach ($xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText as $abstract_text) {
                $label = '';
                
                if (isset($abstract_text['Label'])) {
                    $label = (string) $abstract_text['Label'] . ': ';
                }
                
                $abstract .= $label . (string) $abstract_text . "\n\n";
            }
            
            $article['abstract'] = trim($abstract);
        }
        
        // Dergi
        if (isset($xml->PubmedArticle->MedlineCitation->Article->Journal->Title)) {
            $article['journal'] = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;
        }
        
        // Yayın tarihi
        if (isset($xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate)) {
            $pub_date = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate;
            $year = isset($pub_date->Year) ? (string) $pub_date->Year : '';
            $month = isset($pub_date->Month) ? (string) $pub_date->Month : '';
            $day = isset($pub_date->Day) ? (string) $pub_date->Day : '';
            
            if (!empty($year)) {
                $article['publication_date'] = $year;
                
                if (!empty($month)) {
                    $article['publication_date'] .= '-' . $this->format_month($month);
                    
                    if (!empty($day)) {
                        $article['publication_date'] .= '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                    }
                }
            }
        }
        
        // MeSH terimleri
        if (isset($xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading)) {
            foreach ($xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading as $mesh) {
                if (isset($mesh->DescriptorName)) {
                    $article['mesh_terms'][] = (string) $mesh->DescriptorName;
                }
            }
        }
        
        // Önbelleğe kaydet
        set_transient($cache_key, $article, $this->cache_duration);
        
        return $article;
    }

    /**
     * İlgili makaleleri alır
     *
     * @param string $id PubMed ID
     * @param int $count Sonuç sayısı
     * @return array|WP_Error İlgili makaleler veya hata
     */
    public function get_related_articles($id, $count = 5) {
        // Önbellekte var mı kontrol et
        $cache_key = 'pubmed_related_' . $id . '_' . $count;
        $cached_results = get_transient($cache_key);
        
        if ($cached_results !== false) {
            return $cached_results;
        }
        
        // ELink parametreleri
        $params = array(
            'dbfrom' => 'pubmed',
            'db' => 'pubmed',
            'id' => $id,
            'cmd' => 'neighbor_score',
            'retmode' => 'json',
            'tool' => $this->tool,
            'email' => $this->email,
        );
        
        // API anahtarı ekle
        if (!empty($this->api_key)) {
            $params['api_key'] = $this->api_key;
        }
        
        // ELink isteği gönder
        $elink_url = $this->base_url . 'elink.fcgi?' . http_build_query($params);
        $response = wp_remote_get($elink_url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('JSON çözümleme hatası', 'pubmed-health-importer'));
        }
        
        // İlgili makale ID'lerini çıkar
        $related_ids = array();
        
        if (isset($data['linksets'][0]['linksetdbs'][0]['links'])) {
            $links = $data['linksets'][0]['linksetdbs'][0]['links'];
            
            foreach ($links as $link) {
                if (isset($link['id'])) {
                    $related_ids[] = $link['id'];
                    
                    if (count($related_ids) >= $count) {
                        break;
                    }
                }
            }
        }
        
        if (empty($related_ids)) {
            return array(
                'total' => 0,
                'articles' => array(),
            );
        }
        
        // ESummary parametreleri
        $summary_params = array(
            'db' => 'pubmed',
            'id' => implode(',', $related_ids),
            'retmode' => 'json',
            'tool' => $this->tool,
            'email' => $this->email,
        );
        
        // API anahtarı ekle
        if (!empty($this->api_key)) {
            $summary_params['api_key'] = $this->api_key;
        }
        
        // ESummary isteği gönder
        $esummary_url = $this->base_url . 'esummary.fcgi?' . http_build_query($summary_params);
        $summary_response = wp_remote_get($esummary_url);
        
        if (is_wp_error($summary_response)) {
            return $summary_response;
        }
        
        $summary_body = wp_remote_retrieve_body($summary_response);
        $summary_data = json_decode($summary_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('JSON çözümleme hatası', 'pubmed-health-importer'));
        }
        
        // Sonuçları işle
        $articles = array();
        
        foreach ($related_ids as $related_id) {
            if (isset($summary_data['result'][$related_id])) {
                $article = $summary_data['result'][$related_id];
                
                // Yazarları işle
                $authors = array();
                if (isset($article['authors']) && is_array($article['authors'])) {
                    foreach ($article['authors'] as $author) {
                        if (isset($author['name'])) {
                            $authors[] = $author['name'];
                        }
                    }
                }
                
                // Yayın tarihini işle
                $pub_date = '';
                if (isset($article['pubdate'])) {
                    $pub_date = $article['pubdate'];
                }
                
                $articles[] = array(
                    'id' => $related_id,
                    'title' => isset($article['title']) ? $article['title'] : '',
                    'authors' => $authors,
                    'journal' => isset($article['fulljournalname']) ? $article['fulljournalname'] : '',
                    'publication_date' => $pub_date,
                );
            }
        }
        
        $results = array(
            'total' => count($articles),
            'articles' => $articles,
        );
        
        // Önbelleğe kaydet
        set_transient($cache_key, $results, $this->cache_duration);
        
        return $results;
    }

    /**
     * Zamanlanmış arama yapar
     *
     * @param array $search_params Arama parametreleri
     * @return array|WP_Error Arama sonuçları veya hata
     */
    public function scheduled_search($search_params) {
        $query = isset($search_params['query']) ? $search_params['query'] : '';
        $count = isset($search_params['count']) ? intval($search_params['count']) : 10;
        $date_range = isset($search_params['date_range']) ? $search_params['date_range'] : '30days';
        
        if (empty($query)) {
            return new WP_Error('empty_query', __('Arama sorgusu boş olamaz.', 'pubmed-health-importer'));
        }
        
        // Tarih aralığını ekle
        $date_query = $this->add_date_range($query, $date_range);
        
        // Arama yap
        return $this->search($date_query, $count);
    }

    /**
     * Sorguya kadın ve bebek sağlığı ile ilgili MeSH terimlerini ekler
     *
     * @param string $query Arama sorgusu
     * @return string Genişletilmiş sorgu
     */
    private function add_health_terms($query) {
        // Ayarları al
        $settings = get_option('pubmed_health_importer_settings');
        $mesh_terms = isset($settings['mesh_terms']) ? $settings['mesh_terms'] : array();
        
        // Sorgu zaten MeSH terimleri içeriyorsa değiştirme
        if (strpos($query, '[MeSH') !== false) {
            return $query;
        }
        
        // Kadın ve bebek sağlığı ile ilgili MeSH terimlerini ekle
        $health_query = '(' . $query . ') AND (';
        
        $term_parts = array();
        foreach ($mesh_terms as $term) {
            $term_parts[] = '"' . $term . '"[MeSH]';
        }
        
        $health_query .= implode(' OR ', $term_parts) . ')';
        
        return $health_query;
    }

    /**
     * Sorguya tarih aralığı ekler
     *
     * @param string $query Arama sorgusu
     * @param string $date_range Tarih aralığı (7days, 30days, 60days, 90days, 180days, 1year)
     * @return string Tarih aralığı eklenmiş sorgu
     */
    private function add_date_range($query, $date_range) {
        $date_filter = '';
        
        switch ($date_range) {
            case '7days':
                $date_filter = ' AND ("last 7 days"[PDat])';
                break;
            case '30days':
                $date_filter = ' AND ("last 30 days"[PDat])';
                break;
            case '60days':
                $date_filter = ' AND ("last 60 days"[PDat])';
                break;
            case '90days':
                $date_filter = ' AND ("last 90 days"[PDat])';
                break;
            case '180days':
                $date_filter = ' AND ("last 180 days"[PDat])';
                break;
            case '1year':
                $date_filter = ' AND ("last 1 year"[PDat])';
                break;
            default:
                // Tarih filtresi yok
                break;
        }
        
        return $query . $date_filter;
    }

    /**
     * Ay formatını düzenler
     *
     * @param string $month Ay (isim veya sayı)
     * @return string Formatlanmış ay (01-12)
     */
    private function format_month($month) {
        $month_map = array(
            'Jan' => '01',
            'Feb' => '02',
            'Mar' => '03',
            'Apr' => '04',
            'May' => '05',
            'Jun' => '06',
            'Jul' => '07',
            'Aug' => '08',
            'Sep' => '09',
            'Oct' => '10',
            'Nov' => '11',
            'Dec' => '12',
        );
        
        if (isset($month_map[$month])) {
            return $month_map[$month];
        }
        
        if (is_numeric($month)) {
            return str_pad($month, 2, '0', STR_PAD_LEFT);
        }
        
        return '01';
    }
}
