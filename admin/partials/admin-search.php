<?php
/**
 * Admin arama sayfası görünümü
 *
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/admin/partials
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pubmed-health-importer-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="pubmed-health-importer-search-form">
        <h2><?php _e('PubMed\'de Arama Yap', 'pubmed-health-importer'); ?></h2>
        <p><?php _e('Kadın ve bebek sağlığı ile ilgili makaleleri aramak için aşağıdaki formu kullanın.', 'pubmed-health-importer'); ?></p>
        
        <form id="pubmed-search-form" method="post">
            <div class="pubmed-search-field">
                <label for="pubmed-search-query"><?php _e('Arama Sorgusu:', 'pubmed-health-importer'); ?></label>
                <input type="text" id="pubmed-search-query" name="query" class="regular-text" placeholder="<?php esc_attr_e('Örn: pregnancy nutrition', 'pubmed-health-importer'); ?>" required>
                <p class="description"><?php _e('Anahtar kelimeler veya MeSH terimleri girin. Sistem otomatik olarak kadın ve bebek sağlığı ile ilgili MeSH terimlerini ekleyecektir.', 'pubmed-health-importer'); ?></p>
            </div>
            
            <div class="pubmed-search-field">
                <label for="pubmed-search-count"><?php _e('Sonuç Sayısı:', 'pubmed-health-importer'); ?></label>
                <select id="pubmed-search-count" name="count">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            
            <div class="pubmed-search-field">
                <button type="submit" id="pubmed-search-button" class="button button-primary"><?php _e('Ara', 'pubmed-health-importer'); ?></button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
    
    <div id="pubmed-search-results" class="pubmed-health-importer-search-results" style="display: none;">
        <h2><?php _e('Arama Sonuçları', 'pubmed-health-importer'); ?></h2>
        <div id="pubmed-results-count"></div>
        <div id="pubmed-results-list"></div>
    </div>
    
    <div id="pubmed-search-error" class="pubmed-health-importer-search-error notice notice-error" style="display: none;">
        <p></p>
    </div>
    
    <div id="pubmed-import-success" class="pubmed-health-importer-import-success notice notice-success" style="display: none;">
        <p></p>
    </div>
    
    <div id="pubmed-import-error" class="pubmed-health-importer-import-error notice notice-error" style="display: none;">
        <p></p>
    </div>
    
    <script type="text/template" id="pubmed-result-template">
        <div class="pubmed-result-item" data-pubmed-id="{{ id }}">
            <h3>{{ title }}</h3>
            <div class="pubmed-result-meta">
                <span class="pubmed-result-authors">{{ authors }}</span>
                <span class="pubmed-result-journal">{{ journal }}</span>
                <span class="pubmed-result-date">{{ publication_date }}</span>
            </div>
            <div class="pubmed-result-actions">
                <button type="button" class="button pubmed-view-abstract-button"><?php _e('Özeti Görüntüle', 'pubmed-health-importer'); ?></button>
                <button type="button" class="button button-primary pubmed-import-button"><?php _e('İçe Aktar', 'pubmed-health-importer'); ?></button>
                <span class="spinner"></span>
            </div>
            <div class="pubmed-result-abstract" style="display: none;">
                <h4><?php _e('Özet', 'pubmed-health-importer'); ?></h4>
                <p>{{ abstract }}</p>
            </div>
        </div>
    </script>
</div>
