<?php

function get_letter_export_data($type, $ids)
{
    global $wpdb;

    $post_types = get_hiko_post_types($type);

    $podsAPI = new PodsAPI();

    $pod = $podsAPI->load_pod(['name' => $post_types['letter']]);

    $fields_id = [
        'author' => $pod['fields']['l_author']['id'],
        'recipient' => $pod['fields']['recipient']['id'],
        'origin' => $pod['fields']['origin']['id'],
        'dest' => $pod['fields']['dest']['id'],
        'kw' => $pod['fields']['keywords']['id'],
        'pm' => $pod['fields']['people_mentioned']['id'],
    ];

    $prefix = [
        'letter' => "{$wpdb->prefix}pods_{$post_types['letter']}",
        'relation' => "{$wpdb->prefix}podsrel",
        'place' => "{$wpdb->prefix}pods_{$post_types['place']}",
        'person' => "{$wpdb->prefix}pods_{$post_types['person']}",
        'kw' => "{$wpdb->prefix}pods_{$post_types['keyword']}",
        'pm' => "{$wpdb->prefix}pods_{$post_types['person']}",
    ];

    $fields = implode(', ', [
        't.ID',
        't.date_year',
        't.date_month',
        't.date_day',
        't.date_marked',
        't.date_uncertain',
        't.date_approximate',
        't.date_is_range',
        't.range_year',
        't.range_month',
        't.range_day',
        't.date_note',
        't.languages',
        'l_author.id AS a_id',
        'l_author.name AS a_name',
        't.author_inferred',
        't.author_uncertain',
        'recipient.id AS r_id',
        'recipient.name AS r_name',
        't.recipient_inferred',
        't.recipient_uncertain',
        'origin.id AS o_id',
        'origin.name AS o_name',
        't.origin_inferred',
        't.origin_uncertain',
        'dest.id AS d_id',
        'dest.name AS d_name',
        't.dest_inferred',
        't.dest_uncertain',
        'keywords.name AS keyword',
        'people_mentioned.name AS pm',
        't.name',
        't.l_number',
        't.recipient_notes',
        't.languages',
        't.abstract',
        't.incipit',
        't.explicit',
        't.people_mentioned_notes',
        't.notes_public',
        't.notes_private',
        't.related_resources',
        't.repository',
        't.status',
        't.author_note',
        't.origin_note',
        't.dest_note',
        't.copies',
        't.authors_meta',
        't.places_meta',
        't.document_type',
        't.date_inferred',
        't.manifestation_notes'
    ]);

    $where = empty($ids) ? '' : 'WHERE t.ID in (' . implode(',', $ids) . ')';

    $query = "SELECT {$fields} FROM {$prefix['letter']} AS t
    LEFT JOIN {$prefix['relation']} AS rel_l_author ON
        rel_l_author.field_id = {$fields_id['author']}
        AND rel_l_author.item_id = t.id

    LEFT JOIN {$prefix['person']} AS l_author ON
        l_author.id = rel_l_author.related_item_id

    LEFT JOIN {$prefix['relation']} AS rel_recipient ON
        rel_recipient.field_id = {$fields_id['recipient']}
        AND rel_recipient.item_id = t.id

    LEFT JOIN {$prefix['person']} AS recipient ON
        recipient.id = rel_recipient.related_item_id

    LEFT JOIN {$prefix['relation']} AS rel_origin ON
        rel_origin.field_id = {$fields_id['origin']}
        AND rel_origin.item_id = t.id

    LEFT JOIN {$prefix['place']} AS origin ON
        origin.id = rel_origin.related_item_id

    LEFT JOIN {$prefix['relation']} AS rel_dest ON
        rel_dest.field_id = {$fields_id['dest']}
        AND rel_dest.item_id = t.id

    LEFT JOIN {$prefix['place']} AS dest ON
        dest.id = rel_dest.related_item_id

    LEFT JOIN {$prefix['relation']} AS rel_keywords ON
        rel_keywords.field_id = {$fields_id['kw']}
        AND rel_keywords.item_id = t.id

    LEFT JOIN {$prefix['kw']} AS keywords ON
        keywords.id = rel_keywords.related_item_id

    LEFT JOIN {$prefix['relation']} AS rel_people_mentioned ON
    rel_people_mentioned.field_id = {$fields_id['pm']}
        AND rel_people_mentioned.item_id = t.id

    LEFT JOIN {$prefix['pm']} AS people_mentioned ON
        people_mentioned.id = rel_people_mentioned.related_item_id
    {$where}";

    return parse_letter_export_data(
        $wpdb->get_results($query, ARRAY_A)
    );
}


