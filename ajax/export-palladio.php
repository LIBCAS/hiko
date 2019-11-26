<?php

ini_set("xdebug.var_display_max_children", -1);
ini_set("xdebug.var_display_max_data", -1);
ini_set("xdebug.var_display_max_depth", -1);

require_once get_template_directory() . '/vendor/autoload.php';


use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

function get_palladio_data($type)
{
    /*
    * TODO: sloučit úvodní načítání polí s get_letters_basic_meta
    */
    /*
    * needed data:
    *
    * author: First name (A); Lastname (A); Gender (A); Nationality (A); Age (A); Profession (A);
    * recipient: First name (R); Last name (R); Gender (RA); Nationality (R); Age (R); Profession (R);
    * letter: Date of dispatch; Place of dispatch; Place of dispatch (coordinates); Place of arrival; Place of arrival (coordinates); Languages; Keywords
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
    ];

    $prefix = [
        'letter' => "{$wpdb->prefix}pods_{$post_types['letter']}",
        'relation' => "{$wpdb->prefix}podsrel",
        'place' => "{$wpdb->prefix}pods_{$post_types['place']}",
        'person' => "{$wpdb->prefix}pods_{$post_types['person']}",
        'kw' => "{$wpdb->prefix}pods_{$post_types['keyword']}",
    ];

    $fields = [
        't.ID',
        't.date_day',
        't.date_month',
        't.date_year',
        't.languages',
        'l_author.surname AS a_surname',
        'l_author.forename AS a_forename',
        'l_author.birth_year AS a_birth_year',
        'l_author.profession AS a_profession',
        'l_author.nationality AS a_nationality',
        'l_author.gender AS a_gender',
        'recipient.surname AS r_surname',
        'recipient.forename AS r_forename',
        'recipient.birth_year AS r_birth_year',
        'recipient.profession AS r_profession',
        'recipient.nationality AS r_nationality',
        'recipient.gender AS r_gender',
        'origin.name AS o_name',
        'origin.longitude AS o_longitude',
        'origin.latitude AS o_latitude',
        'dest.name AS d_name',
        'dest.longitude AS d_longitude',
        'dest.latitude AS d_latitude',
        'keywords.name AS keyword',
    ];
    $fields = implode(', ', $fields);

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
    ";

    $query_result = $wpdb->get_results($query, ARRAY_A);

    return parse_palladio_data($query_result);
}


function parse_palladio_data($query_result)
{
    $query_result = merge_distinct_query_result($query_result);
    /*
    * TODO: sloučit úvodní načítání polí s get_letters_basic_meta
    */
    /*
    * needed data:
    *
    * author: First name (A); Lastname (A); Gender (A); Nationality (A); Age (A); Profession (A);
    * recipient: First name (R); Last name (R); Gender (RA); Nationality (R); Age (R); Profession (R);
    * letter: Date of dispatch; Place of dispatch; Place of dispatch (coordinates); Place of arrival; Place of arrival (coordinates); Languages; Keywords
    */

    $result = [];

    $to_flat_fields = [
        'a_surname', 'a_forename', 'a_profession', 'a_nationality', 'a_gender',
        'r_surname', 'r_forename', 'r_profession', 'r_nationality', 'r_gender',
        'o_name', 'd_name',
    ];

    $index = 0;
    foreach ($query_result as $row) {

        $date = '';
        $date .= ($row['date_year'] != 0 ? $row['date_year'] : '');
        $date .= ($row['date_month'] != 0 ? '-' . $row['date_month'] : '');
        $date .= ($row['date_day'] != 0 ? '-' . $row['date_day'] : '');

        $result[$index]['date'] = $date;

        $result[$index]['languages'] = str_replace(';', ',', $row['languages']);

        if (is_array($row['keyword'])) {
            $result[$index]['keyword'] = implode(',', $row['keyword']);
        } else {
            $result[$index]['keyword'] =  $row['keyword'];
        }

        $result[$index]['o_gps'] = '';
        if (is_array($row['o_latitude']) && is_array($row['o_longitude'])) {
            $result[$index]['o_gps'] = $row['o_latitude'][0] . ', ' . $row['o_longitude'][0];
        } elseif (strlen($row['o_latitude']) != 0 && strlen($row['o_longitude'] != 0)) {
            $result[$index]['o_gps'] = $row['o_latitude'] . ', ' . $row['o_longitude'];
        }

        $result[$index]['d_gps'] = '';
        if (is_array($row['d_latitude']) && is_array($row['d_longitude'])) {
            $result[$index]['d_gps'] = $row['d_latitude'][0] . ', ' . $row['d_longitude'][0];
        } elseif (strlen($row['d_latitude']) != 0 && strlen($row['d_longitude'] != 0)) {
            $result[$index]['d_gps'] = $row['d_latitude'] . ', ' . $row['d_longitude'];
        }

        foreach ($row as $field_key => $field) {
            if (in_array($field_key, $to_flat_fields)) {
                if (is_array($field)) {
                    $result[$index][$field_key] = $field[0];
                } else {
                    $result[$index][$field_key] = $field;
                }
            }
        }
        $index++;
    }

    return $result;
}


function merge_distinct_query_result($query_result)
{
    $result = [];

    foreach ($query_result as $row) {
        $row = (array) $row;
        if (!array_key_exists($row['ID'], $result)) {
            foreach ($row as $itemKey => $item) {
                if ($itemKey == 'id') {
                    continue;
                }
                $result[$row['ID']][$itemKey] = $item;
            }
        } else {
            $existingRow = $result[$row['ID']];
            foreach ($row as $itemKey => $item) {
                if ($itemKey == 'ID') {
                    continue;
                }

                if (is_string($item) && $item != $existingRow[$itemKey]) {
                    $result[$row['ID']][$itemKey] = [$existingRow[$itemKey], $item];
                } elseif (is_array($existingRow[$itemKey]) && !in_array($item, $existingRow[$itemKey])) {
                    $result[$row['ID']][$itemKey][] = $item;
                }
            }
        }
    }
    return $result;
}
