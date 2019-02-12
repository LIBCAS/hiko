<?php

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



function test_input($input)
{
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
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


function get_persons_names($type)
{
    $persons_pods = pods(
        $type,
        [
            'orderby'=> 't.surname ASC',
            'limit' => -1
        ]
    );

    $persons = [];
    $index = 0;
    while ($persons_pods->fetch()) {
        $persons[$index]['id'] = $persons_pods->display('id');
        $persons[$index]['name'] = $persons_pods->display('name');
        $index++;
    }

    return $persons;
}


function get_places_names($type)
{
    $places_pods = pods(
        $type,
        [
            'orderby'=> 't.name ASC',
            'limit' => -1
        ]
    );

    $places = [];
    $index = 0;
    while ($places_pods->fetch()) {
        $places[$index]['id'] = $places_pods->display('id');
        $places[$index]['name'] = $places_pods->display('name');
        $index++;
    }

    return $places;
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


add_image_size('xl-thumb', 300);


require 'ajax/letters.php';
require 'ajax/people.php';
require 'ajax/places.php';
require 'ajax/images.php';
require 'ajax/export.php';
