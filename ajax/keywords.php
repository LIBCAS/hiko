<?php

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
    $type = $data->type;

    $data = [
        'name' => test_input($data->nameen),
        'namecz' => test_input($data->namecz),
        'category' => (bool) $data->category ? 1 : 0,
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
add_action('wp_ajax_insert_keyword', 'insert_keyword');


function get_keywords_table_data()
{
    $keyword_type = test_input($_GET['type']);

    $fields = [
        't.id',
        't.name AS name',
        't.namecz',
        't.category',
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
        $keywords_filtered[$index]['namecz'] = $keywords->display('namecz');
        $keywords_filtered[$index]['category'] = $keywords->field('category') == 0 ? false : true;
        $index++;
    }

    echo json_encode(
        $keywords_filtered,
        JSON_UNESCAPED_UNICODE
    );

    wp_die();
}
add_action('wp_ajax_keywords_table_data', 'get_keywords_table_data');
