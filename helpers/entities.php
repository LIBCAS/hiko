<?php

function get_entity($type, $id)
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
        'profession_short' => parse_professions($entity->field('profession_short')),
        'profession_detailed' => parse_professions($entity->field('profession_detailed')),
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
        'profession_detailed' => 'profession_detailed',
        'profession_short' => 'profession_short',
        'surname' => 'surname',
        'type' => 'type',
    ]);

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
}


function parse_professions($professions_list)
{
    if (empty($professions_list)) {
        return [];
    }

    return explode(';', $professions_list);
}
