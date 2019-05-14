<?php

function list_letter_history()
{
    $l_type = test_input($_GET['l_type']);
    $l_id = test_input($_GET['l_id']);
    $types = get_hiko_post_types($l_type);

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 403);
    }
    $history = get_letter_history($types['letter'], $l_id);

    if (!$history) {
        wp_send_json_error('Not found', 404);
    } else {
        wp_send_json_success($history);
    }
}
add_action('wp_ajax_list_letter_history', 'list_letter_history');


function list_all_letters_short()
{
    $type = test_input($_GET['type']);
    $types = get_hiko_post_types($type);

    header('Content-Type: application/json');

    if (hiko_cache_exists('list_' . $types['path'])) {
        header('Last-Modified: ' . get_gmdate(get_hiko_cache_file('list_' . $types['path'])));
        echo read_hiko_cache('list_' . $types['path']);
        wp_die();
    }

    $letters = get_letters_basic_meta_filtered($types['letter'], $types['person'], $types['place']);


    $json_letters = json_encode(
        $letters,
        JSON_UNESCAPED_UNICODE
    );

    header('Last-Modified: ' . get_gmdate());
    echo $json_letters;

    create_hiko_json_cache('list_' . $types['path'], $json_letters);
    wp_die();
}
add_action('wp_ajax_list_all_letters_short', 'list_all_letters_short');


function list_public_letters_single()
{
    $results = [];
    $authors = [];
    $recipients = [];
    $people_mentioned = [];
    $origins = [];
    $destinations = [];
    $l_type = test_input($_GET['l_type']);

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
    $images = $pod->field('images');

    $images_sorted = [];
    $i = 0;
    foreach ($images as $img) {
        if ($img['post_status'] != 'private') {
            $size = wp_get_attachment_image_src($img['ID'], 'large');
            $images_sorted[$i]['img']['large'] = $img['guid'];
            $images_sorted[$i]['img']['thumb'] = wp_get_attachment_image_src($img['ID'], 'thumbnail')[0];
            $images_sorted[$i]['description'] = get_post_field('post_content', $img['ID']);
            $images_sorted[$i]['order'] = intval(get_post_meta($img['ID'], 'order', true));
            $images_sorted[$i]['size'] = [
                'w' => $size[1],
                'h' => $size[2],
            ];

            $i++;
        }
    }

    usort($images_sorted, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

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

    $results['l_number'] = $pod->field('l_number');
    $results['date_year'] = $pod->field('date_year');
    $results['date_month'] = $pod->field('date_month');
    $results['date_day'] = $pod->field('date_day');
    $results['date_marked'] = $pod->field('date_marked');
    $results['range_year'] = $pod->field('range_year');
    $results['range_month'] = $pod->field('range_month');
    $results['range_day'] = $pod->field('range_day');
    $results['date_uncertain'] = (bool) $pod->field('date_uncertain');
    $results['date_approximate'] = (bool) $pod->field('date_approximate');
    $results['date_is_range'] = (bool) $pod->field('date_is_range');
    $results['date_note'] = $pod->field('date_note');
    $results['l_author'] = $authors;
    $results['author_uncertain'] = (bool) $pod->field('author_uncertain');
    $results['author_inferred'] = (bool) $pod->field('author_inferred');
    $results['author_note'] = $pod->field('author_note');
    $results['recipient'] = $recipients;
    $results['recipient_inferred'] = (bool) $pod->field('recipient_inferred');
    $results['recipient_uncertain'] = (bool) $pod->field('recipient_uncertain');
    $results['recipient_notes'] = $pod->field('recipient_notes');
    $results['origin'] = $origins;
    $results['origin_inferred'] = (bool) $pod->field('origin_inferred');
    $results['origin_uncertain'] = (bool) $pod->field('origin_uncertain');
    $results['origin_note'] = $pod->field('origin_note');
    $results['dest'] = $destinations;
    $results['dest_uncertain'] = (bool) $pod->field('dest_uncertain');
    $results['dest_inferred'] = (bool) $pod->field('dest_inferred');
    $results['dest_note'] = $pod->field('dest_note');
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
    $results['status'] = $pod->field('status');

    $results['archive'] = $pod->field('archive');
    $results['collection'] = $pod->field('collection');
    $results['signature'] = $pod->field('signature');
    $results['location_note'] = $pod->field('location_note');
    $results['authors_meta'] = json_decode($pod->field('authors_meta'));
    $results['places_meta'] = json_decode($pod->field('places_meta'));
    $results['document_type'] = $pod->field('document_type');

    $results['images'] = $images_sorted;
    if (is_user_logged_in()) {
        $results['notes_private'] = $pod->field('notes_private');
    }

    echo json_encode(
        $results,
        JSON_UNESCAPED_UNICODE
    );

    wp_die();
}
add_action('wp_ajax_list_public_letters_single', 'list_public_letters_single');
add_action('wp_ajax_nopriv_list_public_letters_single', 'list_public_letters_single');
