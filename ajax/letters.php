<?php

function list_public_bl_letters_short()
{
    $results = [];
    $index = 0;

    $letters_pods = pods(
        'bl_letter',
        [
            'orderby'=> 't.name ASC',
            'limit' => -1,
            'where'=> "t.status = 'publish'"
        ]
    );

    $index = 0;

    while ($letters_pods->fetch()) {
        $authors = [];
        $recipients = [];
        $authors_related = $letters_pods->field('l_author');
        $recipients_related = $letters_pods->field('recipient');

        if (!empty($authors_related)) {
            foreach ($authors_related as $rel_author) {
                $authors[] = $rel_author['name'];
            }
        }

        if (!empty($recipients_related)) {
            foreach ($recipients_related as $rel_recipient) {
                $recipients[] = $rel_recipient['name'];
            }
        }

        $results[$index]['id'] = $letters_pods->display('id');
        $results[$index]['l_number'] = $letters_pods->field('l_number');
        $results[$index]['day'] = $letters_pods->field('date_day');
        $results[$index]['month'] = $letters_pods->field('date_month');
        $results[$index]['year'] = $letters_pods->field('date_year');
        $results[$index]['author'] = $authors;
        $results[$index]['recipient'] = $recipients;
        $results[$index]['origin'] = get_array_name($letters_pods->field('origin'));
        $results[$index]['dest'] = get_array_name($letters_pods->field('dest'));
        $index++;
    }

    echo json_encode(
        $results,
        JSON_UNESCAPED_UNICODE
    );

    wp_die();
}

add_action('wp_ajax_list_public_bl_letters_short', 'list_public_bl_letters_short');
add_action('wp_ajax_nopriv_list_public_bl_letters_short', 'list_public_bl_letters_short');
