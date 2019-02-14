<?php

function list_places_simple()
{
    $type = test_input($_GET['type']);
    $place_pods = pods(
        $type,
        [
            'orderby'=> 't.name ASC',
            'limit' => -1
        ]
    );
    $places = [];
    $index = 0;

    while ($place_pods->fetch()) {
        $places[$index]['id'] = $place_pods->display('id');
        $places[$index]['name'] = $place_pods->display('name');
        $index++;
    }

    echo json_encode(
        $places,
        JSON_UNESCAPED_UNICODE
    );
    wp_die();
}
add_action('wp_ajax_list_places_simple', 'list_places_simple');



function list_place_single()
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

    $results['id'] = $pod->display('id');
    $results['name'] = $pod->field('name');
    $results['country'] = $pod->field('country');
    $results['note'] = $pod->display('note');
    $results['latitude'] = $pod->display('latitude');
    $results['longitude'] = $pod->display('longitude');

    echo json_encode(
        $results,
        JSON_UNESCAPED_UNICODE
    );

    wp_die();
}
add_action('wp_ajax_list_place_single', 'list_place_single');


function delete_place()
{
    if (!array_key_exists('pods_id', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $id = test_input($_GET['pods_id']);
    $type = test_input($_GET['type']);
    $types = get_hiko_post_types($type);

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 404);
    }

    $pod = pods($types['place'], $id);
    $result = $pod->delete();

    wp_die($result);
}

add_action('wp_ajax_delete_place', 'delete_place');
