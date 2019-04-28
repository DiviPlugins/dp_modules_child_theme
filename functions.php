<?php

function divi_child_theme_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}

add_action('wp_enqueue_scripts', 'divi_child_theme_enqueue_styles');

/*
 * Allow upload of .json files.
 */

function custom_myme_types($mime_types) {
    $mime_types['json'] = 'application/json';
    return $mime_types;
}

add_filter('upload_mimes', 'custom_myme_types', 1, 1);

/*
 * Custom Modules API endpoints
 */

function api_endpoint_for_modules_data() {
    register_rest_route('divi_plugins/v1', '/modules', array(
        array('method' => 'GET', 'callback' => 'get_custom_modules_info'),
            //array('method' => 'POST', 'callback' => 'add_new_module')
    ));
}

function get_custom_modules_info() {
    $all_modules_info = array();
    $query = new WP_Query(array(
        'post_type' => "download",
        'post_status' => "publish",
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => "download_category",
                'terms' => array(2, 3),
                'operator' => "IN"
            )
        )
    ));
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $data['id'] = get_the_ID();
            $file_info = edd_get_download_files($data['id']);
            $data['name'] = get_the_title();
            $data['thumbnail'] = get_the_post_thumbnail_url($data['id'], "medium");
            $data['description'] = get_the_excerpt();
            $data['link'] = get_the_permalink();
            $data['category'] = has_term(2, "download_category") ? 'module' : 'user-module';
            if (is_array($file_info)) {
                $file_url = $file_info[1]["file"];
                if (!empty($file_url)) {
                    $data['file_content'] = file_get_contents($file_url);
                }
                $all_modules_info[$data['id']] = $data;
            }
        }
        $all_modules_info['api_query_errors'] = "OK";
    } else {
        $all_modules_info['api_query_errors'] = __('There are no custom modules published at the moment.');
    }
    wp_reset_postdata();
    $response = new WP_REST_Response($all_modules_info, 200);
    return $response;
}

function add_new_module($request) {
    /* $posts = get_posts($args);
      if (empty($posts)) {
      return new WP_Error('empty_category', 'there is no post in this category', array('status' => 404));
      } */
    $response = new WP_REST_Response("OK", 200);
    return $response;
}

add_action('rest_api_init', 'api_endpoint_for_modules_data');
