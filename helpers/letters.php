<?php

function get_letter($types, $id, $lang, $private)
{
    $pod = pods($types['letter'], $id);

    $lang = empty($lang) ? $types['default_lang'] : $lang;

    if (!$pod->exists()) {
        return [];
    }

    $all_fields = $pod->export();

    $unused_fields = [
        'authors_meta',
        'places_meta',
        'created',
        'modified',
        'author',
        'history',
    ];

    $related_fields = [
        'l_author',
        'recipient',
        'origin',
        'dest',
        'people_mentioned',
    ];

    $bool_fields = [
        'author_inferred',
        'author_uncertain',
        'date_approximate',
        'date_inferred',
        'date_is_range',
        'date_uncertain',
        'dest_inferred',
        'dest_uncertain',
        'origin_inferred',
        'origin_uncertain',
        'recipient_inferred',
        'recipient_uncertain',
    ];

    $private_fields = [
        'notes_private',
    ];

    $result = [];
    foreach ($all_fields as $key => $value) {
        if (in_array($key, $unused_fields)) {
            continue;
        }

        if (in_array($key, $related_fields)) {
            $result[$key] = [];
            foreach ($value as $field) {
                $meta = [
                    'id' => $field['id'],
                    'name' => $field['name'],
                ];

                if (in_array($key, ['l_author', 'recipient', 'people_mentioned'])) {
                    $meta['name'] = format_person_name($field['name'], $field['birth_year'], $field['death_year']);
                }

                if (in_array($key, ['l_author', 'recipient'])) {
                    $meta = get_field_related_meta($meta, json_decode($pod->field('authors_meta'), true));
                }

                if (in_array($key, ['origin', 'dest'])) {
                    $meta = get_field_related_meta($meta, json_decode($pod->field('places_meta'), true), $key);
                }

                $result[$key][] = $meta;
            }

            continue;
        }

        if ($key === 'keywords') {
            $result['keywords'] = [];
            foreach ($value as $field) {
                $result['keywords'][] = [
                    'id' => $field['id'],
                    'name' => $lang === 'cs' ? $field['namecz'] : $field['name'],
                ];
            }

            continue;
        }

        if ($key === 'languages') {
            $result['languages'] = explode(';', $value);
            continue;
        }

        if ($key === 'related_resources') {
            if (empty($value)) {
                $result['related_resources'] = [];
                continue;
            }

            $value = json_decode($value, true);
            $result['related_resources'] = !isset($value[0]['title']) || empty($value[0]['title']) ? [] : $value;
            continue;
        }

        if ($key === 'copies') {
            $result['copies'] = empty($value) ? [] : json_decode($value, true);
            continue;
        }

        if (in_array($key, $bool_fields)) {
            $result[$key] = (bool) $value;
            continue;
        }

        if ($key === 'images') {
            $result['images'] = get_sorted_images($value);
            continue;
        }

        if (in_array($key, $private_fields)) {
            if ($private) {
                $result[$key] = $value;
            }
            continue;
        }

        $result[$key] = $value;
    }

    return $result;
}


function get_field_related_meta($field_meta, $meta, $item = '')
{
    if (empty($meta)) {
        return $field_meta;
    }

    $filtered_values =
        array_filter($meta, function ($element) use ($field_meta) {
            return $element['id'] == $field_meta['id'];
        });

    $filtered_values = array_values($filtered_values);

    if (count($filtered_values) === 1) {
        return array_merge($field_meta, $filtered_values[0]);
    }

    if ($item && !empty($filtered_values)) {
        $item = $item === 'dest' ? 'destination' : 'origin';

        $filtered_places = array_filter($filtered_values, function ($element) use ($item) {
            return $element['type'] == $item;
        });

        $filtered_places = array_values($filtered_places);

        if (count($filtered_places) === 1) {
            return array_merge($field_meta, $filtered_places[0]);
        }
    }

    return $field_meta;
}


