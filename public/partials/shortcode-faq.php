<?php
/**
 * FAQ shortcode şablonu
 *
 * @package PubMed_Health_Importer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Mevcut post'un FAQ'sını göster
$faq = get_post_meta(get_the_ID(), 'pubmed_faq', true);

if (empty($faq) || !is_array($faq)) {
    return;
}

echo '<div class="pubmed-faq-shortcode">';
echo '<h3>' . __('Sıkça Sorulan Sorular', 'pubmed-health-importer') . '</h3>';

foreach ($faq as $index => $item) {
    $question = isset($item['question']) ? esc_html($item['question']) : '';
    $answer = isset($item['answer']) ? esc_html($item['answer']) : '';

    if (!empty($question) && !empty($answer)) {
        echo '<details class="pubmed-faq-item">';
        echo '<summary>' . $question . '</summary>';
        echo '<p>' . $answer . '</p>';
        echo '</details>';
    }
}

echo '</div>';
