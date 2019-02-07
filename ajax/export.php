<?php

function export_letters()
{

    if (!array_key_exists('l_type', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $type = sanitize_text_field($_GET['l_type']);

    $pod = pods(
        'bl_letter',
        [
            'orderby'=> 't.created DESC',
            'limit' => -1
        ]
    );

    $results = [];
    $index = 0;

    while ($letters_pods->fetch()) {
        $authors = [];
        $recipients = [];
        $authors_full = $letters_pods->field('l_author');
        $recipients_full = $letters_pods->field('recipient');

        if (!empty($authors_full)) {
            foreach ($authors_full as $rel_author) {
                $authors[] = $rel_author['name'];
            }
        }

        if (!empty($recipients_full)) {
            foreach ($recipients_full as $rel_recipient) {
                $recipients[] = $rel_recipient['name'];
            }
        }

        $results[$index]['l_number'] = $pod->field('l_number');
        $results[$index]['date_year'] = $pod->field('date_year');
        $results[$index]['date_month'] = $pod->field('date_month');
        $results[$index]['date_day'] = $pod->field('date_day');
        $results[$index]['date_marked'] = $pod->field('date_marked');
        $results[$index]['date_uncertain'] = (bool) $pod->field('date_uncertain');
        //$results[$index]['l_author'] = $authors;
        $results[$index]['l_author_marked'] = $pod->field('l_author_marked');
        $results[$index]['author_uncertain'] = (bool) $pod->field('author_uncertain');
        $results[$index]['author_inferred'] = (bool) $pod->field('author_inferred');
        //$results[$index]['recipient'] = $recipients;
        $results[$index]['recipient_marked'] = $pod->field('recipient_marked');
        $results[$index]['recipient_inferred'] = (bool) $pod->field('recipient_inferred');
        $results[$index]['recipient_uncertain'] = (bool) $pod->field('recipient_uncertain');
        $results[$index]['recipient_notes'] = $pod->field('recipient_notes');
        $results[$index]['origin'][$pod->field('origin')['id']] = $pod->field('origin')['name'];
        $results[$index]['origin_marked'] = $pod->field('origin_marked');
        $results[$index]['origin_inferred'] = (bool) $pod->field('origin_inferred');
        $results[$index]['origin_uncertain'] = (bool) $pod->field('origin_uncertain');
        $results[$index]['dest'][$pod->field('dest')['id']] = $pod->field('dest')['name'];
        $results[$index]['dest_marked'] = $pod->field('dest_marked');
        $results[$index]['dest_uncertain'] = (bool) $pod->field('dest_uncertain');
        $results[$index]['dest_inferred'] = (bool) $pod->field('dest_inferred');
        $results[$index]['languages'] = $pod->field('languages');
        $results[$index]['keywords'] = $pod->field('keywords');
        $results[$index]['abstract'] = $pod->field('abstract');
        $results[$index]['incipit'] = $pod->field('incipit');
        $results[$index]['explicit'] = $pod->field('explicit');
        $results[$index]['people_mentioned'] = $people_mentioned;
        $results[$index]['people_mentioned_notes'] = $pod->field('people_mentioned_notes');
        $results[$index]['notes_public'] = $pod->field('notes_public');
        $results[$index]['rel_rec_name'] = $pod->field('rel_rec_name');
        $results[$index]['rel_rec_url'] = $pod->field('rel_rec_url');
        $results[$index]['ms_manifestation'] = $pod->field('ms_manifestation');
        $results[$index]['repository'] = $pod->field('repository');
        $results[$index]['name'] = $pod->field('name');
        $results[$index]['status'] = $pod->field('status');
        //$results[$index]['images'] = $images_sorted;
        $results[$index]['notes_private'] = $pod->field('notes_private');

        $index++;
    }

    wp_send_json_succes($results);

}
add_action('wp_ajax_export_letters', 'export_letters');
