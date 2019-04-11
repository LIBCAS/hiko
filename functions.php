<?php

date_default_timezone_set('Europe/Prague');

function remove_admin_bar()
{
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'remove_admin_bar');


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

function wps_deregister_styles()
{
    wp_dequeue_style('wp-block-library');
}
add_action('wp_print_styles', 'wps_deregister_styles', 100);


function remove_dashboard_widgets()
{
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');
}
add_action('wp_dashboard_setup', 'remove_dashboard_widgets');


function custom_menu_page_removing()
{
    remove_menu_page('edit-comments.php');
    remove_menu_page('edit.php');
}
add_action('admin_menu', 'custom_menu_page_removing');


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
    } else {
        return false;
    }
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
        't.id',
        't.name',
        't.birth_year',
        't.death_year',
        'letter_author.id AS au',
        'letter_recipient.id AS re',
        'letter_people_mentioned.id AS pm'
    ];

    $fields = implode(', ', $fields);

    $persons = pods(
        $person_type,
        [
            'select' => $fields,
            'orderby'=> 't.name ASC',
            'limit' => -1
        ]
    );

    $persons_filtered = [];
    $index = 0;
    while ($persons->fetch()) {
        $persons_filtered[$index]['id'] = $persons->display('id');
        $persons_filtered[$index]['name'] = $persons->display('name');
        $persons_filtered[$index]['birth'] = $persons->display('birth_year');
        $persons_filtered[$index]['death'] = $persons->display('death_year');
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
        'letter_origin.id AS letter_id',
        'letter_destination.id AS dest_id'
    ];

    $fields = implode(', ', $fields);

    $places = pods(
        $place_type,
        [
            'select' => $fields,
            'orderby'=> 't.name ASC',
            'limit' => -1
        ]
    );

    $places_filtered = [];
    $index = 0;
    while ($places->fetch()) {
        $places_filtered[$index]['id'] = $places->display('id');
        $places_filtered[$index]['city'] = $places->display('city');
        $places_filtered[$index]['country'] = $places->display('country');
        $places_filtered[$index]['relationships'] = !is_null($places->display('letter_id')) || !is_null($places->display('dest_id'));
        $index++;
    }

    return $places_filtered;
}


