<?php
/**
 * Popüler makaleler shortcode şablonu
 *
 * @package PubMed_Health_Importer
 */

if (!defined('ABSPATH')) {
    exit;
}

$args = array(
    'post_type' => 'pubmed_article',
    'posts_per_page' => $atts['count'],
    'orderby' => 'meta_value_num',
    'meta_key' => 'pubmed_views',
    'order' => 'DESC',
);

$query = new WP_Query($args);

if ($query->have_posts()) {
    echo '<div class="pubmed-popular-articles">';
    echo '<h3>' . __('Popüler Makaleler', 'pubmed-health-importer') . '</h3>';
    echo '<ul>';

    while ($query->have_posts()) {
        $query->the_post();
        echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
    }

    echo '</ul>';
    echo '</div>';
} else {
    echo '<p>' . __('Henüz makale yok.', 'pubmed-health-importer') . '</p>';
}

wp_reset_postdata();
