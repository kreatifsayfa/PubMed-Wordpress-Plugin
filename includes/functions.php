<?php
/**
 * Yardımcı fonksiyonlar
 *
 * @package PubMed_Health_Importer
 * @subpackage PubMed_Health_Importer/includes
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PubMed makalesinin FAQ'sını görüntüler
 *
 * @param int $post_id Yazı ID
 * @return string FAQ HTML
 */
function pubmed_get_faq($post_id = 0) {
    if ($post_id === 0) {
        $post_id = get_the_ID();
    }

    $faq = get_post_meta($post_id, 'pubmed_faq', true);

    if (empty($faq) || !is_array($faq)) {
        return '';
    }

    $html = '<div class="pubmed-faq" style="margin: 30px 0;">';
    $html .= '<h2 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">' . __('Sıkça Sorulan Sorular', 'pubmed-health-importer') . '</h2>';
    $html .= '<div class="pubmed-faq-items">';

    foreach ($faq as $index => $item) {
        $question = isset($item['question']) ? esc_html($item['question']) : '';
        $answer = isset($item['answer']) ? esc_html($item['answer']) : '';

        if (!empty($question) && !empty($answer)) {
            $html .= '<div class="pubmed-faq-item" style="margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; overflow: hidden;">';
            $html .= '<div class="pubmed-faq-question" style="background: #f5f5f5; padding: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleFaq(' . $index . ')">';
            $html .= '<strong style="margin: 0;">' . $question . '</strong>';
            $html .= '<span class="pubmed-faq-icon" style="font-size: 20px; transition: transform 0.3s;">+</span>';
            $html .= '</div>';
            $html .= '<div class="pubmed-faq-answer" id="faq-answer-' . $index . '" style="padding: 15px; display: none; background: #fff;">';
            $html .= '<p style="margin: 0;">' . $answer . '</p>';
            $html .= '</div>';
            $html .= '</div>';
        }
    }

    $html .= '</div>';
    $html .= '</div>';

    // JavaScript'i ekle
    $html .= '<script>
    function toggleFaq(index) {
        var answer = document.getElementById("faq-answer-" + index);
        var icon = answer.previousElementSibling.querySelector(".pubmed-faq-icon");

        if (answer.style.display === "none") {
            answer.style.display = "block";
            icon.textContent = "−";
            icon.style.transform = "rotate(180deg)";
        } else {
            answer.style.display = "none";
            icon.textContent = "+";
            icon.style.transform = "rotate(0deg)";
        }
    }
    </script>';

    return $html;
}

/**
 * FAQ'yı içerikten sonra otomatik ekler
 *
 * @param string $content İçerik
 * @return string Güncellenmiş içerik
 */
function pubmed_auto_add_faq($content) {
    if (is_singular('pubmed_article')) {
        $faq = pubmed_get_faq();

        if (!empty($faq)) {
            $content .= $faq;
        }
    }

    return $content;
}
add_filter('the_content', 'pubmed_auto_add_faq');

/**
 * Schema markup'ı head'e ekler
 */
