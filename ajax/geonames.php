<?php

function get_geocities_latlng()
{
    if (!array_key_exists('query', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $query = test_input($_GET['query']);
    $api_url = "http://api.geonames.org/searchJSON?maxRows=10&username=jarka&q={$query}";
    $geo_data = json_decode(file_get_contents($api_url));

    if (count($geo_data->geonames) < 1) {
        wp_send_json_error('Not found', 404);
    }

    $result = [];
    $index = 0;

    foreach ($geo_data->geonames as $g) {
        $result[$index] = [
            'adminName' => $g->adminName1,
            'country' => $g->countryName,
            'lat' => $g->lat,
            'lng' => $g->lng,
            'name' => $g->name,
        ];
        $index++;
    }

    wp_send_json_success($result);
}
add_action('wp_ajax_get_geocities_latlng', 'get_geocities_latlng');
