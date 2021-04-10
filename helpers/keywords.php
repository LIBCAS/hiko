<?php

add_action('wp_ajax_insert_keyword', function () {
    if (!is_in_editor_role()) {
        wp_send_json_error('Not allowed', 403);
    }

    $data = decode_php_input();

    $keyword_data = [
        'name' => test_input($data->nameen),
        'namecz' => test_input($data->namecz),
        'is_category' => isset($data->is_category) ? (int) $data->is_category : 0,
        'categories' => isset($data->categories) ? test_input($data->categories) : null,
    ];

    $save_data = [
        'pod' => test_input($data->type),
        'data' => $keyword_data
    ];

    if ($data->action == 'edit') {
        $save_data['id'] = $data->id;
    }

    $saved_id = pods_api()->save_pod_item($save_data);

    wp_send_json_success(array_merge($keyword_data, ['id' => $saved_id]));
});


function list_keywords($type = false, $categories = false, $ajax = true)
{
    $is_category = $categories ? (int) $categories : (int) $_GET['categories']; // not working directly in pods()

    $keywords = pods(
        $type ? $type : test_input($_GET['type']),
        [
            'limit' => -1,
            'orderby' => 't.name ASC',
            'where' => 't.is_category = ' . $is_category,
            'select' => implode(', ', [
                't.id',
                't.name AS name',
                't.namecz',
                't.categories',
            ]),

        ]
    );

    $keywords_filtered = [];

    while ($keywords->fetch()) {
        $keywords_filtered[] = [
            'id' => $keywords->display('id'),
            'name' => $keywords->display('name'),
            'namecz' => $keywords->display('namecz'),
            'categories' => $keywords->display('categories'),
        ];
    }

    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode($keywords_filtered, JSON_UNESCAPED_UNICODE);
        die();
    }

    return $keywords_filtered;
}
add_action('wp_ajax_keywords_table_data', 'list_keywords');
