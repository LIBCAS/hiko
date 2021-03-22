<?php

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
        $results[$key] = test_input($_POST[$value]);
    }

    return $results;
}


function alert($message, $type = 'info')
{
    ob_start();
    ?>
    <div class="alert alert-<?= $type ?>">
        <?= $message; ?>
    </div>
    <?php
    return ob_get_clean();
}


function frontend_refresh()
{
    ob_start();
    ?>
    <script type="text/javascript">
        console.log('refreshed');
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <?php
    echo ob_get_clean();
}


function get_array_name($value)
{
    return is_array($value) ? $value['name'] : '';
}


function get_nonempty_value($value)
{
    return $value !== '';
}


function get_form_checkbox_val($name, $array)
{
    if (array_key_exists($name, $array)) {
        return $array[$name] == 'on' ? 1 : 0;
    }
    return 0;
}


function get_related_name($related_field)
{
    $names = [];

    if (empty($related_field)) {
        return [];
    }

    foreach ($related_field as $field) {
        $names[] = $field['name'];
    }

    return $names;
}


function user_has_role($role)
{
    $user = wp_get_current_user();
    if (in_array($role, (array) $user->roles)) {
        return true;
    }

    return false;
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


function get_persons_table_data($person_type)
{
    $fields = [
        'letter_author.id AS au',
        'letter_people_mentioned.id AS pm',
        'letter_recipient.id AS re',
        't.birth_year',
        't.death_year',
        't.profession_detailed',
        't.profession_short',
        't.id',
        't.name',
        't.persons_meta',
        't.type',
    ];

    $fields = implode(', ', $fields);

    $persons = pods(
        $person_type,
        [
            'select' => $fields,
            'orderby' => 't.name ASC',
            'limit' => -1,
            'groupby' => 't.id'
        ]
    );

    $persons_filtered = [];
    $index = 0;
    while ($persons->fetch()) {
        $alternative_names = json_decode($persons->display('persons_meta'));

        if ($alternative_names && array_key_exists('names', $alternative_names)) {
            $alternative_names = $alternative_names->names;
        } else {
            $alternative_names = [];
        }

        $type = $persons->display('type');
        if (empty($type)) {
            $type = 'person';
        }

        $persons_filtered[$index]['id'] = $persons->display('id');
        $persons_filtered[$index]['name'] = $persons->display('name');
        $persons_filtered[$index]['birth'] = $persons->field('birth_year');
        $persons_filtered[$index]['death'] = $persons->field('death_year');
        $persons_filtered[$index]['profession_short'] = $persons->display('profession_short');
        $persons_filtered[$index]['profession_detailed'] = $persons->display('profession_detailed');
        $persons_filtered[$index]['type'] = $type;
        $persons_filtered[$index]['alternatives'] = $alternative_names;
        $persons_filtered[$index]['relationships'] = !is_null($persons->display('au')) || !is_null($persons->display('re')) || !is_null($persons->display('pm'));

        $index++;
    }

    return $persons_filtered;
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


function get_pods_name_and_id($type, $person = false)
{
    $fields = [
        't.id',
        't.name',
    ];

    if ($person) {
        $fields[] = 't.birth_year';
        $fields[] = 't.death_year';
        $fields[] = 't.type';
    }

    $fields = implode(', ', $fields);

    $pod = pods(
        $type,
        [
            'select' => $fields,
            'orderby' => 't.name ASC',
            'limit' => -1
        ]
    );

    $result = [[
        'id' => '',
        'name' => '',
    ]];

    if ($pod->data()) {
        $result = $pod->data();
    } elseif ($person) {
        $result = [[
            'id' => '',
            'name' => '',
            'birth_year' => '',
            'death_year' => '',
            'type' => ''
        ]];
    }

    /* convert to array of arrays instead objects */
    $result = json_encode($result);
    $result = json_decode($result, true);
    return $result;
}


function parse_json_file($url)
{
    $file = file_get_contents($url);
    $file = json_decode($file);
    return $file;
}


function sum_array_length($array)
{
    $sum = 0;
    foreach ($array as $el) {
        if (is_array($el)) {
            if (count($el) > 0) {
                $sum++;
            }
        }
    }
    return $sum;
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
        json_decode(get_ssl_file(get_template_directory_uri() . '/assets/data/data-types.json'), true)['editors']
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


function save_name_alternatives($persons_string, $person_type)
{
    $persons = json_decode(stripslashes($persons_string));
    foreach ($persons as $person) {
        $person_meta = pods_field($person_type, $person->id, 'persons_meta');
        $data = false;

        if ($person->marked == '') {
            $data = false;
        } elseif ($person_meta == null) {
            $data = [
                'names' => [$person->marked]
            ];
        } else {
            $old_data = json_decode($person_meta);
            $data = [
                'names' => merge_unique($old_data->names, [$person->marked])
            ];
        }

        if ($data) {
            pods_api()->save_pod_item([
                'pod' => $person_type,
                'data' => [
                    'persons_meta' => json_encode($data, JSON_UNESCAPED_UNICODE)
                ],
                'id' => $person->id
            ]);
        }
    }
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
        't.signature',
        't.repository',
        't.date_day',
        't.date_month',
        't.date_year',
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


function get_all_objects_by_id($object, $v)
{
    $found = [];
    foreach ($object as $o) {
        if ($o->id == $v) {
            $found[] = $o;
        }
    }
    return $found;
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

        $letter['editors'] = array_values($letter_history)[0]['editors'];

        $result[] = $letter;
    }

    return $result;
}


function get_hiko_post_types($single_type)
{
    $data = json_decode(
        get_ssl_file(get_template_directory_uri() . '/assets/data/data-types.json'),
        true
    );

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

    $datatypes = json_decode(
        get_ssl_file(get_template_directory_uri() . '/assets/data/data-types.json'),
        true
    );

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
    $types = json_decode(get_ssl_file(get_template_directory_uri() . '/assets/data/data-types.json'), true)['types'];

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

    return json_encode($result, JSON_UNESCAPED_UNICODE);
}


function save_hiko_letter($letter_type, $action, $path)
{
    $types = get_hiko_post_types($path);
    $people_mentioned = [];
    $authors = [];
    $recipients = [];
    $origins = [];
    $destinations = [];
    $keywords = [];

    if (array_key_exists('l_author', $_POST)) {
        foreach ($_POST['l_author'] as $author) {
            $authors[] = test_input($author);
        }
    }

    if (array_key_exists('recipient', $_POST)) {
        foreach ($_POST['recipient'] as $recipient) {
            $recipients[] = test_input($recipient);
        }
    }

    if (array_key_exists('origin', $_POST)) {
        foreach ($_POST['origin'] as $o) {
            $origins[] = test_input($o);
        }
    }

    if (array_key_exists('dest', $_POST)) {
        foreach ($_POST['dest'] as $d) {
            $destinations[] = test_input($d);
        }
    }

    if (array_key_exists('people_mentioned', $_POST)) {
        $people_mentioned = explode(',', $_POST['people_mentioned']);
    }

    if (array_key_exists('keywords', $_POST)) {
        $keywords = explode(';', $_POST['keywords']);
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

    $participant_meta = sanitize_slashed_json($_POST['authors_meta']);

    $data = test_postdata([
        'abstract' => 'abstract',
        'archive' => 'archive',
        'author_note' => 'author_note',
        'collection' => 'collection',
        'date_day' => 'date_day',
        'date_marked' => 'date_marked',
        'date_month' => 'date_month',
        'date_note' => 'date_note',
        'date_year' => 'date_year',
        'dest_note' => 'dest_note',
        'explicit' => 'explicit',
        'incipit' => 'incipit',
        'l_number' => 'l_number',
        'languages' => 'languages',
        'location_note' => 'location_note',
        'manifestation_notes' => 'manifestation_notes',
        'ms_manifestation' => 'ms_manifestation',
        'name' => 'description',
        'notes_private' => 'notes_private',
        'notes_public' => 'notes_public',
        'origin_note' => 'origin_note',
        'people_mentioned_notes' => 'people_mentioned_notes',
        'range_day' => 'range_day',
        'range_month' => 'range_month',
        'range_year' => 'range_year',
        'recipient_notes' => 'recipient_notes',
        'repository' => 'repository',
        'signature' => 'signature',
        'status' => 'status',
    ]);

    $data['author_inferred'] = get_form_checkbox_val('author_inferred', $_POST);
    $data['author_uncertain'] = get_form_checkbox_val('author_uncertain', $_POST);
    $data['authors_meta'] = $participant_meta;
    $data['date_approximate'] = get_form_checkbox_val('date_approximate', $_POST);
    $data['date_inferred'] = get_form_checkbox_val('date_inferred', $_POST);
    $data['date_is_range'] = get_form_checkbox_val('date_is_range', $_POST);
    $data['date_uncertain'] = get_form_checkbox_val('date_uncertain', $_POST);
    $data['dest'] = $destinations;
    $data['dest_inferred'] = get_form_checkbox_val('dest_inferred', $_POST);
    $data['dest_uncertain'] = get_form_checkbox_val('dest_uncertain', $_POST);
    $data['document_type'] = sanitize_slashed_json($_POST['document_type']);
    $data['history'] = $history;
    $data['keywords'] = $keywords;
    $data['l_author'] = $authors;
    $data['origin'] = $origins;
    $data['origin_inferred'] = get_form_checkbox_val('origin_inferred', $_POST);
    $data['origin_uncertain'] = get_form_checkbox_val('origin_uncertain', $_POST);
    $data['people_mentioned'] = $people_mentioned;
    $data['places_meta'] = sanitize_slashed_json($_POST['places_meta']);
    $data['recipient'] = $recipients;
    $data['recipient_inferred'] = get_form_checkbox_val('recipient_inferred', $_POST);
    $data['recipient_uncertain'] = get_form_checkbox_val('recipient_uncertain', $_POST);
    $data['related_resources'] = sanitize_slashed_json($_POST['related_resources']);

    $new_pod = '';

    if ($action == 'new') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => $letter_type,
            'data' => $data
        ]);
    } elseif ($action == 'edit') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => $letter_type,
            'data' => $data,
            'id' => $_GET['edit']
        ]);
    }

    if ($new_pod == '') {
        return alert('Něco se pokazilo', 'warning');
    }

    if (is_wp_error($new_pod)) {
        return alert($new_pod->get_error_message(), 'warning');
    }

    save_name_alternatives($participant_meta, $types['person']);
    frontend_refresh();

    return alert('Uloženo', 'success');
}


