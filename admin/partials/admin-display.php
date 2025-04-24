<?php
/**
 * Admin ana sayfa görünümü
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
    
    <div class="pubmed-health-importer-welcome">
        <h2><?php _e('PubMed Health Importer\'a Hoş Geldiniz!', 'pubmed-health-importer'); ?></h2>
        <p><?php _e('Bu eklenti, PubMed\'den kadın ve bebek sağlığı ile ilgili makaleleri WordPress sitenize içe aktarmanızı sağlar.', 'pubmed-health-importer'); ?></p>
        <p><?php _e('Gemini AI entegrasyonu ile içeriklerinizi zenginleştirebilir, SEO optimizasyonu yapabilir ve Google featured snippet (sıfır snippet) için içeriklerinizi optimize edebilirsiniz.', 'pubmed-health-importer'); ?></p>
    </div>
    
    <div class="pubmed-health-importer-cards">
        <div class="pubmed-health-importer-card">
            <h3><?php _e('PubMed Arama', 'pubmed-health-importer'); ?></h3>
            <p><?php _e('PubMed\'de arama yapın ve sonuçları sitenize içe aktarın.', 'pubmed-health-importer'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=pubmed-health-importer-search'); ?>" class="button button-primary"><?php _e('Aramaya Başla', 'pubmed-health-importer'); ?></a>
        </div>
        
        <div class="pubmed-health-importer-card">
            <h3><?php _e('İçe Aktarılan Makaleler', 'pubmed-health-importer'); ?></h3>
            <p><?php _e('İçe aktarılan makaleleri görüntüleyin ve yönetin.', 'pubmed-health-importer'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=pubmed-health-importer-articles'); ?>" class="button button-primary"><?php _e('Makaleleri Görüntüle', 'pubmed-health-importer'); ?></a>
        </div>
        
        <div class="pubmed-health-importer-card">
            <h3><?php _e('Zamanlanmış Aramalar', 'pubmed-health-importer'); ?></h3>
            <p><?php _e('Otomatik içe aktarma için zamanlanmış aramalar oluşturun.', 'pubmed-health-importer'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=pubmed-health-importer-scheduled'); ?>" class="button button-primary"><?php _e('Zamanlanmış Aramaları Yönet', 'pubmed-health-importer'); ?></a>
        </div>
        
        <div class="pubmed-health-importer-card">
            <h3><?php _e('Ayarlar', 'pubmed-health-importer'); ?></h3>
            <p><?php _e('Eklenti ayarlarını yapılandırın.', 'pubmed-health-importer'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=pubmed-health-importer-settings'); ?>" class="button button-primary"><?php _e('Ayarları Yapılandır', 'pubmed-health-importer'); ?></a>
        </div>
    </div>
    
    <div class="pubmed-health-importer-stats">
        <h2><?php _e('İstatistikler', 'pubmed-health-importer'); ?></h2>
        
        <?php
        global $wpdb;
        
        // İçe aktarılan makale sayısı
        $article_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pubmed_articles");
        
        // Zamanlanmış arama sayısı
        $search_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pubmed_searches");
        
        // Son içe aktarılan makale
        $last_article = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}pubmed_articles ORDER BY id DESC LIMIT 1");
        
        // Son çalıştırılan zamanlanmış arama
        $last_search = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}pubmed_searches ORDER BY last_run DESC LIMIT 1");
        ?>
        
        <div class="pubmed-health-importer-stat-cards">
            <div class="pubmed-health-importer-stat-card">
                <h3><?php _e('İçe Aktarılan Makaleler', 'pubmed-health-importer'); ?></h3>
                <p class="pubmed-health-importer-stat-number"><?php echo esc_html($article_count); ?></p>
            </div>
            
            <div class="pubmed-health-importer-stat-card">
                <h3><?php _e('Zamanlanmış Aramalar', 'pubmed-health-importer'); ?></h3>
                <p class="pubmed-health-importer-stat-number"><?php echo esc_html($search_count); ?></p>
            </div>
            
            <div class="pubmed-health-importer-stat-card">
                <h3><?php _e('Son İçe Aktarılan Makale', 'pubmed-health-importer'); ?></h3>
                <?php if ($last_article): ?>
                    <p><?php echo esc_html($last_article->title); ?></p>
                    <a href="<?php echo get_edit_post_link($last_article->post_id); ?>" class="button button-secondary"><?php _e('Düzenle', 'pubmed-health-importer'); ?></a>
                <?php else: ?>
                    <p><?php _e('Henüz makale içe aktarılmadı.', 'pubmed-health-importer'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="pubmed-health-importer-stat-card">
                <h3><?php _e('Son Çalıştırılan Arama', 'pubmed-health-importer'); ?></h3>
                <?php if ($last_search && $last_search->last_run): ?>
                    <p><?php echo esc_html($last_search->name); ?></p>
                    <p><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_search->last_run))); ?></p>
                <?php else: ?>
                    <p><?php _e('Henüz zamanlanmış arama çalıştırılmadı.', 'pubmed-health-importer'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="pubmed-health-importer-help">
        <h2><?php _e('Yardım ve Destek', 'pubmed-health-importer'); ?></h2>
        <p><?php _e('Eklenti kullanımı hakkında daha fazla bilgi için:', 'pubmed-health-importer'); ?></p>
        <ul>
            <li><a href="#" target="_blank"><?php _e('Dokümantasyon', 'pubmed-health-importer'); ?></a></li>
            <li><a href="#" target="_blank"><?php _e('SSS', 'pubmed-health-importer'); ?></a></li>
            <li><a href="#" target="_blank"><?php _e('Destek', 'pubmed-health-importer'); ?></a></li>
        </ul>
    </div>
</div>
