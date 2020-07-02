<?php

function list_places_simple()
{
    $places = json_encode(
        get_pods_name_and_id(test_input($_GET['type'])),
        JSON_UNESCAPED_UNICODE
    );

    wp_die($places);
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
