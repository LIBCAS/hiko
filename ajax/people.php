<?php

function persons_table_data()
{
    $person_type = test_input($_GET['type']);
    header('Content-Type: application/json');

    if (hiko_cache_exists('list_' . $person_type)) {
        header('Last-Modified: ' . get_gmdate(get_hiko_cache_file('list_' . $person_type)));

        wp_die(
            read_hiko_cache('list_' . $person_type)
        );
    }

    $persons = get_persons_table_data($person_type);

    $json_persons = json_encode(
        $persons,
        JSON_UNESCAPED_UNICODE
    );

    header('Last-Modified: ' . get_gmdate());

    create_hiko_json_cache('list_' . $person_type, $json_persons);

    wp_die($json_persons);
}
add_action('wp_ajax_persons_table_data', 'persons_table_data');


function list_people_simple()
{
    $type = test_input($_GET['type']);

    wp_die(json_encode(
        get_pods_name_and_id($type, true),
        JSON_UNESCAPED_UNICODE
    ));
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

    $alternative_names = json_decode($pod->display('persons_meta'));

    if ($alternative_names && array_key_exists('names', $alternative_names)) {
        $alternative_names = $alternative_names->names;
    } else {
        $alternative_names = [];
    }

    $results['id'] = $pod->display('id');
    $results['name'] = $pod->field('name');
    $results['surname'] = $pod->field('surname');
    $results['forename'] = $pod->field('forename');
    $results['birth_year'] = $pod->field('birth_year');
    $results['death_year'] = $pod->field('death_year');
    $results['emlo'] = $pod->field('emlo');
    $results['note'] = $pod->field('note');
    $results['profession'] = $pod->field('profession');
    $results['nationality'] = $pod->field('nationality');
    $results['gender'] = $pod->field('gender');
    $results['names'] = $alternative_names;
    $results['type'] = $pod->field('type');

    wp_die(json_encode(
        $results,
        JSON_UNESCAPED_UNICODE
    ));
}
add_action('wp_ajax_list_people_single', 'list_people_single');


function count_alternate_name()
{
    global $wpdb;

    $results = [];

    $l_type = test_input($_GET['l_type']);
    $person_id = test_input($_GET['id']);
    $types = get_hiko_post_types($l_type);

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 403);
    }

    $person_meta = pods_field($types['person'], $person_id, 'persons_meta');

    if (!$person_meta) {
        wp_send_json_success([
            'deleted' => [],
        ]);
    }

    $person_meta = json_decode($person_meta);

    if ($person_meta && array_key_exists('names', $person_meta)) {
            $alternative_names = $person_meta->names;
    } else {
        wp_send_json_success([
            'deleted' => [],
        ]);
    }

    $table = $wpdb->prefix . 'pods_' . $types['letter'];

    $person_meta->names = [];

    foreach ($alternative_names as $name) {
        $count = $wpdb->get_var(
            "SELECT COUNT(id) FROM {$table} WHERE authors_meta LIKE '%\"{$name}\"%'"
        );

        if ((int) $count === 0) {
            $results['deleted'][] = $name;
        } else {
            $person_meta->names[] = $name;
        }
    }

    $save = pods_api()->save_pod_item([
        'pod' => $types['person'],
        'data' => [
            'persons_meta' => json_encode($person_meta, JSON_UNESCAPED_UNICODE)
        ],
        'id' => $person_id
    ]);

    $results['save'] = $save;

    wp_send_json_success($results);
}
add_action('wp_ajax_count_alternate_name', 'count_alternate_name');
