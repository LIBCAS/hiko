<?php

function get_letter($types, $id, $lang, $private, $image)
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
            $result['related_resources'] = empty($value) ? [] : json_decode($value, true);
            continue;
        }

        if (in_array($key, $bool_fields)) {
            $result[$key] = (bool) $value;
            continue;
        }

        if ($key === 'images' && $image) {
            /* TODO handle images */
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
        is_user_logged_in(),
        true
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
