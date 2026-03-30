<?php
/**
 * İlgili makaleler shortcode şablonu
 *
 * @package PubMed_Health_Importer
 */

if (!defined('ABSPATH')) {
    exit;
}

// MeSH terimlerini al
$mesh_terms = get_post_meta(get_the_ID(), 'pubmed_mesh_terms', true);

if (empty($mesh_terms)) {
    return;
}

$args = array(
    'post_type' => 'pubmed_article',
    'posts_per_page' => 5,
    'post__not_in' => array(get_the_ID()),
    'tax_query' => array(
        array(
            'taxonomy' => 'pubmed_tag',
            'field' => 'slug',
            'terms' => $mesh_terms,
        ),
    ),
);

$query = new WP_Query($args);

if ($query->have_posts()) {
    echo '<div class="pubmed-related-articles">';
    echo '<h3>' . __('İlgili Makaleler', 'pubmed-health-importer') . '</h3>';
    echo '<ul>';

    while ($query->have_posts()) {
        $query->the_post();
        echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
    }

    echo '</ul>';
    echo '</div>';
}

wp_reset_postdata();
