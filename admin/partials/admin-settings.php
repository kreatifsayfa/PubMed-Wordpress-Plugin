<?php
/**
 * Admin ayarlar sayfası görünümü
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
    
    <div class="pubmed-health-importer-settings">
        <form method="post" action="options.php">
            <?php
            settings_fields('pubmed_health_importer_settings');
            do_settings_sections('pubmed_health_importer_settings');
            submit_button();
            ?>
        </form>
    </div>
    
    <div class="pubmed-health-importer-settings-help">
        <h2><?php _e('Ayarlar Hakkında Yardım', 'pubmed-health-importer'); ?></h2>
        
        <div class="pubmed-health-importer-settings-help-section">
            <h3><?php _e('API Anahtarları', 'pubmed-health-importer'); ?></h3>
            <p><?php _e('PubMed API anahtarı, NCBI\'dan ücretsiz olarak alınabilir. API anahtarı olmadan da eklenti çalışır, ancak istek limitleri daha düşüktür.', 'pubmed-health-importer'); ?></p>
            <p><?php _e('Gemini AI API anahtarı, içerik zenginleştirme ve çeviri özellikleri için gereklidir. Bu özellikler, içeriğinizi daha kapsamlı ve SEO dostu hale getirir.', 'pubmed-health-importer'); ?></p>
        </div>
        
        <div class="pubmed-health-importer-settings-help-section">
            <h3><?php _e('MeSH Terimleri', 'pubmed-health-importer'); ?></h3>
            <p><?php _e('MeSH (Medical Subject Headings) terimleri, PubMed\'de makaleleri kategorize etmek için kullanılan standart terimlerdir. Eklenti, kadın ve bebek sağlığı ile ilgili MeSH terimlerini otomatik olarak aramalarınıza ekler.', 'pubmed-health-importer'); ?></p>
            <p><?php _e('Varsayılan MeSH terimleri listesini değiştirebilir veya genişletebilirsiniz. Her satıra bir terim yazın.', 'pubmed-health-importer'); ?></p>
        </div>
        
        <div class="pubmed-health-importer-settings-help-section">
            <h3><?php _e('SEO Optimizasyonu', 'pubmed-health-importer'); ?></h3>
            <p><?php _e('SEO optimizasyonu, içeriğinizin arama motorlarında daha iyi sıralanmasını sağlar. Eklenti, şema markup ekler, başlık yapısını optimize eder ve içeriği featured snippet için yapılandırır.', 'pubmed-health-importer'); ?></p>
            <p><?php _e('FAQ oluşturma özelliği, içeriğinize otomatik olarak sık sorulan sorular bölümü ekler. Bu, Google\'da featured snippet (sıfır snippet) elde etme şansınızı artırır.', 'pubmed-health-importer'); ?></p>
        </div>
        
        <div class="pubmed-health-importer-settings-help-section">
            <h3><?php _e('Zamanlanmış Aramalar', 'pubmed-health-importer'); ?></h3>
            <p><?php _e('Zamanlanmış aramalar, belirli aralıklarla otomatik olarak çalıştırılır ve yeni makaleleri içe aktarır. Bu, sitenizi güncel tutmak için mükemmel bir yoldur.', 'pubmed-health-importer'); ?></p>
            <p><?php _e('Otomatik içe aktarma ve otomatik yayınlama özelliklerini etkinleştirerek, tamamen otomatik bir içerik akışı oluşturabilirsiniz.', 'pubmed-health-importer'); ?></p>
        </div>
    </div>
</div>
