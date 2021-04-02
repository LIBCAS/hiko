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

        if ($meta_index) {
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