function list_public_letters_single()
{
    if (!isset($_GET['pods_id']) || !isset($_GET['l_type'])) {
        wp_send_json_error('Not found', 404);
    }

    $letter = get_letter(
        get_types_by_letter()[$_GET['l_type']],
        $_GET['pods_id'],
        isset($_GET['lang']) ? test_input($_GET['lang']) : '',
        is_user_logged_in()
    );

    if (empty($letter)) {
        wp_send_json_error('Not found', 404);
    }

    if ($letter['status'] !== 'publish' && !is_user_logged_in()) {
        wp_send_json_error('Not allowed', 403);
    }

    header('Content-Type: application/json');
    wp_die(json_encode($letter, JSON_UNESCAPED_UNICODE));
}
add_action('wp_ajax_list_public_letters_single', 'list_public_letters_single');
add_action('wp_ajax_nopriv_list_public_letters_single', 'list_public_letters_single');


function save_letter($letter_type, $action, $path)
{
    $types = get_hiko_post_types($path);

    $authors = [];
    $recipients = [];
    $origins = [];
    $destinations = [];
    $people_mentioned = [];
    $keywords = [];
    $related_resources = [];
    $languages = [];
    $copies = [];

    if (isset($_POST['authors']) && !empty($_POST['authors'])) {
        $authors = sanitize_slashed_json($_POST['authors']);
    }

    if (isset($_POST['recipients']) && !empty($_POST['recipients'])) {
        $recipients = sanitize_slashed_json($_POST['recipients']);
    }

    $participant_meta = array_merge($authors, $recipients);

    if (isset($_POST['origin']) && !empty($_POST['origin'])) {
        $origins = sanitize_slashed_json($_POST['origin']);
    }

    if (isset($_POST['dest']) && !empty($_POST['dest'])) {
        $destinations = sanitize_slashed_json($_POST['dest']);
    }

    $places_meta = array_merge($origins, $destinations);

    if (isset($_POST['people_mentioned']) && !empty($_POST['people_mentioned'])) {
        $people_mentioned = sanitize_slashed_json($_POST['people_mentioned']);
    }

    if (isset($_POST['keywords']) && !empty($_POST['keywords'])) {
        $keywords = sanitize_slashed_json($_POST['keywords']);
    }

    if (isset($_POST['languages']) && !empty($_POST['languages'])) {
        $languages = sanitize_slashed_json($_POST['languages']);
    }

    if (isset($_POST['related_resources']) && !empty($_POST['related_resources'])) {
        $related_resources = json_decode(stripslashes($_POST['related_resources']), true);
    }

    if (isset($_POST['copies']) && !empty($_POST['copies'])) {
        $copies = sanitize_slashed_json($_POST['copies']);
    }

    if ($action == 'new') {
        $history = date('Y-m-d H:i:s') . ' – ' . get_full_name() . "\n";
    } elseif ($action == 'edit') {
        $history = get_letter_history($letter_type, $_GET['edit']);
        if ($history == '') {
            $created = get_letter_created($letter_type, $_GET['edit']);
            $history = $created['date'] . ' – ' . $created['author'] . "\n";
        }
        $history .= "\n" . date('Y-m-d H:i:s') . ' – ' . get_full_name() . "\n";
    }

    $data = test_postdata([
        'abstract' => 'abstract',
        'author_note' => 'author_note',
        'copyright' => 'copyright',
        'date_day' => 'date_day',
        'date_marked' => 'date_marked',
        'date_month' => 'date_month',
        'date_note' => 'date_note',
        'date_year' => 'date_year',
        'dest_note' => 'dest_note',
        'explicit' => 'explicit',
        'incipit' => 'incipit',
        'name' => 'description',
        'notes_private' => 'notes_private',
        'notes_public' => 'notes_public',
        'origin_note' => 'origin_note',
        'people_mentioned_notes' => 'people_mentioned_notes',
        'range_day' => 'range_day',
        'range_month' => 'range_month',
        'range_year' => 'range_year',
        'recipient_notes' => 'recipient_notes',
        'status' => 'status',
    ]);

    $data['author_inferred'] = get_form_checkbox_val('author_inferred', $_POST);
    $data['author_uncertain'] = get_form_checkbox_val('author_uncertain', $_POST);
    $data['authors_meta'] = !empty($participant_meta) ? json_encode($participant_meta, JSON_UNESCAPED_UNICODE) : null;
    $data['copies'] = !empty($copies) ? json_encode($copies, JSON_UNESCAPED_UNICODE) : null;
    $data['date_approximate'] = get_form_checkbox_val('date_approximate', $_POST);
    $data['date_inferred'] = get_form_checkbox_val('date_inferred', $_POST);
    $data['date_is_range'] = get_form_checkbox_val('date_is_range', $_POST);
    $data['date_uncertain'] = get_form_checkbox_val('date_uncertain', $_POST);
    $data['dest'] = array_column($destinations, 'id');
    $data['dest_inferred'] = get_form_checkbox_val('dest_inferred', $_POST);
    $data['dest_uncertain'] = get_form_checkbox_val('dest_uncertain', $_POST);
    $data['history'] = $history;
    $data['keywords'] = array_column($keywords, 'id');
    $data['languages'] = implode(';', array_column($languages, 'value'));
    $data['l_author'] = array_column($authors, 'id');
    $data['origin'] = array_column($origins, 'id');
    $data['origin_inferred'] = get_form_checkbox_val('origin_inferred', $_POST);
    $data['origin_uncertain'] = get_form_checkbox_val('origin_uncertain', $_POST);
    $data['people_mentioned'] = array_column($people_mentioned, 'id');
    $data['places_meta'] = !empty($places_meta) ? json_encode($places_meta, JSON_UNESCAPED_UNICODE) : null;
    $data['recipient'] = array_column($recipients, 'id');
    $data['recipient_inferred'] = get_form_checkbox_val('recipient_inferred', $_POST);
    $data['recipient_uncertain'] = get_form_checkbox_val('recipient_uncertain', $_POST);
    $data['related_resources'] = !empty($related_resources) ? json_encode($related_resources, JSON_UNESCAPED_UNICODE) : null;

    $new_data = [
        'pod' => $letter_type,
        'data' => $data
    ];

    if ($action == 'edit') {
        $new_data['id'] = (int) $_GET['edit'];
    }

    $new_pod = pods_api()->save_pod_item($new_data);

    if (is_wp_error($new_pod)) {
        return alert($new_pod->get_error_message(), 'warning');
    }

    save_name_alternatives($participant_meta, $types['person']);

    frontend_refresh();

    return alert('Uloženo', 'success');
}


