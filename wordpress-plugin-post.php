<?php
/*
Plugin Name: Wordpress Post API
Description: Adds an endpoint to create new posts through an external POST request with API key authentication.
Version: 1.1
Author: Diego Fuentes
*/

// Genera la API key en el momento adecuado
add_action('plugins_loaded', 'generate_api_key');

function generate_api_key() {
    define('MY_PLUGIN_API_KEY', "COMO.QUIERO123");
}

// Register the custom endpoint
add_action('rest_api_init', 'register_posts_endpoint');

function register_posts_endpoint() {
    register_rest_route('posts/v1', 'crear', array(
        'methods' => 'POST',
        'callback' => 'create_new_post',
        'permission_callback' => 'validate_api_key', // Custom permission callback
    ));
}

// Custom permission callback to validate API key
function validate_api_key($request) {
    $api_key = $request->get_header('X-Api-Key'); // Get the API key from the request header

    if ($api_key === MY_PLUGIN_API_KEY) {
        return true; // API key is valid, allow the request
    } else {
        return false; // API key is invalid, deny the request
    }
}

// Callback to create a new post
function create_new_post($request) {
    $params = $request->get_params();

    // Verifica los parámetros requeridos
    if (empty($params['titulo']) || empty($params['contenido']) || empty($params['autor'])) {
        return new WP_Error('parametros_incompletos', 'Faltan parámetros requeridos.', array('status' => 400));
    }

    // Get the author ID based on the provided author login or email
    $author = get_user_by('login', $params['autor']);
    if (!$author) {
        $author = get_user_by('email', $params['autor']);
    }

    // Crea el post
    $post_data = array(
        'post_title' => sanitize_text_field($params['titulo']),
        'post_content' => wp_kses_post($params['contenido']),
        'post_status' => 'publish',
        'post_type' => 'post',
        'post_author' => $author->ID, // Set the author ID
        'tags_input' => explode(',', $params['etiquetas']), // Etiquetas separadas por coma (tag1,tag2,tag3)
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id) {
        return array('post_id' => $post_id);
    } else {
        return new WP_Error('error_creacion_post', 'Error al crear el post.', array('status' => 500));
    }
}
