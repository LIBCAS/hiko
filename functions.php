<?php

add_action('init', function () {
    if (!session_id()) {
        session_start();
    }
}, 1);

require 'data-types.php';

date_default_timezone_set('Europe/Prague');

add_action('after_setup_theme', function () {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
});

remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'start_post_rel_link', 10, 0);
remove_action('wp_head', 'parent_post_rel_link', 10, 0);
remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
remove_action('wp_head', 'rel_canonical', 10, 0);
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('template_redirect', 'rest_output_link_header', 11, 0);
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_resource_hints', 2);
remove_action('welcome_panel', 'wp_welcome_panel');


add_action('wp_print_styles', function () {
    wp_dequeue_style('wp-block-library');
}, 100);


add_action('wp_dashboard_setup', function () {
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');
});


add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
    remove_menu_page('edit.php');
});


function test_input($input)
{
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_NOQUOTES);
    $input = sanitize_text_field($input);
    return $input;
}


function test_postdata($associative_array)
{
    $results = [];
    foreach ($associative_array as $key => $value) {
        if (!empty($_POST[$value])) {
            $results[$key] = test_input($_POST[$value]);
        }
    }

    return $results;
}


function decode_php_input()
{
    return json_decode(
        mb_convert_encoding(
            file_get_contents('php://input'),
            'UTF-8'
        )
    );
}


function alert($message, $type = 'info')
{
    ob_start(); ?>
    <div class="alert alert-<?= $type ?>">
        <?= $message; ?>
    </div>
    <?php
    return ob_get_clean();
}


function frontend_refresh()
{
    ob_start(); ?>
    <script type="text/javascript">
        console.log('refreshed');
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <?php
    echo ob_get_clean();
}


function get_alert_markup($message, $type = 'info')
{
    ob_start(); ?>
    <div x-data="{ visible: true}">
        <div class="alert alert-<?= $type; ?>" role="alert" x-show="visible" style="display:block">
            <?= $message; ?>
            <button type="button" class="close" aria-label="Close" @click="visible = false">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}


function show_alerts()
{
    if (isset($_SESSION['hiko']['success'])) {
        echo get_alert_markup($_SESSION['hiko']['success'], 'success');
        unset($_SESSION['hiko']['success']);
    } else if (isset($_SESSION['hiko']['warning'])) {
        echo get_alert_markup($_SESSION['hiko']['warning'], 'warning');
        unset($_SESSION['hiko']['warning']);
    }
}


function get_form_checkbox_val($name, $array)
{
    if (array_key_exists($name, $array)) {
        return $array[$name] == 'on' ? 1 : 0;
    }
    return 0;
}


function get_shortened_name()
{
    $user_data = get_user_meta(get_current_user_id());
    return $user_data['first_name'][0]  . ' ' . mb_substr($user_data['last_name'][0], 0, 1) . '.';
}


function get_full_name()
{
    $user_data = get_user_meta(get_current_user_id());
    return $user_data['first_name'][0]  . ' ' . $user_data['last_name'][0];
}


function get_places_table_data($place_type)
{
    $fields = [
        't.id',
        't.name AS city',
        't.country',
        't.latitude',
        't.longitude',
        'letter_origin.id AS letter_id',
        'letter_destination.id AS dest_id'
    ];

    $fields = implode(', ', $fields);

    $places = pods(
        $place_type,
        [
            'select' => $fields,
            'orderby' => 't.name ASC',
            'limit' => -1,
            'groupby' => 't.id'
        ]
    );

    $places_filtered = [];

    while ($places->fetch()) {
        $latlong = '';
        if ($places->display('latitude') && $places->display('longitude')) {
            $latlong = $places->display('latitude') . ',' . $places->display('longitude');
        }

        $places_filtered[] = [
            'id' => $places->display('id'),
            'city' => $places->display('city'),
            'country' => $places->display('country'),
            'latlong' => $latlong,
            'relationships' => !is_null($places->display('letter_id')) || !is_null($places->display('dest_id'))
        ];
    }

    return $places_filtered;
}


function has_user_permission($role)
{
    if (!is_user_logged_in()) {
        return false;
    }

    $roles = (array) wp_get_current_user()->roles;

    if (!in_array($role, $roles) && !in_array('administrator', $roles)) {
        return false;
    }

    return true;
}