function get_gmdate($filepath = false)
{
    $timestamp = time();

    if ($filepath) {
        $timestamp = filemtime($filepath);
    }

    return gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
}


function save_hiko_person($person_type, $action)
{
    $data = test_postdata([
        'birth_year' => 'birth_year',
        'death_year' => 'death_year',
        'emlo' => 'emlo',
        'forename' => 'forename',
        'gender' => 'gender',
        'name' => 'fullname',
        'nationality' => 'nationality',
        'note' => 'note',
        'profession' => 'profession',
        'profession_detailed' => 'profession_detailed',
        'profession_short' => 'profession_short',
        'surname' => 'surname',
        'type' => 'type',
    ]);

    $new_pod = '';

    if ($action == 'new') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => $person_type,
            'data' => $data
        ]);
    } elseif ($action == 'edit') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => $person_type,
            'data' => $data,
            'id' => $_GET['edit']
        ]);
    }

    if ($new_pod == '') {
        return alert('Něco se pokazilo', 'warning');
    }

    if (is_wp_error($new_pod)) {
        return alert($new_pod->get_error_message(), 'warning');
    }

    frontend_refresh();

    return alert('Uloženo', 'success');
}


function save_hiko_place($place_type, $action)
{
    $data = test_postdata([
        'country' => 'country',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'name' => 'place',
        'note' => 'note',
    ]);

    $new_pod = '';

    if ($action == 'new') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => $place_type,
            'data' => $data
        ]);
    } elseif ($action == 'edit') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => $place_type,
            'data' => $data,
            'id' => $_GET['edit']
        ]);
    }

    if ($new_pod == '') {
        return alert('Něco se pokazilo', 'warning');
    }

    if (is_wp_error($new_pod)) {
        return alert($new_pod->get_error_message(), 'warning');
    }

    frontend_refresh();

    return alert('Uloženo', 'success');
}


