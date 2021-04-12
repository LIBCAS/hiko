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
    $country = json_decode(stripslashes($_POST['country']), true);

    if (!$country || !isset($country[0])) {
        return $_SESSION['warning'] = 'Chybí povinné údaje';
    }

    $data = test_postdata([
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'name' => 'place',
        'note' => 'note',
    ]);

    $data['country'] = test_input($country[0]['value']);

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
    $countries = json_decode(
        get_ssl_file(get_template_directory_uri() . '/assets/data/countries.json'),
        true
    );
    return array_map(function ($country) {
        return [
            'value' => $country['name'],
        ];
    }, $countries);


}


function list_places($type)
{
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

    return json_encode($places, JSON_UNESCAPED_UNICODE);
}


add_action('wp_ajax_list_places_simple', function () {
    header('Content-Type: application/json');
    wp_die(list_places(test_input($_GET['type'])));
});