function save_name_alternatives($persons, $person_type)
{
    foreach ($persons as $person) {
        if ($person['marked'] === '') {
            continue;
        }

        $person_meta = pods_field($person_type, $person['id'], 'persons_meta');

        if ($person_meta == null) {
            $data = ['names' => [$person['marked']]];
        } else {
            $old_data = json_decode($person_meta);
            $data = [
                'names' => merge_unique($old_data->names, [$person['marked']])
            ];
        }

        pods_api()->save_pod_item([
            'pod' => $person_type,
            'data' => [
                'persons_meta' => json_encode($data, JSON_UNESCAPED_UNICODE)
            ],
            'id' => $person['id'],
        ]);
    }
}


add_action('wp_ajax_list_letter_history', function () {
    $types = get_hiko_post_types(test_input($_GET['l_type']));

    if (!has_user_permission($types['editor'])) {
        wp_send_json_error('Not allowed', 403);
    }

    $history = get_letter_history($types['letter'], test_input($_GET['l_id']));

    if (!$history) {
        wp_send_json_error('Not found', 404);
    }

    wp_send_json_success($history);
});


add_action('wp_ajax_list_all_letters_short', function () {
    $types = get_hiko_post_types(test_input($_GET['type']));
    $letters = get_letters_basic_meta_filtered($types, true, true);

    $results = [];

    foreach ($letters as $letter) {
        $signature = [];
        foreach ($letter['copies'] as $copy) {
            $meta = [];

            if (!empty($copy['signature'])) {
                $meta[] = $copy['signature'];
            }

            if (!empty($copy['repository'])) {
                $meta[] = $copy['repository'];
            }

            if (!empty($copy['archive'])) {
                $meta[] = $copy['archive'];
            }

            $signature[] = implode('/', $meta);
        }

        $date = format_letter_date($letter['date_day'], $letter['date_month'], $letter['date_year']);
        if ($letter['date_is_range']) {
            $date .= ' – ' . format_letter_date($letter['range_day'], $letter['range_month'], $letter['range_year']);
        }

        $results[] = [
            'ID' => $letter['ID'],
            'author' => $letter['author'],
            'category' => $letter['category'],
            'date_formatted' => $date,
            'dest' => $letter['dest'],
            'editors' => $letter['editors'],
            'keyword' => $letter['keyword'],
            'images' => empty($letter['images']) ? 0 : count($letter['images']),
            'my_letter' => $letter['my_letter'],
            'origin' => $letter['origin'],
            'recipient' => $letter['recipient'],
            'signature' => implode('<br>', $signature),
            'status' => $letter['status'],
            'timestamp' => get_timestamp($letter['date_day'], $letter['date_month'], $letter['date_year']),
        ];
    }

    header('Content-Type: application/json');
    header('Last-Modified: ' . get_gmdate());
    wp_die(json_encode($results, JSON_UNESCAPED_UNICODE));
});