function get_pods_name_and_id($type)
{
    $fields = [
        't.id',
        't.name',
    ];

    $fields = implode(', ', $fields);

    $pod = pods(
        $type,
        [
            'select' => $fields,
            'orderby'=> 't.name ASC',
            'limit' => -1
        ]
    );

    /* convert to array of arrays instead objects */
    $result = json_encode($pod->data());
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
                $sum ++;
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

    $user = wp_get_current_user();
    $roles = (array) $user->roles;

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

    $user = wp_get_current_user();
    $roles = (array) $user->roles;

    $editor_roles = ['administrator', 'blekastad_editor', 'demo_editor'];

    $result = array_intersect($roles, $editor_roles);

    if (count($result) > 0) {
        return true;
    }

    return false;
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


function bulk_add_persons($file)
{
    $file_content = file_get_contents($file);
    $splited_content = explode("\n", $file_content);
    foreach ($splited_content as $person) {
        $bits = explode("\t", $person);

        $data = [
            'name' => $bits[0],
            'surname' => $bits[1],
            'forename' => $bits[2],
            'birth_year' => 0,
            'death_year' => 0,
        ];

        $new_pod = pods_api()->save_pod_item([
            'pod' => 'bl_person',
            'data' => $data
        ]);

        var_dump($new_pod);
    }
}


function import_letters_from_file($file)
{
    $file_content = file_get_contents($file);
    $splited_content = explode("\n", $file_content);
    foreach ($splited_content as $letter) {
        $bits = explode("\t", $letter);
        $data = [
            'l_number' => $bits[0],
            'date_marked' => $bits[4],
            'date_approximate' => $bits[5],
            'date_range' => $bits[6],
            'date_uncertain' => $bits[10],
            'date_notes' => $bits[11],
            'l_author' => explode(';', $bits[12]),
            'l_author_marked' => $bits[13],
            'author_inferred' => $bits[14],
            'author_note' => $bits[15],
            'author_uncertain' => $bits[16],
            'recipient' => explode(';', $bits[17]),
            'recipient_marked' => $bits[18],
            'recipient_inferred' => $bits[19],
            'recipient_uncertain' => $bits[20],
            'recipient_notes' => $bits[21],
            'origin' => explode(';', $bits[22]),
            'origin_marked' => $bits[23],
            'origin_inferred' => $bits[24],
            'origin_uncertain' => $bits[25],
            'dest' => explode(';', $bits[26]),
            'dest_marked' => $bits[27],
            'dest_inferred' => $bits[28],
            'dest_uncertain' => $bits[29],
            'abstract' => $bits[30],
            'keywords' => $bits[31],
            'languages' => $bits[32],
            'incipit' => $bits[33],
            'explicit' => $bits[34],
            'notes_public' => $bits[35],
            'people_mentioned' => explode(';', $bits[36]),
            'people_mentioned_notes' => $bits[37],
            'name' => $bits[3] . '. ' . $bits[2] . '. ' . $bits[1],

        ];

        if (is_numeric($bits[1])) {
            $data['date_year'] = $bits[1];
        }
        if (is_numeric($bits[2])) {
            $data['date_month'] = $bits[2];
        }
        if (is_numeric($bits[3])) {
            $data['date_day'] = $bits[3];
        }
        if (is_numeric($bits[7])) {
            $data['date_range_year'] = $bits[7];
        }
        if (is_numeric($bits[8])) {
            $data['date_range_month'] = $bits[8];
        }
        if (is_numeric($bits[9])) {
            $data['date_range_day'] = $bits[9];
        }

        $new_pod = pods_api()->save_pod_item([
            'pod' => 'bl_letter',
            'data' => $data
        ]);

        var_dump($new_pod);
    }
}


function get_letters_basic_meta($letter_type, $person_type, $place_type)
{
    global $wpdb;

    $podsAPI = new PodsAPI();
    $pod = $podsAPI->load_pod(['name' => $letter_type]);
    $pod_id = $pod['id'];
    $author_field_id = $pod['fields']['l_author']['id'];
    $recipient_field_id = $pod['fields']['recipient']['id'];
    $origin_field_id = $pod['fields']['origin']['id'];
    $dest_field_id = $pod['fields']['dest']['id'];

    $related_ids = "{$author_field_id},{$recipient_field_id},{$origin_field_id},{$dest_field_id}";

    $l_prefix = "{$wpdb->prefix}pods_{$letter_type}";
    $r_prefix = "{$wpdb->prefix}podsrel";
    $pl_prefix = "{$wpdb->prefix}pods_{$place_type}";
    $pe_prefix = "{$wpdb->prefix}pods_{$person_type}";

    $fields = [
        't.id',
        't.l_number',
        't.date_day',
        't.date_month',
        't.date_year',
        't.status',
        't.created',
        'l_author.name AS author',
        'recipient.name AS recipient',
        'origin.name AS origin',
        'dest.name AS dest'
    ];

    $fields = implode(', ', $fields);

    $query = "
    SELECT
    {$fields}
    FROM
    $l_prefix AS t
    LEFT JOIN {$r_prefix} AS rel_l_author ON rel_l_author.field_id = {$author_field_id}
    AND rel_l_author.item_id = t.id
    LEFT JOIN {$pe_prefix} AS l_author ON l_author.id = rel_l_author.related_item_id
    LEFT JOIN {$r_prefix} AS rel_recipient ON rel_recipient.field_id = {$recipient_field_id}
    AND rel_recipient.item_id = t.id
    LEFT JOIN {$pe_prefix} AS recipient ON recipient.id = rel_recipient.related_item_id
    LEFT JOIN {$r_prefix} AS rel_origin ON rel_origin.field_id = {$origin_field_id}
    AND rel_origin.item_id = t.id
    LEFT JOIN {$pl_prefix} AS origin ON origin.id = rel_origin.related_item_id
    LEFT JOIN {$r_prefix} AS rel_dest ON rel_dest.field_id = {$dest_field_id}
    AND rel_dest.item_id = t.id
    LEFT JOIN {$pl_prefix} AS dest ON dest.id = rel_dest.related_item_id
    ORDER BY
    t.created DESC,
    t.name,
    t.id
    ";

    return $wpdb->get_results($query);
}


function get_duplicities_by_id($objet)
{
    $ids = array_map(create_function('$o', 'return $o->id;'), $objet);
    $unique = array_unique($ids);
    return array_unique(array_diff_assoc($ids, $unique));
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


function flatten_duplicate_letters($duplicate_ids, $data)
{
    $flattened = [];

    foreach ($duplicate_ids as $ld) {
        $duplicite_objects = get_all_objects_by_id($data, $ld);

        $single_letter = [
            'id' => '',
            'l_number' => '',
            'date_day' => '',
            'date_month' => '',
            'date_year' => '',
            'status' => '',
            'created' => '',
            'author' => [],
            'recipient' => [],
            'origin' => [],
            'dest' => [],
        ];

        $auth = [];
        $rec = [];
        $origins = [];
        $dests = [];

        for ($i = 0; $i < count($duplicite_objects); $i++) {
            if ($i == 0) {
                $single_letter['id'] = $duplicite_objects[$i]->id;
                $single_letter['l_number'] = $duplicite_objects[$i]->l_number;
                $single_letter['date_day'] = $duplicite_objects[$i]->date_day;
                $single_letter['date_month'] = $duplicite_objects[$i]->date_month;
                $single_letter['date_year'] = $duplicite_objects[$i]->date_year;
                $single_letter['status'] = $duplicite_objects[$i]->status;
                $single_letter['created'] = $duplicite_objects[$i]->created;
            }
            $auth[] = $duplicite_objects[$i]->author;
            $rec[] = $duplicite_objects[$i]->recipient;
            $origins[] = $duplicite_objects[$i]->origin;
            $dests[] = $duplicite_objects[$i]->dest;
        }

        $single_letter['author'] = array_values(array_unique($auth));
        $single_letter['recipient'] = array_values(array_unique($rec));
        $single_letter['origin'] = array_values(array_unique($origins));
        $single_letter['dest'] = array_values(array_unique($dests));

        $flattened[] = (object) $single_letter;
    }

    return $flattened;
}


function get_letters_basic_meta_filtered($letter_type, $person_type, $place_type)
{
    $q_results = get_letters_basic_meta($letter_type, $person_type, $place_type);

    $letters_duplicate_ids = get_duplicities_by_id($q_results);

    $new_flat_letters = flatten_duplicate_letters($letters_duplicate_ids, $q_results);

    $q_results_filtered = array_filter($q_results, function ($r) use ($letters_duplicate_ids) {
        return !in_array($r->id, $letters_duplicate_ids);
    }, ARRAY_FILTER_USE_BOTH);

    foreach ($new_flat_letters as $l) {
        $q_results_filtered[] = $l;
    }

    return array_values($q_results_filtered);
}


function get_hiko_post_types($type)
{
    $data = [
        'letter' => '',
        'place' => '',
        'person' => '',
        'editor' => '',
        'path' => '',
        'title' => '',
    ];

    if ($type == 'blekastad') {
        $data['letter'] = 'bl_letter';
        $data['place'] = 'bl_place';
        $data['person'] = 'bl_person';
        $data['editor'] = 'blekastad_editor';
        $data['path'] = 'blekastad';
        $data['title'] = 'Milada Blekastad';
    } elseif ($type == 'demo') {
        $data['letter'] = 'demo_letter';
        $data['place'] = 'demo_place';
        $data['person'] = 'demo_person';
        $data['editor'] = 'demo_editor';
        $data['path'] = 'demo';
        $data['title'] = 'Zkušební DB';
    }

    return $data;
}

function get_hiko_post_types_by_url()
{
    $req = $_SERVER['REQUEST_URI'];
    if (strpos($req, 'blekastad') !== false) {
        return get_hiko_post_types('blekastad');
    } elseif (strpos($req, 'demo') !== false) {
        return get_hiko_post_types('demo');
    }
    return get_hiko_post_types('');
}


function get_letter_single_field($type, $id, $field_name)
{
    $fields = [
        "t.{$field_name}",
        't.ID'
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
    $people_mentioned = [];
    $authors = [];
    $recipients = [];
    $origins = [];
    $destinations = [];
    $langs = '';
    $keywords = '';

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
        foreach ($_POST['people_mentioned'] as $people) {
            $people_mentioned[] = test_input($people);
        }
    }

    if (array_key_exists('languages', $_POST)) {
        foreach ($_POST['languages'] as $lang) {
            $langs .= test_input($lang) . ';';
        }
    }

    if (array_key_exists('keywords', $_POST)) {
        $keywords = [];
        foreach ($_POST['keywords'] as $kw) {
            $keywords[] = test_input($kw);
        }
    }

    if (is_array($keywords)) {
        $keywords = array_filter(
            $keywords,
            'get_nonempty_value'
        );
        $keywords = implode(';', $keywords);
    } else {
        $keywords = '';
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
        'l_number' => 'l_number',
        'date_year' => 'date_year',
        'date_month' => 'date_month',
        'date_day' => 'date_day',
        'range_year' => 'range_year',
        'range_month' => 'range_month',
        'range_day' => 'range_day',
        'date_marked' => 'date_marked',
        'recipient_notes' => 'recipient_notes',
        'origin_marked' => 'origin_marked',
        'dest_marked' => 'dest_marked',
        'abstract' => 'abstract',
        'incipit' => 'incipit',
        'explicit' => 'explicit',
        'people_mentioned_notes' => 'people_mentioned_notes',
        'notes_public' => 'notes_public',
        'notes_private' => 'notes_private',
        'rel_rec_name' => 'rel_rec_name',
        'rel_rec_url' => 'rel_rec_url',
        'ms_manifestation' => 'ms_manifestation',
        'repository' => 'repository',
        'name' => 'description',
        'status' => 'status',
        'date_note' => 'date_note',
        'origin_note' => 'origin_note',
        'dest_note' => 'dest_note',
        'author_note' => 'author_note',
        'archive' => 'archive',
        'collection' => 'collection',
        'signature' => 'signature',
        'location_note' => 'location_note'
    ]);

    $data['date_uncertain'] = get_form_checkbox_val('date_uncertain', $_POST);
    $data['date_approximate'] = get_form_checkbox_val('date_approximate', $_POST);
    $data['date_is_range'] = get_form_checkbox_val('date_is_range', $_POST);
    $data['author_uncertain'] = get_form_checkbox_val('author_uncertain', $_POST);
    $data['author_inferred'] = get_form_checkbox_val('author_inferred', $_POST);
    $data['recipient_inferred'] = get_form_checkbox_val('recipient_inferred', $_POST);
    $data['recipient_uncertain'] = get_form_checkbox_val('recipient_uncertain', $_POST);
    $data['origin_inferred'] = get_form_checkbox_val('origin_inferred', $_POST);
    $data['origin_uncertain'] = get_form_checkbox_val('origin_uncertain', $_POST);
    $data['dest_uncertain'] = get_form_checkbox_val('dest_uncertain', $_POST);
    $data['dest_inferred'] = get_form_checkbox_val('dest_inferred', $_POST);
    $data['l_author'] = $authors;
    $data['recipient'] = $recipients;
    $data['languages'] = $langs;
    $data['keywords'] = $keywords;
    $data['people_mentioned'] = $people_mentioned;
    $data['dest'] = $destinations;
    $data['origin'] = $origins;
    $data['history'] = $history;
    $data['authors_meta'] = $participant_meta;

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
    } elseif (is_wp_error($new_pod)) {
        return alert($new_pod->get_error_message(), 'warning');
    } else {
        delete_hiko_cache('list_' . $path);
        frontend_refresh();
        return alert('Uloženo', 'success');
    }
}


