<?php

function list_people_simple()
{
    $type = test_input($_GET['type']);
    echo json_encode(
        get_pods_name_and_id($type),
        JSON_UNESCAPED_UNICODE
    );
    wp_die();
}
add_action('wp_ajax_list_people_simple', 'list_people_simple');



function list_people_single()
{
    $results = [];

    if (!array_key_exists('pods_id', $_GET)) {
        wp_send_json_error('Not found', 404);
    }
    $type = test_input($_GET['type']);
    $id = test_input($_GET['pods_id']);
    $pod = pods($type, $id);

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    $results['id'] = $pod->display('id');
    $results['name'] = $pod->field('name');
    $results['surname'] = $pod->field('surname');
    $results['forename'] = $pod->field('forename');
    $results['birth_year'] = $pod->field('birth_year');
    $results['death_year'] = $pod->field('death_year');
    $results['emlo'] = $pod->field('emlo');
    $results['note'] = $pod->field('note');

    echo json_encode(
        $results,
        JSON_UNESCAPED_UNICODE
    );

    wp_die();
}

add_action('wp_ajax_list_people_single', 'list_people_single');
