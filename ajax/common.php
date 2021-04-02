<?php

add_action('wp_ajax_delete_hiko_pod', function () {
    $data = file_get_contents('php://input');
    $data = json_decode(mb_convert_encoding($data, 'UTF-8'));

    $type = test_input($data->pod_type);
    $types = get_hiko_post_types(test_input($data->pod_name));

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 404);
    }

    $pod = pods($types[$type], (int) $data->id);

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    if ($type == 'letter') {
        $images = $pod->field('images');

        foreach ($images as $img) {
            wp_delete_attachment($img['ID'], true);
        }
    }

    $result = $pod->delete();

    if ($result == 1) {
        wp_send_json_success($result);
    }

    wp_send_json_error('Error', 500);
});
