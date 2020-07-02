<?php

function list_places_simple($type = false, $ajax = true)
{
    if (!$type) {
        $type = test_input($_GET['type']);
    }

    $fields = implode(', ', [
        't.id',
        't.name',
        't.latitude',
        't.longitude',
    ]);

    $pod = pods(
        $type,
        [
            'limit' => -1,
            'orderby' => 't.name ASC',
            'select' => $fields,
        ]
    );

    $places = [];

    while ($pod->fetch()) {
        $name = $pod->display('name');

        if ($pod->display('latitude') && $pod->display('longitude')) {
            $name .= ' (' . $pod->display('latitude') . ', ' . $pod->display('longitude') . ')';
        }

        $places[] = [
            'id'=> $pod->display('id'),
            'name'=> $name,
        ];
    }

    if (!$pod->data()) {
        $places[] = [
            'id' => '',
            'name' => '',
        ];
    }

    $places = json_encode(
        $places,
        JSON_UNESCAPED_UNICODE
    );

    if ($ajax) {
        header('Content-Type: application/json');
        wp_die($places);
    }

    return $places;
}
add_action('wp_ajax_list_places_simple', 'list_places_simple');


function list_place_single()
{
    $results = [];

    if (!array_key_exists('pods_id', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $pod = pods(
        test_input($_GET['type']),
        test_input($_GET['pods_id'])
    );

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    $results['country'] = $pod->field('country');
    $results['id'] = $pod->display('id');
    $results['latitude'] = $pod->display('latitude');
    $results['longitude'] = $pod->display('longitude');
    $results['name'] = $pod->field('name');
    $results['note'] = $pod->display('note');

    wp_die(json_encode(
        $results,
        JSON_UNESCAPED_UNICODE
    ));
}
add_action('wp_ajax_list_place_single', 'list_place_single');
