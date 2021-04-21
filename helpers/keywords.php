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


function list_keywords($type, $categories)
{
    $keywords = pods(
        $type,
        [
            'limit' => -1,
            'orderby' => 't.name ASC',
            'where' => 't.is_category = ' . (int) $categories,
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

    return $keywords_filtered;
}


function get_keywords_names($type, $lang)
{
    $keywords = pods(
        $type,
        [
            'limit' => -1,
            'orderby' => $lang === 'en' ? 't.name ASC' : 't.namecz ASC',
            'select' => implode(', ', [
                't.id',
                $lang === 'en' ? 't.name AS name' : 't.namecz AS name'
            ]),

        ]
    );

    $list = [];

    while ($keywords->fetch()) {
        $list[] = [
            'id' => $keywords->display('id'),
            'name' => $keywords->display('name'),
        ];
    }

    return $list;
}


add_action('wp_ajax_keywords_table_data', function () {
    $keywords = list_keywords(test_input($_GET['type']), (int) $_GET['categories']);
    header('Content-Type: application/json');
    echo json_encode($keywords, JSON_UNESCAPED_UNICODE);
    wp_die();
});
