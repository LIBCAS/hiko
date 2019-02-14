<?php

function insert_location_data()
{
    if (!is_in_editor_role()) {
        wp_send_json_error('Not allowed', 403);
    }

    $data = file_get_contents('php://input');
    $data = mb_convert_encoding($data, 'HTML-ENTITIES', "UTF-8");
    $data = json_decode($data);

    $type = $data->type;
    $item = $data->item;
    $action = $data->action;

    $data = [
        'loc_name' => test_input($item),
        'loc_type' => test_input($type),
    ];

    if ($action == 'add') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => 'location',
            'data' => $data
        ]);
        wp_send_json_success($new_pod);
    }
}
add_action('wp_ajax_insert_location_data', 'insert_location_data');
