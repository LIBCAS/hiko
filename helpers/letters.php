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
        'notes_public',
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
                    $meta = get_field_related_meta($meta, json_decode($pod->field('places_meta'), true));
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
            $result['related_resources'] = !isset($value['title']) ||empty($value['title']) ? [] : $value;
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


function get_field_related_meta($field_meta, $meta)
{
    if (empty($meta)) {
        return $field_meta;
    }

    $meta_index = array_search((string) $field_meta['id'], array_column($meta, 'id'));

    if ($meta_index !== false) {
        return array_merge($field_meta, $meta[$meta_index]);
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
            $data = [ 'names' => [$person['marked']] ];
        } else {
            $old_data = json_decode($person_meta);
            $data = [
                'names' => merge_unique($old_data->names, [ $person['marked'] ])
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
        $signature = $letter['signature'];
        $signature .= $letter['signature'] && $letter['repository'] ? '/' : '';
        $signature .= $letter['repository'] ? $letter['repository'] : '';
        $results[] = [
            'ID' => $letter['ID'],
            'author' => $letter['author'],
            'category' => $letter['category'],
            'date_formatted' => format_letter_date($letter['date_day'], $letter['date_month'], $letter['date_year']),
            'dest' => $letter['dest'],
            'editors' => $letter['editors'],
            'keyword' => $letter['keyword'],
            'images' => (empty($letter['images'])) ? 0 : count($letter['images']),
            'my_letter' => $letter['my_letter'],
            'origin' => $letter['origin'],
            'recipient' => $letter['recipient'],
            'signature' => $letter['signature'],
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