function public_list_all_letters()
{
    $letters = get_letters_basic_meta_filtered(
        get_hiko_post_types(test_input($_GET['type'])),
        false
    );

    $results = [];

    foreach ($letters as $letter) {
        $letter['copies'] = empty($letter['copies']) ? [] : json_decode($letter['copies'], true);
        $signature = array_column($letter['copies'], 'signature');

        $results[] = [
            'id' => $letter['ID'],
            'sig' => !isset($signature) || empty($signature) ? '' : $signature[0],
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


function list_all_letters_meta($post_types)
{
    global $wpdb;
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
        't.id AS ID',
        't.copies',
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
        't.author_inferred',
        't.author_uncertain',
        't.recipient_inferred',
        't.recipient_uncertain',
        't.origin_inferred',
        't.origin_uncertain',
        't.dest_inferred',
        't.dest_uncertain',
        't.name',
        't.recipient_notes',
        't.languages',
        't.abstract',
        't.incipit',
        't.explicit',
        't.people_mentioned_notes',
        't.notes_public',
        't.notes_private',
        't.related_resources',
        't.status',
        't.author_note',
        't.origin_note',
        't.dest_note',
        't.authors_meta',
        't.places_meta',
        't.date_inferred',
        'l_author.id AS a_id',
        'l_author.name AS a_name',
        't.author_inferred',
        'recipient.id AS r_id',
        'recipient.name AS r_name',
        'origin.id AS o_id',
        'origin.name AS o_name',
        'dest.id AS d_id',
        'dest.name AS d_name',
        'keywords.name AS keyword',
        'people_mentioned.name AS pm',
    ]);

    $query = "
    SELECT {$fields}
    FROM {$prefix['letter']} AS t
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
    ";

    $query_result = $wpdb->get_results($query, ARRAY_A);
    $letters = merge_distinct_query_result($query_result);

    foreach ($letters as $key => $letter) {
        $related_resources = [];

        if (!empty($letter['related_resources'])) {
            $related_resources = json_decode($letter['related_resources'], true);
            $related_resources = !isset($related_resources[0]['title']) || empty($related_resources[0]['title']) ? [] : $related_resources;
        }

        $letters[$key]['authors'] = add_field_related_data($letter['a_id'], $letter['a_name'], json_decode($letter['authors_meta'], true), '');
        $letters[$key]['recipients'] = add_field_related_data($letter['r_id'], $letter['r_name'], json_decode($letter['authors_meta'], true), '');
        $letters[$key]['origins'] = add_field_related_data($letter['o_id'], $letter['o_name'], json_decode($letter['places_meta'], true), 'origin');
        $letters[$key]['destinations'] = add_field_related_data($letter['d_id'], $letter['d_name'], json_decode($letter['places_meta'], true), 'dest');
        $letters[$key]['related_resources'] = $related_resources;
        $letters[$key]['copies'] = json_decode($letter['copies'], true);
    }

    return $letters;
}


function add_field_related_data($ids, $names, $all_meta, $key)
{
    $items = empty($ids) ? [] : array_combine((array) $ids, (array) $names);

    $result = [];

    foreach ($items as $id => $name) {
        $result[] = get_field_related_meta(['id' => $id, 'name' => $name,], $all_meta, $key);
    }

    return $result;
}
