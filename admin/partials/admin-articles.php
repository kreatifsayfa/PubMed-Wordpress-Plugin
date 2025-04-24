<?php
/**
 * Admin makaleler sayfası görünümü
 *
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/admin/partials
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Sayfalama parametreleri
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// Makaleleri al
global $wpdb;
$table_name = $wpdb->prefix . 'pubmed_articles';

$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$articles = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name ORDER BY id DESC LIMIT %d OFFSET %d",
    $per_page, $offset
));

// Toplam sayfa sayısı
$total_pages = ceil($total_items / $per_page);
?>

<div class="wrap pubmed-health-importer-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="pubmed-health-importer-articles">
        <h2><?php _e('İçe Aktarılan Makaleler', 'pubmed-health-importer'); ?></h2>
        
        <?php if (empty($articles)): ?>
            <div class="notice notice-info">
                <p><?php _e('Henüz makale içe aktarılmadı.', 'pubmed-health-importer'); ?></p>
            </div>
        <?php else: ?>
            <div class="tablenav top">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(_n('%s öğe', '%s öğe', $total_items, 'pubmed-health-importer'), number_format_i18n($total_items)); ?>
                    </span>
                    
                    <?php if ($total_pages > 1): ?>
                        <span class="pagination-links">
                            <?php
                            // İlk sayfa
                            if ($paged > 1) {
                                echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1)) . '"><span class="screen-reader-text">' . __('İlk sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&laquo;</span></a>';
                            } else {
                                echo '<span class="first-page button disabled"><span class="screen-reader-text">' . __('İlk sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&laquo;</span></span>';
                            }
                            
                            // Önceki sayfa
                            if ($paged > 1) {
                                echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', max(1, $paged - 1))) . '"><span class="screen-reader-text">' . __('Önceki sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                            } else {
                                echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Önceki sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&lsaquo;</span></span>';
                            }
                            
                            // Sayfa numarası
                            echo '<span class="paging-input">' . sprintf(_x('%1$s / %2$s', 'paging', 'pubmed-health-importer'), $paged, $total_pages) . '</span>';
                            
                            // Sonraki sayfa
                            if ($paged < $total_pages) {
                                echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', min($total_pages, $paged + 1))) . '"><span class="screen-reader-text">' . __('Sonraki sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                            } else {
                                echo '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Sonraki sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&rsaquo;</span></span>';
                            }
                            
                            // Son sayfa
                            if ($paged < $total_pages) {
                                echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages)) . '"><span class="screen-reader-text">' . __('Son sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&raquo;</span></a>';
                            } else {
                                echo '<span class="last-page button disabled"><span class="screen-reader-text">' . __('Son sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&raquo;</span></span>';
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary"><?php _e('Başlık', 'pubmed-health-importer'); ?></th>
                        <th scope="col" class="manage-column column-authors"><?php _e('Yazarlar', 'pubmed-health-importer'); ?></th>
                        <th scope="col" class="manage-column column-journal"><?php _e('Dergi', 'pubmed-health-importer'); ?></th>
                        <th scope="col" class="manage-column column-date"><?php _e('Yayın Tarihi', 'pubmed-health-importer'); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php _e('İşlemler', 'pubmed-health-importer'); ?></th>
                    </tr>
                </thead>
                
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td class="title column-title column-primary">
                                <strong>
                                    <a href="<?php echo get_edit_post_link($article->post_id); ?>">
                                        <?php echo esc_html($article->title); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo get_edit_post_link($article->post_id); ?>"><?php _e('Düzenle', 'pubmed-health-importer'); ?></a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo get_permalink($article->post_id); ?>"><?php _e('Görüntüle', 'pubmed-health-importer'); ?></a> |
                                    </span>
                                    <span class="pubmed">
                                        <a href="https://pubmed.ncbi.nlm.nih.gov/<?php echo esc_attr($article->pubmed_id); ?>/" target="_blank"><?php _e('PubMed\'de Görüntüle', 'pubmed-health-importer'); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td class="authors column-authors">
                                <?php
                                $authors = json_decode($article->authors, true);
                                if (is_array($authors) && !empty($authors)) {
                                    echo esc_html(implode(', ', array_slice($authors, 0, 3)));
                                    
                                    if (count($authors) > 3) {
                                        echo ' ' . sprintf(__('ve %d diğer', 'pubmed-health-importer'), count($authors) - 3);
                                    }
                                }
                                ?>
                            </td>
                            <td class="journal column-journal">
                                <?php echo esc_html($article->journal); ?>
                            </td>
                            <td class="date column-date">
                                <?php echo esc_html($article->publication_date); ?>
                            </td>
                            <td class="actions column-actions">
                                <button type="button" class="button pubmed-enhance-button" data-post-id="<?php echo esc_attr($article->post_id); ?>"><?php _e('İçeriği Zenginleştir', 'pubmed-health-importer'); ?></button>
                                <span class="spinner"></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                
                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary"><?php _e('Başlık', 'pubmed-health-importer'); ?></th>
                        <th scope="col" class="manage-column column-authors"><?php _e('Yazarlar', 'pubmed-health-importer'); ?></th>
                        <th scope="col" class="manage-column column-journal"><?php _e('Dergi', 'pubmed-health-importer'); ?></th>
                        <th scope="col" class="manage-column column-date"><?php _e('Yayın Tarihi', 'pubmed-health-importer'); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php _e('İşlemler', 'pubmed-health-importer'); ?></th>
                    </tr>
                </tfoot>
            </table>
            
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(_n('%s öğe', '%s öğe', $total_items, 'pubmed-health-importer'), number_format_i18n($total_items)); ?>
                    </span>
                    
                    <?php if ($total_pages > 1): ?>
                        <span class="pagination-links">
                            <?php
                            // İlk sayfa
                            if ($paged > 1) {
                                echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1)) . '"><span class="screen-reader-text">' . __('İlk sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&laquo;</span></a>';
                            } else {
                                echo '<span class="first-page button disabled"><span class="screen-reader-text">' . __('İlk sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&laquo;</span></span>';
                            }
                            
                            // Önceki sayfa
                            if ($paged > 1) {
                                echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', max(1, $paged - 1))) . '"><span class="screen-reader-text">' . __('Önceki sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                            } else {
                                echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Önceki sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&lsaquo;</span></span>';
                            }
                            
                            // Sayfa numarası
                            echo '<span class="paging-input">' . sprintf(_x('%1$s / %2$s', 'paging', 'pubmed-health-importer'), $paged, $total_pages) . '</span>';
                            
                            // Sonraki sayfa
                            if ($paged < $total_pages) {
                                echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', min($total_pages, $paged + 1))) . '"><span class="screen-reader-text">' . __('Sonraki sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                            } else {
                                echo '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Sonraki sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&rsaquo;</span></span>';
                            }
                            
                            // Son sayfa
                            if ($paged < $total_pages) {
                                echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages)) . '"><span class="screen-reader-text">' . __('Son sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&raquo;</span></a>';
                            } else {
                                echo '<span class="last-page button disabled"><span class="screen-reader-text">' . __('Son sayfa', 'pubmed-health-importer') . '</span><span aria-hidden="true">&raquo;</span></span>';
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div id="pubmed-enhance-success" class="pubmed-health-importer-enhance-success notice notice-success" style="display: none;">
        <p></p>
    </div>
    
    <div id="pubmed-enhance-error" class="pubmed-health-importer-enhance-error notice notice-error" style="display: none;">
        <p></p>
    </div>
</div>
