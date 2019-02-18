<?php

require_once get_template_directory() . '/vendor/autoload.php';


use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

function export_letters()
{

    if (!array_key_exists('type', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $type = sanitize_text_field($_GET['type']);
    $types = get_hiko_post_types($type);

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 403);
    }

    $pod = pods(
        $types['letter'],
        [
        'orderby'=> 't.created DESC',
        'limit' => -1
        ]
    );

    $writer = WriterFactory::create(Type::XLSX);
    $writer->openToBrowser($type . '-export.xlsx');

    $writer->addRow([
            'l_number',
            'date_year',
            'date_month',
            'date_day',
            'date_marked',
            'range_year',
            'range_month',
            'range_day',
            'date_uncertain',
            'date_approximate',
            'date_is_range',
            'date_note',
            'author',
            'author_marked',
            'author_uncertain',
            'author_inferred',
            'author_note',
            'recipient',
            'recipient_marked',
            'recipient_inferred',
            'recipient_uncertain',
            'recipient_notes',
            'origin',
            'origin_marked',
            'origin_inferred',
            'origin_uncertain',
            'origin_note',
            'destination',
            'destination_marked',
            'destination_uncertain',
            'destination_inferred',
            'destination_note',
            'languages',
            'keywords',
            'abstract',
            'incipit',
            'explicit',
            'people_mentioned',
            'people_mentioned_notes',
            'notes_public',
            'rel_rec_name',
            'rel_rec_url',
            'ms_manifestation',
            'repository',
            'archive',
            'collection',
            'signature',
            'description',
            'status',
        ]);
    while ($pod->fetch()) {
        $authors = '';
        $recipients = '';
        $origins = '';
        $destinations = '';
        $people_mentioned = '';
        $row = [
            (string) $pod->field('l_number'),
            (string) $pod->field('date_year'),
            (string) $pod->field('date_month'),
            (string) $pod->field('date_day'),
            (string) $pod->field('date_marked'),
            (string) $pod->field('range_year'),
            (string) $pod->field('range_month'),
            (string) $pod->field('range_day'),
            (string) ($pod->field('date_uncertain') == '1') ? '1' : '',
            (string) ($pod->field('date_approximate') == '1') ? '1' : '',
            (string) ($pod->field('date_is_range') == '1') ? '1' : '',
            (string) $pod->field('date_note'),
            (string) $authors,
            (string) $pod->field('l_author_marked'),
            (string) ($pod->field('author_uncertain') == '1') ? '1' : '',
            (string) ($pod->field('author_inferred') == '1') ? '1' : '',
            (string) $pod->field('author_note'),
            (string) $recipients,
            (string) $pod->field('recipient_marked'),
            (string) ($pod->field('recipient_inferred') == '1') ? '1' : '',
            (string) ($pod->field('recipient_uncertain') == '1') ? '1' : '',
            (string) $pod->field('recipient_notes'),
            (string) $origins,
            (string) $pod->field('origin_marked'),
            (string) ($pod->field('origin_inferred') == '1') ? '1' : '',
            (string) ($pod->field('origin_uncertain') == '1') ? '1' : '',
            (string) $pod->field('origin_note'),
            (string) $destinations,
            (string) $pod->field('dest_marked'),
            (string) ($pod->field('dest_uncertain') == '1') ? '1' : '',
            (string) ($pod->field('dest_inferred') == '1') ? '1' : '',
            (string) $pod->field('dest_note'),
            (string) $pod->field('languages'),
            (string) $pod->field('keywords'),
            (string) $pod->field('abstract'),
            (string) $pod->field('incipit'),
            (string) $pod->field('explicit'),
            (string) $people_mentioned,
            (string) $pod->field('people_mentioned_notes'),
            (string) $pod->field('notes_public'),
            (string) $pod->field('rel_rec_name'),
            (string) $pod->field('rel_rec_url'),
            (string) (is_string($pod->field('ms_manifestation'))) ? $pod->field('ms_manifestation') : '',//($pod->field('ms_manifestation') === null) ? '' : $pod->field('ms_manifestation'),
            (string) $pod->field('repository'),
            (string) $pod->field('archive'),
            (string) $pod->field('collection'),
            (string) $pod->field('signature'),
            (string) $pod->field('name'),
            (string) (is_string($pod->field('status'))) ? $pod->field('status') : '',
        ];
        $writer->addRow($row);
    }
    $writer->close();
    wp_die();
}
add_action('wp_ajax_export_letters', 'export_letters');
