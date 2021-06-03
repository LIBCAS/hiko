<?php

add_action('wp_ajax_export_palladio_tgm', function () {
    array_to_csv_download(get_palladio_tgm_data(), 'palladio-tgm.csv');

    wp_die();
});


function get_palladio_tgm_data()
{
    /*
    Age (M): věk Masaryka u přijaté i odeslané korespondence
    Name (O): jméno korespondenčního partnera
    Gender (O): pohlaví korespondenčního partnera
    Nationality (O): národnost korespondenčního partnera
    Age (O): věk korespondenčního partnera
    Profession (O): profese korespondenčního partnera
    Profession category (O): kategorie profesí korespondenčního partnera
    Date of dispatch: datum odeslání dopisu
    Year of dispatch: rok odeslání dopisu
    Place of M: místo pobytu Masaryka
    Place of M (coordinates): to samé jako výše
    Place of O: místo korespondenčního partnera
    Place of O (coordinates): to samé jako výše
    Languages, Keywords, Keywords categories, People mentioned, Document type, Preservation, Type of copy,
    Received/Sent
    */

    global $wpdb;

    $post_types = get_hiko_post_types('tgm');

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

    $data = parse_palladio_tgm_data(
        $wpdb->get_results($query, ARRAY_A),
        get_professions($post_types['profession'], $post_types['default_lang']),
        list_keywords($post_types['keyword'], 1),
        $post_types['default_lang']
    );

    $order_keys = [
        'Age (M)', 'Name (O)', 'Gender (O)', 'Nationality (O)', 'Age (O)', 'Profession (O)', 'Profession category (O)', 'Date of dispatch', 'Year of dispatch', 'Place of M', 'Place of M (coordinates)', 'Place of O', 'Place of O (coordinates)', 'Languages', 'Keywords', 'Keywords categories',
        'People mentioned', 'Document type', 'Preservation', 'Type of copy', 'Received/Sent'
    ];

    $ordered_data = [];

    foreach ($data as $index => $row) {
        foreach ($order_keys as $key) {
            $ordered_data[$index][$key] = $row[$key];
        }
    }

    return $ordered_data;
}


