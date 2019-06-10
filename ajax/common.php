<?php

function delete_hiko_pod()
{
    $data = file_get_contents('php://input');
    $data = mb_convert_encoding($data, 'UTF-8');
    $data = json_decode($data);

    $id = test_input($data->id);
    $type = test_input($data->pod_type);
    $name = test_input($data->pod_name);

    $types = get_hiko_post_types($name);

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 404);
    }

    $pod = pods($types[$type], $id);

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    if ($type == 'letter') {
        $images = $pod->field('images');
        foreach ($images as $img) {
            wp_delete_attachment($img['ID'], true);
        }
        delete_hiko_cache('list_' . $types['path']);
        delete_hiko_cache('list_' . $types['person']);
    }

    if ($type == 'person') {
        delete_hiko_cache('list_' . $types['person']);
    }

    $result = $pod->delete();

    if ($result == 1) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error('Error', 500);
    }
}

add_action('wp_ajax_delete_hiko_pod', 'delete_hiko_pod');
