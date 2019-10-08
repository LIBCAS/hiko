<?php

function list_keywords_simple()
{
    $type = test_input($_GET['type']);

    wp_die(json_encode(
        get_pods_name_and_id($type),
        JSON_UNESCAPED_UNICODE
    ));
}
add_action('wp_ajax_list_keywords_simple', 'list_keywords_simple');


function list_keyword_single()
{
    $results = [];

    if (!array_key_exists('pods_id', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $id = test_input($_GET['pods_id']);
    $type = test_input($_GET['type']);

    $pod = pods($type, $id);

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    $results['name'] = $pod->field('name');

    wp_die(json_encode(
        $results,
        JSON_UNESCAPED_UNICODE
    ));
}
add_action('wp_ajax_list_keyword_single', 'list_keyword_single');


function insert_keyword()
{
    if (!is_in_editor_role()) {
        wp_send_json_error('Not allowed', 403);
    }

    $data = file_get_contents('php://input');
    $data = mb_convert_encoding($data, 'UTF-8');
    $data = json_decode($data);

    $action = $data->action;
    $id = $data->id;
    $item = $data->item;
    $type = $data->type;

    $data = [
        'name' => test_input($item),
    ];

    if ($action == 'add') {
        pods_api()->save_pod_item([
            'pod' => $type,
            'data' => $data
        ]);

        wp_send_json_success();
    } elseif ($action == 'edit') {
        pods_api()->save_pod_item([
            'pod' => $type,
            'data' => $data,
            'id' => $id
        ]);

        wp_send_json_success();
    }

    wp_send_json_error();
}
add_action('wp_ajax_insert_keyword', 'insert_keyword');


function get_keywords_table_data()
{
    $keyword_type = test_input($_GET['type']);

    $fields = [
        't.id',
        't.name AS name',
    ];

    $fields = implode(', ', $fields);

    $keywords = pods(
        $keyword_type,
        [
            'select' => $fields,
            'orderby' => 't.name ASC',
            'limit' => -1
        ]
    );

    $keywords_filtered = [];
    $index = 0;
    while ($keywords->fetch()) {
        $keywords_filtered[$index]['id'] = $keywords->display('id');
        $keywords_filtered[$index]['name'] = $keywords->display('name');
        $index++;
    }

    echo json_encode(
        $keywords_filtered,
        JSON_UNESCAPED_UNICODE
    );

    wp_die();
}
add_action('wp_ajax_keywords_table_data', 'get_keywords_table_data');