function is_in_editor_role()
{
    if (!is_user_logged_in()) {
        return false;
    }

    $result = array_intersect(
        (array) wp_get_current_user()->roles,
        fetch_types()['editors']
    );

    return count($result) > 0 ? true : false;
}


function verify_upload_img($img)
{
    if (!file_exists($img['tmp_name'][0]) || !is_uploaded_file($img['tmp_name'][0])) {
        return 'No upload';
    }
    if ($img['size'][0] > 1000000) {
        return 'Exceeded filesize limit.';
    }

    $img_info = getimagesize($img['tmp_name'][0]);

    if ($img_info['mime'] != 'image/jpeg') {
        return 'Not valid jpg';
    }

    return true;
}


function merge_unique($array1, $array2)
{
    if ($array1 == null) {
        return array_unique($array2);
    }

    $merged = array_merge($array1, $array2);

    return array_unique($merged);
}


function get_letters_basic_meta($meta, $draft)
{
    global $wpdb;

    $podsAPI = new PodsAPI();
    $pod = $podsAPI->load_pod(['name' => $meta['letter']]);
    $author_field_id = $pod['fields']['l_author']['id'];
    $recipient_field_id = $pod['fields']['recipient']['id'];
    $origin_field_id = $pod['fields']['origin']['id'];
    $dest_field_id = $pod['fields']['dest']['id'];
    $keyword_field_id = $pod['fields']['keywords']['id'];
    $img_field_id = $pod['fields']['images']['id'];

    $l_prefix = "{$wpdb->prefix}pods_{$meta['letter']}";
    $r_prefix = "{$wpdb->prefix}podsrel";
    $pl_prefix = "{$wpdb->prefix}pods_{$meta['place']}";
    $pe_prefix = "{$wpdb->prefix}pods_{$meta['person']}";
    $kw_prefix = "{$wpdb->prefix}pods_{$meta['keyword']}";

    $fields = [
        't.id AS ID',
        't.date_day',
        't.date_month',
        't.date_year',
        't.copies',
        't.status',
        't.created',
        'l_author.name AS author',
        'recipient.name AS recipient',
        'origin.name AS origin',
        'dest.name AS dest',
        $meta['default_lang'] === 'en' ? 'keyword.name AS keyword' : 'keyword.namecz AS keyword',
        'keyword.categories AS category',
        'posts.ID as images'
    ];

    $fields = implode(', ', $fields);

    $draft_condition = $draft ? '' : 'WHERE t.status = \'publish\'';

    $user_name = get_full_name();

    $query = "
    SELECT
    LOCATE('{$user_name}', t.history) AS my_letter,
    {$fields}
    FROM
    $l_prefix AS t
    LEFT JOIN {$r_prefix} AS rel_l_author ON rel_l_author.field_id = {$author_field_id}
    AND rel_l_author.item_id = t.id
    LEFT JOIN {$pe_prefix} AS l_author ON l_author.id = rel_l_author.related_item_id
    LEFT JOIN {$r_prefix} AS rel_img ON rel_img.field_id = {$img_field_id}
    AND rel_img.item_id = t.id
    LEFT JOIN {$wpdb->prefix}posts AS posts ON posts.ID = rel_img.related_item_id
    LEFT JOIN {$r_prefix} AS rel_recipient ON rel_recipient.field_id = {$recipient_field_id}
    AND rel_recipient.item_id = t.id
    LEFT JOIN {$pe_prefix} AS recipient ON recipient.id = rel_recipient.related_item_id
    LEFT JOIN {$r_prefix} AS rel_origin ON rel_origin.field_id = {$origin_field_id}
    AND rel_origin.item_id = t.id
    LEFT JOIN {$pl_prefix} AS origin ON origin.id = rel_origin.related_item_id
    LEFT JOIN {$r_prefix} AS rel_dest ON rel_dest.field_id = {$dest_field_id}
    AND rel_dest.item_id = t.id
    LEFT JOIN {$pl_prefix} AS dest ON dest.id = rel_dest.related_item_id
    LEFT JOIN {$r_prefix} AS rel_keyword ON rel_keyword.field_id = {$keyword_field_id}
    AND rel_keyword.item_id = t.id
    LEFT JOIN {$kw_prefix} AS keyword ON keyword.id = rel_keyword.related_item_id
    {$draft_condition}
    ORDER BY
    t.created DESC,
    t.name,
    t.id
    ";

    return $wpdb->get_results($query, ARRAY_A);
}


