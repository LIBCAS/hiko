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


function list_public_bl_letters_single()
{
    $results = [];
    $authors = [];
    $recipients = [];
    $people_mentioned = [];

    if (!array_key_exists('pods_id', $_GET)) {
        echo '404';
        wp_die();
    }

    $pod = pods('bl_letter', $_GET['pods_id']);

    if ($pod->field('status') != 'publish') {
        echo '403';
        wp_die();
    }

    $authors_related = $pod->field('l_author');
    $recipients_related = $pod->field('recipient');
    $people_mentioned_related = $pod->field('people_mentioned');

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

    if (!empty($people_mentioned_related)) {
        foreach ($people_mentioned_related as $rel_pm) {
            $people_mentioned[] = $rel_pm['name'];
        }
    }

    $results['l_number'] = $pod->field('l_number');
    $results['date_year'] = $pod->field('date_year');
    $results['date_month'] = $pod->field('date_month');
    $results['date_day'] = $pod->field('date_day');
    $results['date_marked'] = $pod->field('date_marked');
    $results['date_uncertain'] = $pod->field('date_uncertain');
    $results['l_author'] = $authors;
    $results['l_author_marked'] = $pod->field('l_author_marked');
    $results['author_uncertain'] = $pod->field('author_uncertain');
    $results['author_inferred'] = $pod->field('author_inferred');
    $results['recipient'] = $recipients;
    $results['recipient_marked'] = $pod->field('recipient_marked');
    $results['recipient_inferred'] = $pod->field('recipient_inferred');
    $results['recipient_uncertain'] = $pod->field('recipient_uncertain');
    $results['recipient_notes'] = $pod->field('recipient_notes');
    $results['origin'] = $pod->field('origin');
    $results['origin_marked'] = $pod->field('origin_marked');
    $results['origin_inferred'] = $pod->field('origin_inferred');
    $results['origin_uncertain'] = $pod->field('origin_uncertain');
    $results['dest'] = $pod->field('dest');
    $results['dest_marked'] = $pod->field('dest_marked');
    $results['dest_uncertain'] = $pod->field('dest_uncertain');
    $results['dest_inferred'] = $pod->field('dest_inferred');
    $results['languages'] = $pod->field('languages');
    $results['keywords'] = $pod->field('keywords');
    $results['abstract'] = $pod->field('abstract');
    $results['incipit'] = $pod->field('incipit');
    $results['explicit'] = $pod->field('explicit');
    $results['people_mentioned'] = $people_mentioned;
    $results['people_mentioned_notes'] = $pod->field('people_mentioned_notes');
    $results['notes_public'] = $pod->field('notes_public');
    $results['rel_rec_name'] = $pod->field('rel_rec_name');
    $results['rel_rec_url'] = $pod->field('rel_rec_url');
    $results['ms_manifestation'] = $pod->field('ms_manifestation');
    $results['repository'] = $pod->field('repository');
    $results['name'] = $pod->field('name');

    echo json_encode(
        $results,
        JSON_UNESCAPED_UNICODE
    );

    wp_die();
}

add_action('wp_ajax_list_public_bl_letters_single', 'list_public_bl_letters_single');
add_action('wp_ajax_nopriv_list_public_bl_letters_single', 'list_public_bl_letters_single');



function delete_bl_letter()
{
    if (!array_key_exists('pods_id', $_GET)) {
        echo '404';
        wp_die();
    }

    $user = wp_get_current_user();
    $role = (array) $user->roles;

    if (!in_array('blekastad_editor', $role) && !in_array('administrator', $role)) {
        echo '403';
        wp_die();
    }

    $pod = pods('bl_letter', $_GET['pods_id']);
    $result = $pod->delete();
    echo $result;

    wp_die();
}

add_action('wp_ajax_delete_bl_letter', 'delete_bl_letter');