function save_hiko_person($person_type, $action)
{
    $data = test_postdata([
        'name' => 'fullname',
        'surname' => 'surname',
        'forename' => 'forename',
        'birth_year' => 'birth_year',
        'death_year' => 'death_year',
        'emlo' => 'emlo',
        'note' => 'note',
        'profession' => 'profession',
        'nationality' => 'nationality'
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
    } elseif (is_wp_error($new_pod)) {
        return alert($new_pod->get_error_message(), 'warning');
    } else {
        frontend_refresh();
        return alert('Uloženo', 'success');
    }
}


function save_hiko_place($place_type, $action)
{
    $data = test_postdata([
        'name' => 'place',
        'country' => 'country',
        'note' => 'note',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
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
    } elseif (is_wp_error($new_pod)) {
        return alert($new_pod->get_error_message(), 'warning');
    } else {
        frontend_refresh();
        return alert('Uloženo', 'success');
    }
}

function get_languages()
{
    $languages = get_ssl_file(get_template_directory_uri() . '/assets/data/languages.json');
    return json_decode($languages);
}

function display_persons_and_places($person_type, $place_type)
{
    $persons = json_encode(
        get_pods_name_and_id($person_type),
        JSON_UNESCAPED_UNICODE
    );
    $places = json_encode(
        get_pods_name_and_id($place_type),
        JSON_UNESCAPED_UNICODE
    );

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
    $filename = md5($name) . '.json';


    if (!file_exists($cache_folder)) {
        wp_mkdir_p($cache_folder);
    }
    $save = file_put_contents($cache_folder . '/' . $filename, $json_data);

    return $save;
}


function hiko_cache_exists($name)
{
    $cache_folder = WP_CONTENT_DIR . '/hiko-cache';
    $file = md5($name) . '.json';

    if (file_exists($cache_folder . '/' . $file)) {
        return true;
    }
    return false;
}


function delete_hiko_cache($name)
{
    $file = WP_CONTENT_DIR . '/hiko-cache' . '/' . md5($name) . '.json';

    if (file_exists($file)) {
        unlink($file);
    }
    return false;
}


function read_hiko_cache($name)
{
    $file = WP_CONTENT_DIR . '/hiko-cache' . '/' . md5($name) . '.json';
    return file_get_contents($file);
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


add_image_size('xl-thumb', 300);

require 'ajax/common.php';
require 'ajax/letters.php';
require 'ajax/people.php';
require 'ajax/places.php';
require 'ajax/images.php';
require 'ajax/export.php';
require 'ajax/location.php';
require 'ajax/geonames.php';