function get_letters_history($letter_type)
{
    $fields = implode(', ', [
        't.id AS ID',
        't.history',
    ]);

    $letters = pods(
        $letter_type,
        [
            'select' => $fields,
            'limit' => -1,
        ]
    );

    $result = [];

    while ($letters->fetch()) {
        $result[] = [
            'ID' => $letters->display('ID'),
            'editors' => get_editors_from_history($letters->display('history')),
        ];
    }

    return $result;
}


function get_editors_from_history($history)
{
    $editors = [];

    $lines = explode("\n", $history);

    foreach ($lines as $line) {
        $name = explode(' – ', $line)[1];

        if (!in_array($name, $editors)) {
            $editors[] = $name;
        }
    }

    return $editors;
}


function get_letters_basic_meta_filtered($meta, $draft = true, $history = false)
{
    $filtered_letters = merge_distinct_query_result(
        get_letters_basic_meta($meta, $draft)
    );

    $letters = array_values($filtered_letters);

    if (!$history) {
        return $letters;
    }

    $history = get_letters_history($meta['letter']);

    $result = [];

    foreach ($letters as $letter) {
        $letter_history = array_filter($history, function ($h) use ($letter) {
            return ($h['ID'] == $letter['ID']);
        });

        $signature = [];
        $repository = [];
        if (!empty($letter['copies'])) {
            $copies = json_decode($letter['copies'], true);
            $signature = array_column($copies, 'signature');
            $repository = array_column($copies, 'repository');
        }

        $letter['repository'] = empty($repository) ? '' : $repository[0];
        $letter['signature'] = empty($signature) ? '' : $signature[0];
        $letter['editors'] = array_values($letter_history)[0]['editors'];
        $result[] = $letter;
    }

    return $result;
}


function get_hiko_post_types($single_type)
{
    $data = fetch_types();

    if (!isset($data['types'][$single_type])) {
        return [];
    }

    return $data['types'][$single_type];
}


function output_current_type_script($type)
{
    $type_formatted = [
        'defaultLanguage' => $type['default_lang'],
        'keyword' => $type['keyword'],
        'letterType' => $type['letter'],
        'path' => $type['path'],
        'personType' => $type['person'],
        'placeType' => $type['place'],
        'profession' => $type['profession'],
    ];

    ob_start(); ?>
    <script id="datatype" type="application/json">
        <?= json_encode($type_formatted, JSON_UNESCAPED_UNICODE); ?>
    </script>
    <?php echo ob_get_clean();
};


function get_hiko_post_types_by_url($url = '')
{
    $req = $url != '' ? $url : $_SERVER['REQUEST_URI'];

    $datatypes = fetch_types();

    $type = [];

    foreach (array_keys($datatypes['types']) as $key) {
        if (strpos($req, $key) !== false) {
            $type = get_hiko_post_types($key);
            break;
        }
    }

    return $type;
}


function get_types_by_letter()
{
    $types = fetch_types()['types'];

    $result = [];
    foreach ($types as $type_key => $values) {
        $values['handle'] = $type_key;
        $result[$values['letter']] = $values;
    }

    return $result;
}


function get_letter_single_field($type, $id, $field_name)
{
    $pod = pods(
        $type,
        [
            'where' => "t.id = '{$id}'",
            'select' => implode(', ', [
                "t.{$field_name}",
                't.ID'
            ])
        ]
    );

    while ($pod->fetch()) {
        if (!$pod->exists()) {
            return false;
        }

        return $pod->display($field_name);
    }
}


function get_letter_history($type, $id)
{
    return get_letter_single_field($type, $id, 'history');
}


function get_letter_created($type, $id)
{
    $author = '';
    $fields = [
        "t.created AS time",
        't.ID',
        'author.id AS author'
    ];

    $fields = implode(', ', $fields);

    $pod = pods(
        $type,
        [
            'where' => "t.id = '{$id}'",
            'select' => $fields
        ]
    );

    while ($pod->fetch()) {
        if (!$pod->exists()) {
            return false;
        }
        $author = get_user_meta($pod->display('author'));

        return [
            'date' => $pod->display('time'),
            'author' => $author['first_name'][0]  . ' ' . $author['last_name'][0],
        ];
    }
}


