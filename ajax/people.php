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

    if (!$pod->exists()) {
        echo '404';
        wp_die();
    }

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


function delete_bl_person()
{
    if (!array_key_exists('pods_id', $_GET)) {
        echo '404';
        wp_die();
    }

    $user = wp_get_current_user();
    $role = (array) $user->roles;

    if (!in_array('blekastad_editor', $role) && !in_array('administrator', $role)) {
        echo '403';
        wp_die();
    }

    $pod = pods('bl_person', $_GET['pods_id']);
    $result = $pod->delete();
    echo $result;

    wp_die();
}

add_action('wp_ajax_delete_bl_person', 'delete_bl_person');
