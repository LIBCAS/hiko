<?php

function get_entity($type, $id, $professions)
{
    $entity = pods($type, $id);

    if (!$entity->exists()) {
        return [];
    }

    $entity_meta = json_decode($entity->display('persons_meta'));

    $alternative_names = [];

    if ($entity_meta && isset($entity_meta->names)) {
        $alternative_names = $entity_meta->names;
    }

    return [
        'name' => $entity->field('name'),
        'surname' => $entity->field('surname'),
        'forename' => $entity->field('forename'),
        'birth_year' => $entity->field('birth_year'),
        'death_year' => $entity->field('death_year'),
        'viaf' => $entity->field('viaf'),
        'note' => $entity->field('note'),
        'profession_short' => parse_professions($entity->field('profession_short'), $professions),
        'profession_detailed' => parse_professions($entity->field('profession_detailed'), $professions),
        'nationality' => $entity->field('nationality'),
        'gender' => $entity->field('gender'),
        'names' => $alternative_names,
        'type' => $entity->field('type'),
    ];
}


function save_entity($person_type, $action)
{
    $data = test_postdata([
        'birth_year' => 'birth_year',
        'death_year' => 'death_year',
        'viaf' => 'viaf',
        'forename' => 'forename',
        'gender' => 'gender',
        'name' => 'fullname',
        'nationality' => 'nationality',
        'note' => 'note',
        'profession' => 'profession',
        'surname' => 'surname',
        'type' => 'type',
    ]);

    $data['profession_detailed'] = parse_professions_before_save('profession_detailed');
    $data['profession_short'] = parse_professions_before_save('profession_short');

    $save_data = [
        'pod' => $person_type,
        'data' => $data
    ];

    if ($action == 'edit') {
        $save_data['id'] = (int) $_GET['edit'];
    }

    $new_entity = pods_api()->save_pod_item($save_data);

    if (is_wp_error($new_entity)) {
        $_SESSION['warning'] = $new_entity->get_error_message();
    }

    $_SESSION['success'] = 'Uloženo';

    wp_redirect($_SERVER['HTTP_REFERER']);

    die();
}


function parse_professions($professions_list, $all_professions)
{
    if (empty($professions_list)) {
        return '';
    }

    $selected = [];

    foreach (explode(';', $professions_list) as $profession) {
        $meta_index = array_search($profession, array_column($all_professions, 'id'));

        if ($meta_index !== false) {
            $selected[] = $all_professions[$meta_index]['name'];
        }
    }

    return implode(';', $selected);
}


function get_grouped_profession_data($professions, $short)
{
    $result = [];

    foreach ($professions as $profession) {
        if ($profession['palladio'] === $short) {
            $result[] = [
                'value' => $profession['name'],
                'id' => $profession['id'],
            ];
        }
    }

    return $result;
}


function parse_professions_before_save($professions)
{
    if (!isset($_POST[$professions]) || empty($_POST[$professions])) {
        return '';
    }

    $result = [];

    $parsed = json_decode(stripslashes($_POST[$professions]), true);

    foreach ($parsed as $profession) {
        $result[] =  $profession['id'];
    }

    return implode(';', $result);
}


add_action('wp_ajax_count_alternate_name', function () {
    global $wpdb;

    $types = get_hiko_post_types(test_input($_GET['l_type']));

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 403);
    }

    $person_id = (int) $_GET['id'];

    $person_meta = pods_field($types['person'], $person_id, 'persons_meta');

    if (!$person_meta) {
        wp_send_json_success([
            'deleted' => [],
        ]);
    }

    $person_meta = json_decode($person_meta);

    if (!$person_meta || !array_key_exists('names', $person_meta)) {
        wp_send_json_success([
            'deleted' => [],
        ]);
    }

    $table = $wpdb->prefix . 'pods_' . $types['letter'];

    $person_meta->names = [];

    $results = [];

    foreach ($person_meta->names as $name) {
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
});


add_action('wp_ajax_persons_table_data', function () {
    $fields = implode(', ', [
        'letter_author.id AS au',
        'letter_people_mentioned.id AS pm',
        'letter_recipient.id AS re',
        't.birth_year',
        't.death_year',
        't.profession_detailed',
        't.profession_short',
        't.id',
        't.name',
        't.persons_meta',
        't.type',
    ]);

    $persons = pods(
        test_input($_GET['type']),
        [
            'select' => $fields,
            'orderby' => 't.name ASC',
            'limit' => -1,
            'groupby' => 't.id',
        ]
    );

    $persons_filtered = [];

    while ($persons->fetch()) {
        $persons_meta = json_decode($persons->display('persons_meta'));

        $alternative_names = [];
        if ($persons_meta && array_key_exists('names', $persons_meta)) {
            $alternative_names = $persons_meta->names;
        }

        $persons_filtered[] = [
            'id' => $persons->display('id'),
            'name' => $persons->display('name'),
            'birth' => $persons->field('birth_year'),
            'death' => $persons->field('death_year'),
            'profession_short' => $persons->display('profession_short'),
            'profession_detailed' => $persons->display('profession_detailed'),
            'type' => empty($persons->display('type')) ? 'person' : $persons->display('type'),
            'alternatives' => $alternative_names,
            'relationships' => !is_null($persons->display('au')) || !is_null($persons->display('re')) || !is_null($persons->display('pm')),
        ];
    }

    header('Content-Type: application/json');
    header('Last-Modified: ' . get_gmdate());
    echo json_encode($persons_filtered, JSON_UNESCAPED_UNICODE);
    wp_die();
});


add_action('wp_ajax_list_people_simple', function () {
    wp_die(json_encode(
        get_pods_name_and_id(test_input($_GET['type']), true),
        JSON_UNESCAPED_UNICODE
    ));
});
