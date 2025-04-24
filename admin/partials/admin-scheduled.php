<?php
/**
 * Admin zamanlanmış aramalar sayfası görünümü
 *
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/admin/partials
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Zamanlanmış aramaları al
global $wpdb;
$table_name = $wpdb->prefix . 'pubmed_searches';
$searches = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
?>

<div class="wrap pubmed-health-importer-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="pubmed-health-importer-scheduled-searches">
        <h2><?php _e('Zamanlanmış Aramalar', 'pubmed-health-importer'); ?></h2>
        <p><?php _e('Zamanlanmış aramalar, belirli aralıklarla otomatik olarak çalıştırılır ve yeni makaleleri içe aktarır.', 'pubmed-health-importer'); ?></p>
        
        <div class="pubmed-health-importer-add-search">
            <h3><?php _e('Yeni Zamanlanmış Arama Ekle', 'pubmed-health-importer'); ?></h3>
            
            <form id="pubmed-scheduled-search-form" method="post">
                <input type="hidden" id="pubmed-scheduled-search-id" name="id" value="0">
                
                <div class="pubmed-scheduled-search-field">
                    <label for="pubmed-scheduled-search-name"><?php _e('Arama Adı:', 'pubmed-health-importer'); ?></label>
                    <input type="text" id="pubmed-scheduled-search-name" name="name" class="regular-text" required>
                </div>
                
                <div class="pubmed-scheduled-search-field">
                    <label for="pubmed-scheduled-search-description"><?php _e('Açıklama:', 'pubmed-health-importer'); ?></label>
                    <textarea id="pubmed-scheduled-search-description" name="description" class="large-text" rows="3"></textarea>
                </div>
                
                <div class="pubmed-scheduled-search-field">
                    <label for="pubmed-scheduled-search-query"><?php _e('Arama Sorgusu:', 'pubmed-health-importer'); ?></label>
                    <input type="text" id="pubmed-scheduled-search-query" name="query" class="regular-text" required>
                    <p class="description"><?php _e('Anahtar kelimeler veya MeSH terimleri girin. Sistem otomatik olarak kadın ve bebek sağlığı ile ilgili MeSH terimlerini ekleyecektir.', 'pubmed-health-importer'); ?></p>
                </div>
                
                <div class="pubmed-scheduled-search-field">
                    <label for="pubmed-scheduled-search-count"><?php _e('Sonuç Sayısı:', 'pubmed-health-importer'); ?></label>
                    <select id="pubmed-scheduled-search-count" name="count">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                
                <div class="pubmed-scheduled-search-field">
                    <label for="pubmed-scheduled-search-schedule"><?php _e('Zamanlama:', 'pubmed-health-importer'); ?></label>
                    <select id="pubmed-scheduled-search-schedule" name="schedule">
                        <option value="hourly"><?php _e('Saatlik', 'pubmed-health-importer'); ?></option>
                        <option value="daily" selected><?php _e('Günlük', 'pubmed-health-importer'); ?></option>
                        <option value="weekly"><?php _e('Haftalık', 'pubmed-health-importer'); ?></option>
                    </select>
                </div>
                
                <div class="pubmed-scheduled-search-field">
                    <button type="submit" id="pubmed-scheduled-search-save-button" class="button button-primary"><?php _e('Kaydet', 'pubmed-health-importer'); ?></button>
                    <button type="button" id="pubmed-scheduled-search-cancel-button" class="button" style="display: none;"><?php _e('İptal', 'pubmed-health-importer'); ?></button>
                    <span class="spinner"></span>
                </div>
            </form>
        </div>
        
        <div class="pubmed-health-importer-search-list">
            <h3><?php _e('Mevcut Zamanlanmış Aramalar', 'pubmed-health-importer'); ?></h3>
            
            <?php if (empty($searches)): ?>
                <div class="notice notice-info">
                    <p><?php _e('Henüz zamanlanmış arama eklenmedi.', 'pubmed-health-importer'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-name column-primary"><?php _e('Ad', 'pubmed-health-importer'); ?></th>
                            <th scope="col" class="manage-column column-query"><?php _e('Sorgu', 'pubmed-health-importer'); ?></th>
                            <th scope="col" class="manage-column column-schedule"><?php _e('Zamanlama', 'pubmed-health-importer'); ?></th>
                            <th scope="col" class="manage-column column-last-run"><?php _e('Son Çalıştırma', 'pubmed-health-importer'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php _e('İşlemler', 'pubmed-health-importer'); ?></th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php foreach ($searches as $search): ?>
                            <tr data-id="<?php echo esc_attr($search->id); ?>">
                                <td class="name column-name column-primary">
                                    <strong><?php echo esc_html($search->name); ?></strong>
                                    <?php if (!empty($search->description)): ?>
                                        <p class="description"><?php echo esc_html($search->description); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="query column-query">
                                    <?php
                                    $search_params = json_decode($search->search_params, true);
                                    echo esc_html($search_params['query']);
                                    ?>
                                </td>
                                <td class="schedule column-schedule">
                                    <?php
                                    switch ($search->schedule) {
                                        case 'hourly':
                                            _e('Saatlik', 'pubmed-health-importer');
                                            break;
                                        case 'daily':
                                            _e('Günlük', 'pubmed-health-importer');
                                            break;
                                        case 'weekly':
                                            _e('Haftalık', 'pubmed-health-importer');
                                            break;
                                        default:
                                            echo esc_html($search->schedule);
                                            break;
                                    }
                                    ?>
                                </td>
                                <td class="last-run column-last-run">
                                    <?php
                                    if (!empty($search->last_run)) {
                                        echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($search->last_run)));
                                    } else {
                                        _e('Henüz çalıştırılmadı', 'pubmed-health-importer');
                                    }
                                    ?>
                                </td>
                                <td class="actions column-actions">
                                    <button type="button" class="button pubmed-scheduled-search-run-button" data-id="<?php echo esc_attr($search->id); ?>"><?php _e('Çalıştır', 'pubmed-health-importer'); ?></button>
                                    <button type="button" class="button pubmed-scheduled-search-edit-button" data-id="<?php echo esc_attr($search->id); ?>"><?php _e('Düzenle', 'pubmed-health-importer'); ?></button>
                                    <button type="button" class="button pubmed-scheduled-search-delete-button" data-id="<?php echo esc_attr($search->id); ?>"><?php _e('Sil', 'pubmed-health-importer'); ?></button>
                                    <span class="spinner"></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    
                    <tfoot>
                        <tr>
                            <th scope="col" class="manage-column column-name column-primary"><?php _e('Ad', 'pubmed-health-importer'); ?></th>
                            <th scope="col" class="manage-column column-query"><?php _e('Sorgu', 'pubmed-health-importer'); ?></th>
                            <th scope="col" class="manage-column column-schedule"><?php _e('Zamanlama', 'pubmed-health-importer'); ?></th>
                            <th scope="col" class="manage-column column-last-run"><?php _e('Son Çalıştırma', 'pubmed-health-importer'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php _e('İşlemler', 'pubmed-health-importer'); ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="pubmed-scheduled-search-success" class="pubmed-health-importer-scheduled-search-success notice notice-success" style="display: none;">
        <p></p>
    </div>
    
    <div id="pubmed-scheduled-search-error" class="pubmed-health-importer-scheduled-search-error notice notice-error" style="display: none;">
        <p></p>
    </div>
    
    <div id="pubmed-scheduled-search-run-results" class="pubmed-health-importer-scheduled-search-run-results" style="display: none;">
        <h3><?php _e('Arama Sonuçları', 'pubmed-health-importer'); ?></h3>
        <div id="pubmed-scheduled-search-results-count"></div>
        <div id="pubmed-scheduled-search-results-list"></div>
    </div>
</div>
