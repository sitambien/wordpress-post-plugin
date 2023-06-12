<?php
/*
Plugin Name: Wordpress Post API
Description: Endpoint para crear nuevas entradas por una solicitud POST externa.
Version: 1.2
Author: Diego Fuentes
*/

// Genera la API key
add_action('plugins_loaded', 'generate_api_key');

function generate_api_key() {
    define('MY_PLUGIN_API_KEY', "COMO.QUIERO123");
}

// Registro del endpoint
add_action('rest_api_init', 'register_posts_endpoint');

function register_posts_endpoint() {
    register_rest_route('posts/v1', 'crear', array(
        'methods' => 'POST',
        'callback' => 'create_new_post',
        'permission_callback' => 'validate_api_key', // permisos
    ));
}

// Callback para validar la clave de la API
function validate_api_key($request) {
    $api_key = $request->get_header('X-Api-Key'); // toma la API key del header

    if ($api_key === MY_PLUGIN_API_KEY) {
        return true;
    } else {
        return false;
    }
}

// Callback para crear la entrada
function create_new_post($request) {
    $params = $request->get_params();

    // Verifica los parámetros
    if (empty($params['titulo']) || empty($params['contenido']) || empty($params['autor'])) {
        return new WP_Error('parametros_incompletos', 'Faltan parámetros requeridos.', array('status' => 400));
    }

    // Autor ID
    $author = get_user_by('login', $params['autor']);
    if (!$author) {
        $author = get_user_by('email', $params['autor']);
    }

    // Postea la entrada
    $post_data = array(
        'post_title' => sanitize_text_field($params['titulo']),
        'post_content' => wp_kses_post($params['contenido']),
        'post_status' => 'publish',
        'post_type' => 'post',
        'post_author' => $author->ID, // author ID
        'tags_input' => explode(',', $params['etiquetas']), // Etiquetas separadas por coma
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id) {
        return array('post_id' => $post_id);
    } else {
        return new WP_Error('error_creacion_post', 'Error al crear el post.', array('status' => 500));
    }
}