function parse_palladio_tgm_data($query_result, $professions, $kw_categories, $lang)
{
    $query_result = merge_distinct_query_result($query_result);

    $result = [];

    $tgm_name = get_masaryk_name();

    foreach ($query_result as $index => $row) {
        $author = get_first_person_name($row['a_surname'], $row['a_forename']);
        $recipient = get_first_person_name($row['r_surname'], $row['r_forename']);

        if ($tgm_name !== $author && $tgm_name !== $recipient) {
            continue;
        }

        $date = $row['date_year'] != 0 ? $row['date_year'] : '';

        if ($row['date_month'] != 0 && !empty($row['date_month']) && $row['date_day'] != 0  && !empty($row['date_day'])) {
            $date .= '-' . $row['date_month'] . '-' . $row['date_day'];
        }

        $result[$index]['Date of dispatch'] = $date;
        $result[$index]['Year of dispatch'] = $row['date_year'] != 0 ? $row['date_year'] : '';
        $result[$index]['Languages'] = strtolower(str_replace(';', '|', $row['languages']));
        $result[$index]['Keywords'] = is_array($row['keyword']) ? implode('|', $row['keyword']) : $row['keyword'];

        if (is_array($row['kw_categories'])) {
            $result[$index]['Keywords categories'] = implode('|', array_map(function ($category) use ($kw_categories, $lang) {
                return get_keyword_category_by_name($category, $kw_categories, $lang);
            }, $row['kw_categories']));
        } else {
            $result[$index]['Keywords categories'] = get_keyword_category_by_name($row['kw_categories'], $kw_categories, $lang);
        }

        $result[$index]['People mentioned'] = is_array($row['people_mentioned']) ? implode('|', $row['people_mentioned']) : (string) $row['people_mentioned'];

        if (!empty($row['copies'])) {
            $copies = json_decode($row['copies'], true);
            $type = array_column($copies, 'type');
            $preservation = array_column($copies, 'preservation');
            $copy = array_column($copies, 'copy');
        }

        $result[$index]['Document type'] = !isset($type) || empty($type) ? '' : $type[0];
        $result[$index]['Preservation'] = !isset($preservation) || empty($preservation) ? '' : $preservation[0];
        $result[$index]['Type of copy'] = !isset($copy) || empty($copy) ? '' : $copy[0];

        if ($tgm_name === $author) {
            $result[$index]['Age (M)'] = get_person_age($row['a_birth_year'], $row['date_year']);
            $result[$index]['Age (O)'] = get_person_age($row['r_birth_year'], $row['date_year']);
            $result[$index]['Nationality (O)'] = is_array($row['r_nationality']) ? $row['r_nationality'][0] : $row['r_nationality'];
            $result[$index]['Gender (O)'] = is_array($row['r_gender']) ? $row['r_gender'][0] : $row['r_gender'];
            $result[$index]['Name (O)'] = $recipient;
            $result[$index]['Profession (O)'] = get_person_separated_professions($row['r_profession'], $professions);
            $result[$index]['Profession category (O)'] = get_person_separated_professions($row['r_category'], $professions);
            $result[$index]['Place of M (coordinates)'] = get_place_coordinates($row['o_latitude'], $row['o_longitude']);
            $result[$index]['Place of O (coordinates)'] = get_place_coordinates($row['d_latitude'], $row['d_longitude']);
            $result[$index]['Place of M'] = is_array($row['o_name']) ? $row['o_name'][0] : $row['o_name'];
            $result[$index]['Place of O'] = is_array($row['d_name']) ? $row['d_name'][0] : $row['d_name'];
            $result[$index]['Received/Sent'] = 'Sent';
        } else {
            $result[$index]['Age (O)'] = get_person_age($row['a_birth_year'], $row['date_year']);
            $result[$index]['Age (M)'] = get_person_age($row['r_birth_year'], $row['date_year']);
            $result[$index]['Nationality (O)'] = is_array($row['a_nationality']) ? $row['a_nationality'][0] : $row['a_nationality'];
            $result[$index]['Gender (O)'] = is_array($row['a_gender']) ? $row['a_gender'][0] : $row['a_gender'];
            $result[$index]['Name (O)'] = $author;
            $result[$index]['Profession (O)'] = get_person_separated_professions($row['a_profession'], $professions);
            $result[$index]['Profession category (O)'] = get_person_separated_professions($row['a_category'], $professions);
            $result[$index]['Place of O (coordinates)'] = get_place_coordinates($row['o_latitude'], $row['o_longitude']);
            $result[$index]['Place of M (coordinates)'] = get_place_coordinates($row['d_latitude'], $row['d_longitude']);
            $result[$index]['Place of O'] = is_array($row['o_name']) ? $row['o_name'][0] : $row['o_name'];
            $result[$index]['Place of M'] = is_array($row['d_name']) ? $row['d_name'][0] : $row['d_name'];
            $result[$index]['Received/Sent'] = 'Received';
        }
    }

    return array_values($result);
}


function get_person_age($birth, $year)
{
    if (is_array($birth) && $birth[0] != 0 && $year != 0) {
        return $year - $birth[0];
    }

    if (strlen($birth) != 0 && $birth[0] != 0 && strlen($year != 0)) {
        return $year - $birth;
    }

    return '';
}


function get_person_separated_professions($person_professions, $professions)
{
    if (is_array($person_professions)) {
        return  separate_by_vertibar(parse_professions($person_professions[0], $professions));
    }

    return separate_by_vertibar(parse_professions($person_professions, $professions));
}


function get_place_coordinates($latitude, $longitude)
{
    if (is_array($latitude) && is_array($longitude)) {
        return $latitude[0] . ', ' . $longitude[0];
    }

    if (strlen($latitude) != 0 && strlen($longitude != 0)) {
        return $latitude . ', ' . $longitude;
    }

    return '';
}

function get_first_person_name($surname, $forename)
{
    $name = '';
    $name .= is_array($forename) ? $forename[0] : (string) $forename;
    $name .= ' ';
    $name .= is_array($surname) ? $surname[0] : (string) $surname;

    return trim($name);
}

