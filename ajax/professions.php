<?php

function insert_profession()
{
    if (!is_in_editor_role()) {
        wp_send_json_error('Not allowed', 403);
    }

    $data = file_get_contents('php://input');
    $data = mb_convert_encoding($data, 'UTF-8');
    $data = json_decode($data);

    $action = $data->action;
    $id = $data->id;
    $type = $data->type;

    $data = [
        'name' => test_input($data->nameen),
        'namecz' => test_input($data->namecz),
        'palladio' => (bool) $data->palladio ? 1 : 0,
    ];

    if ($action == 'add') {
        $newId = pods_api()->save_pod_item([
            'pod' => $type,
            'data' => $data
        ]);

        wp_send_json_success(array_merge($data, ['id' => $newId]));
    } elseif ($action == 'edit') {
        pods_api()->save_pod_item([
            'pod' => $type,
            'data' => $data,
            'id' => $id
        ]);

        wp_send_json_success(array_merge($data, ['id' => $id]));
    }

    wp_send_json_error();
}
add_action('wp_ajax_insert_profession', 'insert_profession');


function get_professions_table_data($professions_type = false, $ajax = true)
{
    if (!$professions_type) {
        $professions_type = test_input($_GET['type']);
    }

    $fields = [
        't.id',
        't.name AS name',
        't.namecz',
        't.palladio',
    ];

    $fields = implode(', ', $fields);

    $professions = pods(
        $professions_type,
        [
            'select' => $fields,
            'orderby' => 't.name ASC',
            'limit' => -1
        ]
    );

    $professions_filtered = [];
    $index = 0;
    while ($professions->fetch()) {
        $professions_filtered[$index]['id'] = $professions->display('id');
        $professions_filtered[$index]['name'] = $professions->display('name');
        $professions_filtered[$index]['namecz'] = $professions->display('namecz');
        $professions_filtered[$index]['palladio'] = $professions->field('palladio') == 0 ? false : true;
        $index++;
    }


    $professions_filtered = json_encode($professions_filtered, JSON_UNESCAPED_UNICODE);

    if ($ajax) {
        header('Content-Type: application/json');
        wp_die($professions_filtered);
    }

    return $professions_filtered;
}
add_action('wp_ajax_professions_table_data', 'get_professions_table_data');


function get_professions_list($professions_type = false, $ajax = true)
{
    if (!$professions_type) {
        $professions_type = test_input($_GET['type']);
    }

    $fields = implode(', ', [
        't.id',
        't.name AS name',
    ]);

    $professions = pods(
        $professions_type,
        [
            'select' => $fields,
            'orderby' => 't.name ASC',
            'limit' => -1
        ]
    );

    $professions_filtered = [];

    while ($professions->fetch()) {
        $professions_filtered[$professions->display('id')] = $professions->display('name');
    }

    $professions_filtered = json_encode($professions_filtered, JSON_UNESCAPED_UNICODE);

    if ($ajax) {
        header('Content-Type: application/json');
        wp_die($professions_filtered);
    }

    return $professions_filtered;
}
add_action('wp_ajax_professions_list', 'get_professions_list');
