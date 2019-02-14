<?php

function insert_location_data()
{
    if (!is_in_editor_role()) {
        wp_send_json_error('Not allowed', 403);
    }

    $data = file_get_contents('php://input');
    $data = mb_convert_encoding($data, 'HTML-ENTITIES', "UTF-8");
    $data = htmlspecialchars_decode($data);

    $data = json_decode($data);

    $type = $data->type;
    $item = $data->item;
    $action = $data->action;

    $data = [
        'name' => test_input($item),
        'loc_type' => test_input($type),
    ];

    if ($action == 'add') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => 'location',
            'data' => $data
        ]);

        wp_send_json_success();
    }
}
add_action('wp_ajax_insert_location_data', 'insert_location_data');



function list_locations()
{
    if (!is_in_editor_role()) {
        wp_send_json_error('Not allowed', 403);
    }

    $index = 0;
    $locations = [];

    $pod = pods(
        'location',
        [
            'orderby'=> 't.name ASC',
            'limit' => -1,
        ]
    );
    while ($pod->fetch()) {
        $locations[$index]['id'] = $pod->display('id');
        $locations[$index]['name'] = $pod->display('name');
        $locations[$index]['type'] = $pod->display('loc_type');
        $index++;
    }

    wp_send_json_success($locations);
}
add_action('wp_ajax_list_locations', 'list_locations');
