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
        $_SESSION['hiko']['warning'] = $new_entity->get_error_message();
    }

    $_SESSION['hiko']['success'] = 'Uloženo';

    frontend_refresh();
}


function list_entities($type)
{
    $data = pods(
        $type,
        [
            'select' => implode(', ', [
                't.id',
                't.name',
                't.birth_year',
                't.death_year',
            ]),
            'orderby' => 't.name ASC',
            'limit' => -1
        ]
    );

    $entities = [];

    while ($data->fetch()) {
        $entities[] = [
            'id' => $data->display('id'),
            'name' => format_person_name($data->display('name'), $data->field('birth_year'), $data->field('death_year')),
        ];
    }

    return $entities;
}


function format_person_name($name, $birth, $death)
{
    if ($birth != 0 || $death != 0) {
        $name .= empty($birth) ? ' (–' : ' (' . $birth . '–';
        $name .= empty($death) ? ')' : $death . ')';
    }

    return $name;
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


add_action('wp_ajax_regenerate_alternate_name', function () {
    global $wpdb;

    $types = get_hiko_post_types(test_input($_GET['l_type']));

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 403);
    }

    $person_id = (int) $_GET['id'];

    $table = $wpdb->prefix . 'pods_' . $types['letter'];

    $used_names = [];

    $metas = $wpdb->get_col("SELECT authors_meta FROM {$table} WHERE authors_meta LIKE '%\"\id\":\"{$person_id}\"%'");

    foreach ($metas as $meta) {
        $meta = json_decode($meta, true);
        $key = array_search($person_id, array_column($meta, 'id'));

        if ($key !== false && !empty($meta[$key]['marked'])) {
            $used_names[] = $meta[$key]['marked'];
        }
    }

    $used_names = array_unique($used_names);
    sort($used_names);

    $save = pods_api()->save_pod_item([
        'pod' => $types['person'],
        'data' => [
            'persons_meta' => json_encode(['names' => $used_names], JSON_UNESCAPED_UNICODE)
        ],
        'id' => $person_id
    ]);

    $results['save'] = $save;

    wp_send_json_success($results);
});


add_action('wp_ajax_persons_table_data', function () {
    $data_types = get_hiko_post_types($_GET['type']);

    $persons = pods(
        $data_types['person'],
        [
            'groupby' => 't.id',
            'limit' => -1,
            'orderby' => 't.name ASC',
            'select' => implode(', ', [
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
            ]),
        ]
    );

    $professions = get_professions($data_types['profession'], $data_types['default_lang']);

    $persons_filtered = [];

    while ($persons->fetch()) {
        $persons_meta = json_decode($persons->display('persons_meta'));

        $alternative_names = [];
        if ($persons_meta && array_key_exists('names', $persons_meta)) {
            $alternative_names = (array) $persons_meta->names;
        }

        $dates = '';
        $birth = $persons->field('birth_year');
        $death = $persons->field('death_year');
        if ($birth || $death) {
            $dates = $birth ? '(' . $birth . '–' : '(–';
            $dates .= $death ? $death . ')' : ')';
        }

        $persons_filtered[] = [
            'id' => $persons->display('id'),
            'name' => [
                'name' => $persons->display('name'),
                'dates' => $dates,
            ],
            'short' => explode(';', parse_professions($persons->field('profession_short'), $professions)),
            'detailed' => explode(';', parse_professions($persons->field('profession_detailed'), $professions)),
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
    header('Content-Type: application/json');
    wp_die(json_encode(
        list_entities(test_input($_GET['type']), true),
        JSON_UNESCAPED_UNICODE
    ));
});
