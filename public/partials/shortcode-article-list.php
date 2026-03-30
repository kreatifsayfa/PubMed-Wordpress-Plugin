<?php
/**
 * Makale listesi shortcode şablonu
 *
 * @package PubMed_Health_Importer
 */

if (!defined('ABSPATH')) {
    exit;
}

$args = array(
    'post_type' => 'pubmed_article',
    'posts_per_page' => $atts['count'],
    'orderby' => $atts['orderby'],
    'order' => $atts['order'],
);

if (!empty($atts['category'])) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'pubmed_category',
            'field' => 'slug',
            'terms' => $atts['category'],
        ),
    );
}

if (!empty($atts['tag'])) {
    $args['tag_slug__in'] = explode(',', $atts['tag']);
}

$query = new WP_Query($args);

if ($query->have_posts()) {
    echo '<div class="pubmed-article-list">';
    echo '<ul class="pubmed-articles">';

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