add_action('wp_ajax_export_letters', function () {
    if (!array_key_exists('type', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $type = sanitize_text_field($_GET['type']);

    array_to_csv_download(
        get_letter_export_data($type, []),
        "export-$type.csv"
    );

    wp_die();
});


add_action('wp_ajax_export_selected_letters', function () {
    $data = decode_php_input();

    array_to_csv_download(
        get_letter_export_data($data->type, $data->ids),
        "export-selected-{$data->type}.csv"
    );

    wp_die();
});


add_action('wp_ajax_export_persons', function () {
    if (!array_key_exists('type', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    if ($_GET['format'] !== 'csv') {
        wp_send_json_error('Format not found', 404);
    }

    global $wpdb;
    $type = get_hiko_post_types($_GET['type']);
    $professions = get_professions($type['profession'], $type['default_lang']);
    $query = "SELECT `name`, `surname`, `forename`, `birth_year`, `death_year`, `profession_detailed` AS profession, `profession_short` AS category, `nationality`, `gender`, `note`, `type`
        FROM {$wpdb->prefix}pods_{$type['person']}
        ORDER BY `name`";

    $persons = $wpdb->get_results($query, ARRAY_A);

    foreach ($persons as $key => $person) {
        $persons[$key]['profession'] = implode('|', explode(';', parse_professions($person['profession'], $professions)));
        $persons[$key]['category'] = implode('|', explode(';', parse_professions($person['category'], $professions)));
    }

    array_to_csv_download($persons, "export-{$type['person']}.csv");
    wp_die();
});


add_action('wp_ajax_export_places', function () {
    if (!array_key_exists('type', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    if ($_GET['format'] !== 'csv') {
        wp_send_json_error('Format not found', 404);
    }

    $type = sanitize_text_field($_GET['type']);

    global $wpdb;

    $fields = 'name, country, note, longitude, latitude, note';

    $query = "SELECT {$fields} FROM {$wpdb->prefix}pods_{$type} ORDER BY `name`";

    array_to_csv_download(
        $wpdb->get_results($query, ARRAY_A),
        "export-$type.csv"
    );

    wp_die();
});


function parse_letter_export_data($query_result)
{
    $result = [];

    foreach (merge_distinct_query_result($query_result) as $row) {
        $authors_meta = json_decode($row['authors_meta'], true);
        $places_meta = json_decode($row['places_meta'], true);
        $copies = get_export_letter_copies_meta($row['copies']);

        $result[] = [
            'ID' => $row['ID'],
            'Date' => get_export_letter_date($row, false),
            'Date as marked' => $row['date_marked'],
            'Date uncertain' => (bool) $row['date_uncertain'],
            'Date approximate' => (bool) $row['date_approximate'],
            'Date inferred' => (bool) $row['date_inferred'],
            'Date is range' => (bool) $row['date_is_range'],
            'Date 2 (range)' => (bool) $row['date_is_range'] ? get_export_letter_date($row, true) : '',
            'Notes on date' => $row['date_note'],
            'Author' =>  get_export_letter_meta($row['a_id'], $row['a_name'], $authors_meta),
            'Author inferred' => (bool) $row['author_inferred'],
            'Author uncertain' => (bool) $row['author_uncertain'],
            'Notes on author' => $row['author_note'],
            'Recipient' =>  get_export_letter_meta($row['r_id'], $row['r_name'], $authors_meta),
            'Recipient inferred' => (bool) $row['recipient_inferred'],
            'Recipient uncertain' => (bool) $row['recipient_uncertain'],
            'Notes on recipient' => $row['recipient_notes'],
            'Origin' =>  get_export_letter_meta($row['o_id'], $row['o_name'], $places_meta),
            'Origin inferred' => (bool) $row['origin_inferred'],
            'Origin uncertain' => (bool) $row['origin_uncertain'],
            'Notes on origin' => $row['origin_note'],
            'Destination' =>  get_export_letter_meta($row['d_id'], $row['d_name'], $places_meta),
            'Destination inferred' => (bool) $row['dest_inferred'],
            'Destination uncertain' => (bool) $row['dest_uncertain'],
            'Notes on destination' => $row['dest_note'],
            'Languages' => str_replace(';', '|', $row['languages']),
            'Keywords' =>  get_export_letter_join_list($row['keyword']),
            'Abstract' => $row['abstract'],
            'Incipit' => $row['incipit'],
            'Explicit' => $row['explicit'],
            'People mentioned' =>  get_export_letter_join_list($row['pm']),
            'Notes on people mentioned' => $row['people_mentioned_notes'],
            'Notes on letter for public display' => $row['notes_public'],
            'Editor\'s notes' => $row['notes_private'],
            'Related resources' => get_export_letter_related_resources($row['related_resources']),
            'MS manifestation' => $copies['ms_manifestation'],
            'Notes on manifestation' => $copies['manifestation_notes'],
            'Document type' => $copies['type'],
            'Preservation' => $copies['preservation'],
            'Type of copy' => $copies['copy'],
            'Repository' => $copies['repository'],
            'Archive' => $copies['Archive'],
            'Collection' => $copies['collection'],
            'Signature' => $copies['signature'],
            'Notes on location' => $copies['location_note'],
            'Status' => $row['status'],
        ];
    }

    return $result;
}


function get_export_letter_date($letter, $is_range)
{
    $dates =  [
        'year' => $is_range ? 'range_year' : 'date_year',
        'month' => $is_range ? 'range_month' : 'date_month',
        'day' => $is_range ? 'range_day' : 'date_day',
    ];

    $date = intval($letter[$dates['year']]) . '-';
    $date .= intval($letter[$dates['month']]) . '-';
    $date .= intval($letter[$dates['day']]);

    return $date;
}


function get_export_letter_meta($ids, $names, $meta)
{
    if (!$ids || empty($ids)) {
        return '';
    }

    if (is_string($ids)) {
        $ids = [$ids];
    }

    if (is_string($names)) {
        $names = [$names];
    }

    $result = [];
    for ($i = 0; $i < count($ids); $i++) {
        $meta_row = array_filter($meta, function ($item) use ($ids, $i) {
            return $item['id'] == $ids[$i];
        });
        $meta_row = array_values(array_filter($meta_row)); // reindex
        $meta_row = $meta_row[0];

        $item = 'Name: ' . $names[$i] . ', marked as: ' . $meta_row['marked'];
        if (isset($meta_row['salutation']) && !empty($meta_row['salutation'])) {
            $item .= ', salutation: ' . $meta_row['salutation'];
        }

        $result[] = $item;
    }

    return implode('|', $result);
}


function get_export_letter_join_list($data)
{
    $result = '';

    if (is_array($data)) {
        $result = implode('|', array_unique($data));
    } elseif (is_string($data)) {
        $result = $data;
    }

    return $result;
}


function get_export_letter_related_resources($data)
{
    $data = json_decode($data, true);

    if (!$data || empty($data)) {
        return '';
    }

    $result = [];

    foreach ($data as $item) {
        if (empty($item['title'])) {
            continue;
        }

        $formatted = $item['title'];
        if (!empty($item['link'])) {
            $formatted .= ' (' . $item['link'] . ')';
        }

        $result[] = $formatted;
    }

    return implode('|', $result);
}

function get_export_letter_copies_meta($data)
{
    $copies = json_decode($data, true);

    $type = array_column($copies, 'type');
    $preservation = array_column($copies, 'preservation');
    $copy = array_column($copies, 'copy');
    $repository = array_column($copies, 'repository');
    $archive = array_column($copies, 'archive');
    $collection = array_column($copies, 'collection');
    $collection = array_column($copies, 'collection');
    $signature = array_column($copies, 'signature');
    $ms_manifestation = array_column($copies, 'ms_manifestation');
    $manifestation_notes = array_column($copies, 'manifestation_notes');
    $location_note = array_column($copies, 'location_note');

    return [
        'type' => !isset($type) || empty($type) ? '' : $type[0],
        'preservation' => !isset($preservation) || empty($preservation) ? '' : $preservation[0],
        'copy' => !isset($copy) || empty($copy) ? '' : $copy[0],
        'repository' => !isset($repository) || empty($repository) ? '' : $repository[0],
        'archive' => !isset($archive) || empty($archive) ? '' : $archive[0],
        'collection' => !isset($collection) || empty($collection) ? '' : $collection[0],
        'signature' => !isset($signature) || empty($signature) ? '' : $signature[0],
        'ms_manifestation' => !isset($ms_manifestation) || empty($ms_manifestation) ? '' : $ms_manifestation[0],
        'manifestation_notes' => !isset($manifestation_notes) || empty($manifestation_notes) ? '' : $manifestation_notes[0],
        'location_note' => !isset($location_note) || empty($location_note) ? '' : $location_note[0],
    ];
}

