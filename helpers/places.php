<?php

function get_place($type, $id)
{
    $place = pods($type, (int) $id);

    if (!$place->exists()) {
        return [];
    }

    return [
        'country' => $place->field('country'),
        'id' => $place->display('id'),
        'latitude' => $place->display('latitude'),
        'longitude' => $place->display('longitude'),
        'name' => $place->field('name'),
        'note' => $place->display('note'),
    ];
}


function save_place($place_type, $action)
{
    $data = test_postdata([
        'country' => 'country',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'name' => 'place',
        'note' => 'note',
    ]);

    if (empty($data['country']) || empty($data['name'])) {
        return $_SESSION['warning'] = 'Chybí povinné údaje';
    }

    $save = [
        'pod' => $place_type,
        'data' => $data
    ];

    if ($action == 'edit') {
        $save['id'] = (int) $_GET['edit'];
    }

    $new_place = pods_api()->save_pod_item($save);

    if (is_wp_error($new_place)) {
        $_SESSION['warning'] = $new_place->get_error_message();
    }

    $_SESSION['success'] = 'Uloženo';

    wp_redirect($_SERVER['HTTP_REFERER']);

    die();
}


function get_countries()
{
    return json_decode(
        get_ssl_file(get_template_directory_uri() . '/assets/data/countries.json'),
        true
    );
}


function list_places($type = false, $ajax = true)
{
    $type = $type ? $type : test_input($_GET['type']);

    $pod = pods(
        $type,
        [
            'limit' => -1,
            'orderby' => 't.name ASC',
            'select' => implode(', ', [
                't.id',
                't.name',
                't.latitude',
                't.longitude',
            ]),
        ]
    );

    $places = [];

    while ($pod->fetch()) {
        $name = $pod->display('name');

        if ($pod->display('latitude') && $pod->display('longitude')) {
            $name .= ' (' . $pod->display('latitude') . ', ' . $pod->display('longitude') . ')';
        }

        $places[] = [
            'id'=> $pod->display('id'),
            'name'=> $name,
        ];
    }

    if (!$pod->data()) {
        $places[] = [ 'id' => '', 'name' => '', ];
    }

    $places = json_encode($places, JSON_UNESCAPED_UNICODE);

    if ($ajax) {
        header('Content-Type: application/json');
        wp_die($places);
    }

    return $places;
}
add_action('wp_ajax_list_places_simple', 'list_places');
