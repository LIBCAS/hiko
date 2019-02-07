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
    $origin = [];
    $destination = [];

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

    $results['l_number'] = $pod->field('l_number');
    $results['date_year'] = $pod->field('date_year');
    $results['date_month'] = $pod->field('date_month');
    $results['date_day'] = $pod->field('date_day');
    $results['date_marked'] = $pod->field('date_marked');
    $results['date_uncertain'] = (bool) $pod->field('date_uncertain');
    $results['l_author'] = $authors;
    $results['l_author_marked'] = $pod->field('l_author_marked');
    $results['author_uncertain'] = (bool) $pod->field('author_uncertain');
    $results['author_inferred'] = (bool) $pod->field('author_inferred');
    $results['recipient'] = $recipients;
    $results['recipient_marked'] = $pod->field('recipient_marked');
    $results['recipient_inferred'] = (bool) $pod->field('recipient_inferred');
    $results['recipient_uncertain'] = (bool) $pod->field('recipient_uncertain');
    $results['recipient_notes'] = $pod->field('recipient_notes');
    $results['origin'][$pod->field('origin')['id']] = $pod->field('origin')['name'];
    $results['origin_marked'] = $pod->field('origin_marked');
    $results['origin_inferred'] = (bool) $pod->field('origin_inferred');
    $results['origin_uncertain'] = (bool) $pod->field('origin_uncertain');
    $results['dest'][$pod->field('dest')['id']] = $pod->field('dest')['name'];
    $results['dest_marked'] = $pod->field('dest_marked');
    $results['dest_uncertain'] = (bool) $pod->field('dest_uncertain');
    $results['dest_inferred'] = (bool) $pod->field('dest_inferred');
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
        echo '404';
        wp_die();
    }

    if (!has_user_permission('blekastad_editor')) {
        echo '403';
        wp_die();
    }

    $pod = pods('bl_letter', $_GET['pods_id']);
    $result = $pod->delete();
    echo $result;

    wp_die();
}

add_action('wp_ajax_delete_bl_letter', 'delete_bl_letter');
