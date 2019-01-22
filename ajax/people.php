<?php

function list_bl_people_simple()
{
    echo json_encode(
        get_persons_names('bl_person'),
        JSON_UNESCAPED_UNICODE
    );
    wp_die();
}
add_action('wp_ajax_list_bl_people_simple', 'list_bl_people_simple');



function list_bl_people_single()
{
    $results = [];

    if (!array_key_exists('pods_id', $_GET)) {
        echo '404';
        wp_die();
    }

    $pod = pods('bl_person', $_GET['pods_id']);

    $results['id'] = $pod->display('id');
    $results['name'] = $pod->field('name');
    $results['surname'] = $pod->field('surname');
    $results['forename'] = $pod->field('forename');
    $results['birth_year'] = $pod->field('birth_year');
    $results['death_year'] = $pod->field('death_year');
    $results['emlo'] = $pod->field('emlo');

    echo json_encode(
        $results,
        JSON_UNESCAPED_UNICODE
    );

    wp_die();
}

add_action('wp_ajax_list_bl_people_single', 'list_bl_people_single');
