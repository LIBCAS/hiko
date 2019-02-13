<?php

function list_public_bl_letters_short()
{
    $letters = get_letters_basic_meta_filtered('bl_letter', 'bl_person', 'bl_place');
    /*
    if not logged filter private
    */
    echo json_encode(
        [$letters],
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
    $origins = [];
    $destinations = [];

    if (!array_key_exists('pods_id', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $pod = pods('bl_letter', $_GET['pods_id']);

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
    $results['l_author_marked'] = $pod->field('l_author_marked');
    $results['author_uncertain'] = (bool) $pod->field('author_uncertain');
    $results['author_inferred'] = (bool) $pod->field('author_inferred');
    $results['author_note'] = $pod->field('author_note');
    $results['recipient'] = $recipients;
    $results['recipient_marked'] = $pod->field('recipient_marked');
    $results['recipient_inferred'] = (bool) $pod->field('recipient_inferred');
    $results['recipient_uncertain'] = (bool) $pod->field('recipient_uncertain');
    $results['recipient_notes'] = $pod->field('recipient_notes');
    $results['origin'] = $origins;
    $results['origin_marked'] = $pod->field('origin_marked');
    $results['origin_inferred'] = (bool) $pod->field('origin_inferred');
    $results['origin_uncertain'] = (bool) $pod->field('origin_uncertain');
    $results['origin_note'] = $pod->field('origin_note');
    $results['dest'] = $destinations;
    $results['dest_marked'] = $pod->field('dest_marked');
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
add_action('wp_ajax_list_public_bl_letters_single', 'list_public_bl_letters_single');
add_action('wp_ajax_nopriv_list_public_bl_letters_single', 'list_public_bl_letters_single');



function delete_bl_letter()
{
    if (!array_key_exists('pods_id', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    if (!has_user_permission('blekastad_editor')) {
        wp_send_json_error('Not allowed', 403);
    }

    $pod = pods('bl_letter', $_GET['pods_id']);

    $images = $pod->field('images');

    foreach ($images as $img) {
        wp_delete_attachment($img['ID'], true);
    }

    $result = $pod->delete();

    wp_send_json_success($result);
}

add_action('wp_ajax_delete_bl_letter', 'delete_bl_letter');
