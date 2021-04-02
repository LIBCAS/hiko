<?php

add_action('wp_ajax_insert_location_data', function () {
    if (!is_in_editor_role()) {
        wp_send_json_error('Not allowed', 403);
    }

    $data = file_get_contents('php://input');
    $data = json_decode(mb_convert_encoding($data, 'UTF-8'));

    $save_data = [
        'pod' => 'location',
        'data' => [
            'loc_type' => test_input($data->type),
            'name' => test_input($data->item),
        ]
    ];

    if ($data->action === 'edit') {
        $save_data['id'] = $data->id;
    }

    pods_api()->save_pod_item($save_data);

    wp_send_json_success();
});


add_action('wp_ajax_delete_location_data', function () {
    if (!is_in_editor_role()) {
        wp_send_json_error('Not allowed', 403);
    }

    $data = file_get_contents('php://input');
    $data = json_decode(mb_convert_encoding($data, 'UTF-8'));
    $pod = pods('location', $data->id);

    wp_send_json_success($pod->delete());
});


add_action('wp_ajax_list_locations', function () {
    if (!is_in_editor_role()) {
        wp_send_json_error('Not allowed', 403);
    }

    $pod = pods(
        'location',
        [
            'orderby' => 't.name ASC',
            'limit' => -1,
        ]
    );

    $locations = [];

    while ($pod->fetch()) {
        $locations[] = [
            'id' => $pod->display('id'),
            'name' => $pod->display('name'),
            'type' => $pod->display('loc_type'),
        ];
    }

    wp_send_json_success($locations);
});
