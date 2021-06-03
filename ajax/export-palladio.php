<?php

function export_palladio_data()
{
    if (!array_key_exists('type', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $type = sanitize_text_field($_GET['type']);

    $format = sanitize_text_field($_GET['format']);

    if ($format != 'csv') {
        wp_send_json_error('Format not found', 404);
    }

    $data = get_palladio_data($type);

    array_to_csv_download($data, "export-$type.csv");

    wp_die();
}
add_action('wp_ajax_export_palladio', 'export_palladio_data');


function export_palladio_masaryk_data()
{
    $format = sanitize_text_field($_GET['format']);

    $from = (int) $_GET['from'];

    if ($format != 'csv') {
        wp_send_json_error('Format not found', 404);
    }

    $data = get_masaryk_data();

    if ($from == 1) {
        array_to_csv_download($data['from'], 'export-from-masaryk.csv');
    } else {
        array_to_csv_download($data['to'], 'export-to-masaryk.csv');
    }

    wp_die();
}
add_action('wp_ajax_export_palladio_masaryk', 'export_palladio_masaryk_data');


function get_palladio_data($type)
{
    /*
    * TODO: sloučit úvodní načítání polí s get_letters_basic_meta
    */
    /*
    * needed data:
    *
    * author: First name (A); Last Name (A); Gender (A); Nationality (A); Age (A); Profession (A); Profession category (A);
    * recipient: First name (R); Last name (R); Gender (RA); Nationality (R); Age (R); Profession (R); Profession category (R);
    * letter: Date of dispatch; Place of dispatch; Place of dispatch (coordinates); Place of arrival; Place of arrival (coordinates); Languages; Keywords;Keywords categories; People mentioned; Document type, Preservation, Type of copy
    */

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
    ];

    $fields = implode(', ', [
        't.ID',
        't.date_day',
        't.date_month',
        't.date_year',
        't.languages',
        't.copies',
        'l_author.surname AS a_surname',
        'l_author.forename AS a_forename',
        'l_author.birth_year AS a_birth_year',
        'l_author.profession_detailed AS a_profession',
        'l_author.profession_short AS a_category',
        'l_author.nationality AS a_nationality',
        'l_author.gender AS a_gender',
        'recipient.surname AS r_surname',
        'recipient.forename AS r_forename',
        'recipient.birth_year AS r_birth_year',
        'recipient.profession_detailed AS r_profession',
        'recipient.profession_short AS r_category',
        'recipient.nationality AS r_nationality',
        'recipient.gender AS r_gender',
        'origin.name AS o_name',
        'origin.longitude AS o_longitude',
        'origin.latitude AS o_latitude',
        'dest.name AS d_name',
        'dest.longitude AS d_longitude',
        'dest.latitude AS d_latitude',
        'keywords.name AS keyword',
        'keywords.categories AS kw_categories',
        'people_mentioned.name AS people_mentioned',
    ]);

    $query = "SELECT {$fields}
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
    LEFT JOIN {$prefix['person']} AS people_mentioned ON
        people_mentioned.id = rel_people_mentioned.related_item_id";

    $data = parse_palladio_data(
        $wpdb->get_results($query, ARRAY_A),
        get_professions($post_types['profession'], $post_types['default_lang']),
        list_keywords($post_types['keyword'], 1),
        $post_types['default_lang']
    );

    $order_keys = [
        'Name (A)', 'Gender (A)', 'Nationality (A)', 'Age (A)', 'Profession (A)', 'Profession category (A)',
        'Name (R)', 'Gender (R)', 'Nationality (R)', 'Age (R)', 'Profession (R)', 'Profession category (R)',
        'Date of dispatch', 'Place of dispatch', 'Place of dispatch (coordinates)', 'Place of arrival',
        'Place of arrival (coordinates)', 'Languages', 'Keywords', 'Keywords categories', 'People mentioned',
        'Document type', 'Preservation', 'Type of copy'
    ];

    $ordered_data = [];

    foreach ($data as $index => $row) {
        foreach ($order_keys as $key) {
            $ordered_data[$index][$key] = $row[$key];
        }
    }

    return $ordered_data;
}


function parse_palladio_data($query_result, $professions, $kw_categories, $lang)
{
    $query_result = merge_distinct_query_result($query_result);

    $result = [];

    $to_flat_fields = [
        'a_nationality' => 'Nationality (A)',
        'a_gender' => 'Gender (A)',
        'r_nationality' => 'Nationality (R)',
        'r_gender' => 'Gender (R)',
        'o_name' => 'Place of dispatch',
        'd_name' => 'Place of arrival',
    ];

    foreach ($query_result as $index => $row) {
        $date = $row['date_year'] != 0 ? $row['date_year'] : '';

        if ($row['date_month'] != 0 && !empty($row['date_month']) && $row['date_day'] != 0  && !empty($row['date_day'])) {
            $date .= '-' . $row['date_month'] . '-' . $row['date_day'];
        }

        $result[$index]['Date of dispatch'] = $date;

        $result[$index]['Languages'] = strtolower(str_replace(';', '|', $row['languages']));

        if (is_array($row['keyword'])) {
            $result[$index]['Keywords'] = implode('|', $row['keyword']);
        } else {
            $result[$index]['Keywords'] =  $row['keyword'];
        }

        if (is_array($row['kw_categories'])) {
            $result[$index]['Keywords categories'] = implode('|', array_map(function ($category) use ($kw_categories, $lang) {
                return get_keyword_category_by_name($category, $kw_categories, $lang);
            }, $row['kw_categories']));
        } else {
            $result[$index]['Keywords categories'] = get_keyword_category_by_name($row['kw_categories'], $kw_categories, $lang);
        }

        $result[$index]['Place of dispatch (coordinates)'] = '';
        if (is_array($row['o_latitude']) && is_array($row['o_longitude'])) {
            $result[$index]['Place of dispatch (coordinates)'] = $row['o_latitude'][0] . ', ' . $row['o_longitude'][0];
        } elseif (strlen($row['o_latitude']) != 0 && strlen($row['o_longitude'] != 0)) {
            $result[$index]['Place of dispatch (coordinates)'] = $row['o_latitude'] . ', ' . $row['o_longitude'];
        }

        $result[$index]['Place of arrival (coordinates)'] = '';
        if (is_array($row['d_latitude']) && is_array($row['d_longitude'])) {
            $result[$index]['Place of arrival (coordinates)'] = $row['d_latitude'][0] . ', ' . $row['d_longitude'][0];
        } elseif (strlen($row['d_latitude']) != 0 && strlen($row['d_longitude'] != 0)) {
            $result[$index]['Place of arrival (coordinates)'] = $row['d_latitude'] . ', ' . $row['d_longitude'];
        }

        if (is_array($row['a_surname'])) {
            $result[$index]['Name (A)'] = trim("{$row['a_forename'][0]} {$row['a_surname'][0]}");
        } else {
            $result[$index]['Name (A)'] = trim("{$row['a_forename']} {$row['a_surname']}");
        }

        if (is_array($row['r_surname'])) {
            $result[$index]['Name (R)'] = trim("{$row['r_forename'][0]} {$row['r_surname'][0]}");
        } else {
            $result[$index]['Name (R)'] = trim("{$row['r_forename']} {$row['r_surname']}");
        }

        $result[$index]['Age (A)'] = '';
        if (is_array($row['a_birth_year']) && $row['a_birth_year'][0] != 0 && $row['date_year'] != 0) {
            $result[$index]['Age (A)'] = $row['date_year'] - $row['a_birth_year'][0];
        } elseif (strlen($row['a_birth_year']) != 0 && $row['a_birth_year'][0] != 0 && strlen($row['date_year'] != 0)) {
            $result[$index]['Age (A)'] = $row['date_year'] - $row['a_birth_year'];
        }

        $result[$index]['Age (R)'] = '';
        if (is_array($row['r_birth_year']) && $row['r_birth_year'][0] != 0 && $row['date_year'] != 0) {
            $result[$index]['Age (R)'] = $row['date_year'] - $row['r_birth_year'][0];
        } elseif (strlen($row['r_birth_year']) != 0  && $row['r_birth_year'][0] != 0 && strlen($row['date_year'] != 0)) {
            $result[$index]['Age (R)'] = $row['date_year'] - $row['r_birth_year'];
        }

        if (is_array($row['a_profession'])) {
            $result[$index]['Profession (A)'] =  separate_by_vertibar(parse_professions($row['a_profession'][0], $professions));
        } else {
            $result[$index]['Profession (A)'] =  separate_by_vertibar(parse_professions($row['a_profession'], $professions));
        }

        if (is_array($row['a_category'])) {
            $result[$index]['Profession category (A)'] =  separate_by_vertibar(parse_professions($row['a_category'][0], $professions));
        } else {
            $result[$index]['Profession category (A)'] =  separate_by_vertibar(parse_professions($row['a_category'], $professions));
        }

        if (is_array($row['r_profession'])) {
            $result[$index]['Profession (R)'] = separate_by_vertibar(parse_professions($row['r_profession'][0], $professions));
        } else {
            $result[$index]['Profession (R)'] = separate_by_vertibar(parse_professions($row['r_profession'], $professions));
        }

        if (is_array($row['r_category'])) {
            $result[$index]['Profession category (R)'] =  separate_by_vertibar(parse_professions($row['r_category'][0], $professions));
        } else {
            $result[$index]['Profession category (R)'] =  separate_by_vertibar(parse_professions($row['r_category'], $professions));
        }

        if (is_array($row['people_mentioned'])) {
            $result[$index]['People mentioned'] =  implode('|', $row['people_mentioned']);
        } else {
            $result[$index]['People mentioned'] =  (string) $row['people_mentioned'];
        }

        if (!empty($row['copies'])) {
            $copies = json_decode($row['copies'], true);
            $type = array_column($copies, 'type');
            $preservation = array_column($copies, 'preservation');
            $copy = array_column($copies, 'copy');
        }

        $result[$index]['Document type'] = !isset($type) || empty($type) ? '' : $type[0];
        $result[$index]['Preservation'] = !isset($preservation) || empty($preservation) ? '' : $preservation[0];
        $result[$index]['Type of copy'] = !isset($copy) || empty($copy) ? '' : $copy[0];

        foreach ($row as $field_key => $field) {
            if (array_key_exists($field_key, $to_flat_fields)) {
                if (is_array($field)) {
                    $result[$index][$to_flat_fields[$field_key]] = $field[0];
                } else {
                    $result[$index][$to_flat_fields[$field_key]] = $field;
                }
            }
        }
    }

    return $result;
}


function get_masaryk_data()
{
    $name = get_masaryk_name();

    if (!$name) {
        return false;
    }

    $results = [
        'from' => [],
        'to' => [],
    ];

    $letters = get_palladio_data('tgm');

    foreach ($letters as $l) {
        if ($l['Name (A)'] == $name) {
            $results['from'][] = $l;
        } elseif ($l['Name (R)'] == $name) {
            $results['to'][] = $l;
        }
    }

    return $results;
}


function get_masaryk_name()
{
    $fields = implode(', ', [
        't.forename',
        't.id',
        't.surname',
    ]);

    $data = pods(
        'tgm_person',
        [
            'select' => $fields,
            'where' => 't.id = ' . MASARYK_ID,
        ]
    );

    return trim($data->display('forename') . ' ' . $data->display('surname'));
}