function hiko_sanitize_file_name($file)
{
    $file = remove_accents($file);

    $file = sanitize_file_name($file);

    return $file;
}


function get_languages()
{
    return json_decode(
        get_ssl_file(get_template_directory_uri() . '/assets/data/languages.json')
    );
}

function get_json_languages()
{
    $languages = get_ssl_file(get_template_directory_uri() . '/assets/data/languages.json');
    ob_start();
    ?>
    <script id="languages" type="application/json">
        <?= $languages; ?>
    </script>
    <?php
    return ob_get_clean();
}


function get_json_countries()
{
    $countries = get_ssl_file(get_template_directory_uri() . '/assets/data/countries.json');
    ob_start();
    ?>
    <script id="countries" type="application/json">
        <?= $countries; ?>
    </script>
    <?php
    return ob_get_clean();
}


function display_persons_and_places($person_type, $place_type)
{
    $persons = json_encode(
        get_pods_name_and_id($person_type, true),
        JSON_UNESCAPED_UNICODE
    );

    $places = list_places_simple($place_type, false);

    ob_start();
    ?>
    <script id="people" type="application/json">
        <?= $persons; ?>
    </script>

    <script id="places" type="application/json">
        <?= $places; ?>
    </script>

    <?php
    return ob_get_clean();
}


function create_hiko_json_cache($name, $json_data)
{
    $cache_folder = WP_CONTENT_DIR . '/hiko-cache';
    $filename = "{$name}.json";

    if (!file_exists($cache_folder)) {
        wp_mkdir_p($cache_folder);
    }

    $save = file_put_contents($cache_folder . '/' . $filename, $json_data);

    return $save;
}


function hiko_cache_exists($name)
{
    $cache_folder = WP_CONTENT_DIR . '/hiko-cache';
    $file = "{$name}.json";

    if (file_exists($cache_folder . '/' . $file)) {
        return true;
    }
    return false;
}


function delete_hiko_cache($name)
{
    $file = WP_CONTENT_DIR . '/hiko-cache' . '/' . $name . '.json';

    if (file_exists($file)) {
        unlink($file);
    }
    return false;
}


function read_hiko_cache($name)
{
    $file = get_hiko_cache_file($name);
    return file_get_contents($file);
}


function get_hiko_cache_file($name)
{
    return WP_CONTENT_DIR . '/hiko-cache' . '/' . $name . '.json';
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


add_image_size('xl-thumb', 300);

require 'ajax/common.php';
require 'ajax/letters.php';
require 'ajax/people.php';
require 'ajax/places.php';
require 'ajax/images.php';
require 'ajax/export.php';
require 'ajax/export-palladio.php';
require 'ajax/location.php';
require 'ajax/geonames.php';
require 'ajax/keywords.php';
require 'ajax/professions.php';