function sanitize_slashed_json($data)
{
    $result = [];

    $data = json_decode(stripslashes($data));

    foreach ($data as $key => $value) {
        $temp = [];

        foreach ($value as $sec_key => $sec_val) {
            if ($sec_key != 'key') {
                $temp[test_input($sec_key)] = test_input($sec_val);
            }
        }

        $result[] = $temp;
    }

    return $result;
}


function get_gmdate($filepath = false)
{
    $timestamp = time();

    if ($filepath) {
        $timestamp = filemtime($filepath);
    }

    return gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
}


function hiko_sanitize_file_name($file)
{
    $file = remove_accents($file);

    $file = sanitize_file_name($file);

    return $file;
}


function get_ssl_file($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


function merge_distinct_query_result($query_result)
{
    $result = [];

    foreach ($query_result as $row) {
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
                    $result[$row['ID']][$itemKey] = [];

                    if (!is_array($existingRow[$itemKey])) {
                        if (!in_array($existingRow[$itemKey], $result[$row['ID']][$itemKey])) {
                            $result[$row['ID']][$itemKey][] = $existingRow[$itemKey];
                        }
                    } else {
                        foreach ($existingRow[$itemKey] as $val) {
                            if (!in_array($val, $result[$row['ID']][$itemKey])) {
                                $result[$row['ID']][$itemKey][] = $val;
                            }
                        }
                    }

                    if (!in_array($item, $result[$row['ID']][$itemKey])) {
                        $result[$row['ID']][$itemKey][] = $item;
                    }
                }
            }
        }
    }

    return $result;
}


function array_to_csv_download($array, $filename = "export.csv", $delimiter = ";", $enclosure = '"')
{
    $f = fopen('php://memory', 'w');

    fputs($f, "\xEF\xBB\xBF");

    fputcsv($f, array_keys($array[0]), $delimiter);

    foreach ($array as $line) {
        fputcsv($f, $line, $delimiter);
    }

    fseek($f, 0);

    header('Content-Type: application/csv');
    header('Content-Encoding: UTF-8');
    header('Content-type: text/csv; charset=UTF-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Content-Disposition: attachment; filename="' . $filename . '";');

    fpassthru($f);
}


function separate_by_vertibar($str)
{
    $str = str_replace(';', '|', $str);
    $str = str_replace(',', '|', $str);
    $str = str_replace('| ', '|', $str);
    return $str;
}


function get_editors_by_role($role)
{
    return get_users([
        'role' => $role,
        'meta_key' => 'last_name',
        'orderby' => 'meta_value',
        'order' => 'asc',
    ]);
}

function get_languages()
{
    $langs = json_decode(
        get_ssl_file(get_template_directory_uri() . '/assets/data/languages.json'),
        true
    );

    return array_column(array_values($langs), 'name');
}


function input_value($form_data, $field)
{
    return isset($form_data[$field]) ? $form_data[$field] : '';
}


function input_value_list($form_data, $field)
{
    return isset($form_data[$field]) ? implode(';', $form_data[$field]) : '';
}


function input_json_value($form_data, $field)
{
    if (!isset($form_data[$field]) || empty($form_data[$field])) {
        return '[]';
    }

    $results = [];

    foreach ($form_data[$field] as $item) {
        $results[] = [
            'id' => $item['id'],
            'value' => $item['name'],
        ];
    }

    return htmlspecialchars(json_encode($results, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}


function input_bool($form_data, $field)
{
    if (!isset($form_data[$field])) {
        return '';
    }

    return $form_data[$field] ? 'checked' : '';
}


add_image_size('xl-thumb', 300);

require 'ajax/common.php';
require 'helpers/letters.php';
require 'helpers/professions.php';
require 'helpers/entities.php';
require 'helpers/places.php';
require 'ajax/images.php';
require 'ajax/export.php';
require 'ajax/export-palladio.php';
require 'helpers/location.php';
require 'ajax/geonames.php';
require 'helpers/keywords.php';
