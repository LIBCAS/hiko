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


function list_public_letters_single()
{
    $results = [];
    $authors = [];
    $recipients = [];
    $people_mentioned = [];
    $origins = [];
    $destinations = [];
    $keywords = [];
    $l_type = test_input($_GET['l_type']);
    $lang = test_input($_GET['lang']);

    if (!array_key_exists('pods_id', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $pod = pods($l_type, $_GET['pods_id']);

    if ($pod->field('status') != 'publish' && !is_user_logged_in()) {
        wp_send_json_error('Not allowed', 403);
    }

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    $authors_related = $pod->field('l_author');
    $recipients_related = $pod->field('recipient');
    $people_mentioned_related = $pod->field('people_mentioned');
    $origins_related = $pod->field('origin');
    $dests_related = $pod->field('dest');
    $keywords_related = $pod->field('keywords');

    if (!empty($authors_related)) {
        foreach ($authors_related as $rel_author) {
            $authors[$rel_author['id']] = $rel_author['name'];
        }
    }

    if (!empty($recipients_related)) {
        foreach ($recipients_related as $rel_recipient) {
            $recipients[$rel_recipient['id']] = $rel_recipient['name'];
        }
    }

    if (!empty($people_mentioned_related)) {
        foreach ($people_mentioned_related as $rel_pm) {
            $people_mentioned[$rel_pm['id']] = $rel_pm['name'];
        }
    }

    if (!empty($origins_related)) {
        foreach ($origins_related as $o) {
            $origins[$o['id']] = $o['name'];
        }
    }

    if (!empty($dests_related)) {
        foreach ($dests_related as $d) {
            $destinations[$d['id']] = $d['name'];
        }
    }

    if (!empty($keywords_related)) {
        foreach ($keywords_related as $k) {
            $keywords[$k['id']] = $lang === 'cs' ? $k['namecz'] : $k['name'];
        }
    }

    $results['abstract'] = $pod->field('abstract');
    $results['archive'] = $pod->field('archive');
    $results['author_inferred'] = (bool) $pod->field('author_inferred');
    $results['author_note'] = $pod->field('author_note');
    $results['author_uncertain'] = (bool) $pod->field('author_uncertain');
    $results['authors_meta'] = json_decode($pod->field('authors_meta'));
    $results['collection'] = $pod->field('collection');
    $results['date_approximate'] = (bool) $pod->field('date_approximate');
    $results['date_day'] = $pod->field('date_day');
    $results['date_inferred'] = (bool) $pod->field('date_inferred');
    $results['date_is_range'] = (bool) $pod->field('date_is_range');
    $results['date_marked'] = $pod->field('date_marked');
    $results['date_month'] = $pod->field('date_month');
    $results['date_note'] = $pod->field('date_note');
    $results['date_uncertain'] = (bool) $pod->field('date_uncertain');
    $results['date_year'] = $pod->field('date_year');
    $results['dest'] = $destinations;
    $results['dest_inferred'] = (bool) $pod->field('dest_inferred');
    $results['dest_note'] = $pod->field('dest_note');
    $results['dest_uncertain'] = (bool) $pod->field('dest_uncertain');
    $results['document_type'] = $pod->field('document_type');
    $results['explicit'] = $pod->field('explicit');
    $results['images'] = get_pod_sorted_images($pod, true);
    $results['incipit'] = $pod->field('incipit');
    $results['keywords'] = $keywords;
    $results['l_author'] = $authors;
    $results['l_number'] = $pod->field('l_number');
    $results['languages'] = $pod->field('languages');
    $results['location_note'] = $pod->field('location_note');
    $results['ms_manifestation'] = $pod->field('ms_manifestation');
    $results['manifestation_notes'] = $pod->field('manifestation_notes');
    $results['name'] = $pod->field('name');
    $results['notes_public'] = $pod->field('notes_public');
    $results['origin'] = $origins;
    $results['origin_inferred'] = (bool) $pod->field('origin_inferred');
    $results['origin_note'] = $pod->field('origin_note');
    $results['origin_uncertain'] = (bool) $pod->field('origin_uncertain');
    $results['people_mentioned'] = $people_mentioned;
    $results['people_mentioned_notes'] = $pod->field('people_mentioned_notes');
    $results['places_meta'] = json_decode($pod->field('places_meta'));
    $results['range_day'] = $pod->field('range_day');
    $results['range_month'] = $pod->field('range_month');
    $results['range_year'] = $pod->field('range_year');
    $results['recipient'] = $recipients;
    $results['recipient_inferred'] = (bool) $pod->field('recipient_inferred');
    $results['recipient_notes'] = $pod->field('recipient_notes');
    $results['recipient_uncertain'] = (bool) $pod->field('recipient_uncertain');
    $results['related_resources'] = $pod->field('related_resources');
    $results['repository'] = $pod->field('repository');
    $results['signature'] = $pod->field('signature');
    $results['status'] = $pod->field('status');

    if (is_user_logged_in()) {
        $results['notes_private'] = $pod->field('notes_private');
    }

    wp_die(json_encode($results, JSON_UNESCAPED_UNICODE));
}
add_action('wp_ajax_list_public_letters_single', 'list_public_letters_single');
add_action('wp_ajax_nopriv_list_public_letters_single', 'list_public_letters_single');