function pubmed_add_schema_markup() {
    if (!is_singular('pubmed_article')) {
        return;
    }

    $post_id = get_the_ID();
    $schema = get_post_meta($post_id, 'pubmed_schema_markup', true);

    if (!empty($schema)) {
        // JSON validasyonu ve güvenlik
        $json_data = json_decode($schema, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '<script type="application/ld+json">' . wp_json_encode($json_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        }
    }
}
add_action('wp_head', 'pubmed_add_schema_markup');

/**
 * SEO meta etiketlerini ekler
 */
function pubmed_add_seo_meta_tags() {
    if (!is_singular('pubmed_article')) {
        return;
    }

    $post_id = get_the_ID();

    // SEO başlığı
    $seo_title = get_post_meta($post_id, 'pubmed_seo_title', true);
    if (!empty($seo_title)) {
        echo '<meta name="title" content="' . esc_attr($seo_title) . '">' . "\n";
    }

    // SEO açıklaması
    $seo_description = get_post_meta($post_id, 'pubmed_seo_description', true);
    if (!empty($seo_description)) {
        echo '<meta name="description" content="' . esc_attr($seo_description) . '">' . "\n";
    }

    // Open Graph etiketleri
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr(get_the_excerpt() ? wp_strip_all_tags(get_the_excerpt()) : $seo_description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
    echo '<meta property="og:locale" content="tr_TR">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

    // Canonical URL
    echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '">' . "\n";

    if (has_post_thumbnail()) {
        echo '<meta property="og:image" content="' . esc_url(get_the_post_thumbnail_url($post_id, 'large')) . '">' . "\n";
    }

    // Twitter Card etiketleri
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr(get_the_excerpt() ? wp_strip_all_tags(get_the_excerpt()) : $seo_description) . '">' . "\n";

    if (has_post_thumbnail()) {
        echo '<meta name="twitter:image" content="' . esc_url(get_the_post_thumbnail_url($post_id, 'large')) . '">' . "\n";
    }
}
add_action('wp_head', 'pubmed_add_seo_meta_tags', 1);

/**
 * PubMed makalesi kaynak bilgilerini gösterir
 *
 * @param int $post_id Yazı ID
 * @return string Kaynak HTML
 */
function pubmed_get_source_info($post_id = 0) {
    if ($post_id === 0) {
        $post_id = get_the_ID();
    }

    $pubmed_id = get_post_meta($post_id, 'pubmed_id', true);
    $journal = get_post_meta($post_id, 'pubmed_journal', true);
    $publication_date = get_post_meta($post_id, 'pubmed_publication_date', true);
    $authors = get_post_meta($post_id, 'pubmed_authors', true);

    if (empty($pubmed_id)) {
        return '';
    }

    $html = '<div class="pubmed-source-info" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #0073aa; border-radius: 4px;">';
    $html .= '<h3 style="margin-top: 0; color: #0073aa;">' . __('Bilimsel Kaynak', 'pubmed-health-importer') . '</h3>';

    if (!empty($authors) && is_array($authors)) {
        $html .= '<p><strong>' . __('Yazarlar:', 'pubmed-health-importer') . '</strong> ' . esc_html(implode(', ', $authors)) . '</p>';
    }

    if (!empty($journal)) {
        $html .= '<p><strong>' . __('Dergi:', 'pubmed-health-importer') . '</strong> ' . esc_html($journal) . '</p>';
    }

    if (!empty($publication_date)) {
        $html .= '<p><strong>' . __('Yayın Tarihi:', 'pubmed-health-importer') . '</strong> ' . esc_html($publication_date) . '</p>';
    }

    $html .= '<p><strong>' . __('PubMed ID:', 'pubmed-health-importer') . '</strong> <a href="https://pubmed.ncbi.nlm.nih.gov/' . esc_attr($pubmed_id) . '" target="_blank" rel="noopener noreferrer">' . esc_html($pubmed_id) . '</a></p>';
    $html .= '</div>';

    return $html;
}

/**
 * Featured snippet içeriğini gösterir
 *
 * @param int $post_id Yazı ID
 * @return string Featured snippet HTML
 */
function pubmed_get_featured_snippet($post_id = 0) {
    if ($post_id === 0) {
        $post_id = get_the_ID();
    }

    $snippet = get_post_meta($post_id, 'pubmed_featured_snippet', true);

    if (empty($snippet) || !is_array($snippet)) {
        return '';
    }

    $html = '';

    // Tanım snippet'i
    if (isset($snippet['definition']) && !empty($snippet['definition']['content'])) {
        $html .= '<div class="pubmed-snippet-definition" style="margin-bottom: 20px; padding: 15px; background: #e8f4f8; border-left: 4px solid #0073aa; border-radius: 4px;">';
        $html .= '<p style="margin: 0; font-size: 16px; line-height: 1.6;">' . esc_html($snippet['definition']['content']) . '</p>';
        $html .= '</div>';
    }

    // Liste snippet'i
    if (isset($snippet['list']) && !empty($snippet['list']['items'])) {
        $html .= '<div class="pubmed-snippet-list" style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #0073aa;">' . esc_html($snippet['list']['title']) . '</h4>';
        $html .= '<ul style="list-style: none; padding: 0;">';

        foreach ($snippet['list']['items'] as $item) {
            $html .= '<li style="padding: 8px 0; border-bottom: 1px solid #eee;">✓ ' . esc_html($item) . '</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';
    }

    return $html;
}

/**
 * Zamanlanmış arama işleyicisi
 */
function pubmed_process_scheduled_searches() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pubmed_searches';
    $current_time = current_time('mysql');

    // Çalışması gereken aramaları al
    $searches = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name
        WHERE schedule IN ('hourly', 'daily', 'weekly')
        AND (last_run IS NULL OR last_run < DATE_SUB(%s, INTERVAL CASE
            WHEN schedule = 'hourly' THEN 1 HOUR
            WHEN schedule = 'daily' THEN 1 DAY
            WHEN schedule = 'weekly' THEN 1 WEEK
        END))",
        $current_time
    ));

    if (empty($searches)) {
        return;
    }

    $pubmed_api = new PubMed_API();

    foreach ($searches as $search) {
        $params = json_decode($search->search_params, true);

        if (empty($params)) {
            continue;
        }

        // Arama yap
        $results = $pubmed_api->scheduled_search($params);

        if (!is_wp_error($results) && !empty($results['articles'])) {
            // Her makaleyi içe aktar
            foreach ($results['articles'] as $article) {
                // Makalenin daha önce içe aktarılıp aktarılmadığını kontrol et
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}pubmed_articles WHERE pubmed_id = %s",
                    $article['id']
                ));

                if (!$existing) {
                    // Makaleyi içe aktar (basitleştirilmiş)
                    // Tam içe aktarma için ajax_pubmed_import fonksiyonunu kullanın
                }
            }
        }

        // Son çalışma zamanını güncelle
        $wpdb->update(
            $table_name,
            array('last_run' => $current_time),
            array('id' => $search->id),
            array('%s'),
            array('%d')
        );
    }
}

// Cron görevi ekle
if (!wp_next_scheduled('pubmed_process_scheduled_searches')) {
    wp_schedule_event(time(), 'hourly', 'pubmed_process_scheduled_searches');
}
add_action('pubmed_process_scheduled_searches', 'pubmed_process_scheduled_searches');

/**
 * Eklenti deaktivasyonunda cron görevini kaldır
 */
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('pubmed_process_scheduled_searches');
});
