<?php

function list_letter_history()
{
    $types = get_hiko_post_types(test_input($_GET['l_type']));

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 403);
    }

    $history = get_letter_history($types['letter'], test_input($_GET['l_id']));

    if (!$history) {
        wp_send_json_error('Not found', 404);
    }

    wp_send_json_success($history);
}
add_action('wp_ajax_list_letter_history', 'list_letter_history');


function list_all_letters_short()
{
    $types = get_hiko_post_types(test_input($_GET['type']));

    header('Content-Type: application/json');

    header('Last-Modified: ' . get_gmdate());

    $json_letters = json_encode(
        get_letters_basic_meta_filtered($types, true, true),
        JSON_UNESCAPED_UNICODE
    );

    wp_die($json_letters);
}
add_action('wp_ajax_list_all_letters_short', 'list_all_letters_short');


function public_list_all_letters()
{
    $types = get_hiko_post_types(test_input($_GET['type']));

    $letters = get_letters_basic_meta_filtered($types, false);

    $results = [];

    foreach ($letters as $letter) {
        $results[] = [
            'id' => $letter['ID'],
            'sig' => $letter['signature'],
            'dd' => $letter['date_day'],
            'mm' => $letter['date_month'],
            'yy' => $letter['date_year'],
            'aut' => $letter['author'],
            'rec' => $letter['recipient'],
            'ori' => $letter['origin'],
            'des' => $letter['dest'],
        ];
    }

    header('Content-Type: application/json');

    wp_die(json_encode($results, JSON_UNESCAPED_UNICODE));
}
add_action('wp_ajax_nopriv_public_list_all_letters', 'public_list_all_letters');
add_action('wp_ajax_public_list_all_letters', 'public_list_all_letters');
