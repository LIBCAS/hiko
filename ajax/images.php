<?php

function handle_img_uploads()
{
    $f = $_FILES['files'];
    $valid = verify_upload_img($f);

    if ($valid !== true) {
        wp_send_json_error($valid, 400);
    }

    if (!array_key_exists('l_type', $_GET) || !array_key_exists('letter', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $id = sanitize_text_field($_GET['letter']);
    $type = sanitize_text_field($_GET['l_type']);

    $pod = pods($type, $id);

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    if ($type != 'bl_letter') {
        wp_send_json_error('Not found', 404);
    }


    $upload_dir = wp_upload_dir();
    $new_file_dir = $upload_dir['basedir'] . '/' . $type . '/' . $id;
    $file_path = remove_accents($f['name'][0]);

    $filename = $new_file_dir . '/' . $file_path;
    $attachment = [
        'guid' => $upload_dir['url'] . '/'. $type . '/' . $id . '/' . basename($filename),
        'post_mime_type' => wp_check_filetype(basename($filename), null)['type'],
        'post_title' => sanitize_title(preg_replace('/\.[^.]+$/', '', basename($filename))),
        'post_content' => '',
        'post_status' => 'private'
    ];

    if (!is_dir($new_file_dir)) {
        $nf = mkdir($new_file_dir, 0777, true);
        if (!$nf) {
            wp_send_json_error(error_get_last()['message'], 501);
        }
    }

    if ($file_path) {
        $u = move_uploaded_file($f['tmp_name'][0], $filename);
        if (!$u) {
            wp_send_json_error(error_get_last()['message'], 501);
        } else {
            $insert = wp_insert_attachment(
                $attachment,
                $filename,
                0
            );
            if (is_wp_error($insert)) {
                wp_send_json_error('error', 500);
            } else {
                $thumbs = wp_generate_attachment_metadata($insert, $filename);
                wp_update_attachment_metadata($insert, $thumbs);
                $pod->add_to('images', $insert);
                $pod->save;
                wp_send_json_success();
            }
        }
    }
    wp_send_json_error('error', 500);
}
add_action('wp_ajax_handle_img_uploads', 'handle_img_uploads');


function list_images()
{
    $result = [];

    if (!array_key_exists('l_type', $_GET) || !array_key_exists('letter', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $id = sanitize_text_field($_GET['letter']);
    $type = sanitize_text_field($_GET['l_type']);

    $pod = pods($type, $id);

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    $images = $pod->field('images');

    $results['name'] = $pod->field('name');
    $results['images'] = [];

    $i = 0;
    foreach ($images as $img) {
        $results['images'][$i]['id'] = $img['ID'];
        $results['images'][$i]['img']['large'] = $img['guid'];
        $results['images'][$i]['img']['thumb'] = wp_get_attachment_image_src($img['ID'], 'thumbnail')[0];
        $results['images'][$i]['caption'] = wp_get_attachment_caption($img['ID']);
        $results['images'][$i]['status'] = $img['post_status'];
        $i++;
    }

    wp_send_json_success($results);
}
add_action('wp_ajax_list_images', 'list_images');



function delete_image()
{

    $result = [];

    if (!array_key_exists('l_type', $_GET) || !array_key_exists('letter', $_GET) || !array_key_exists('img', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $letter_id = sanitize_text_field($_GET['letter']);
    $type = sanitize_text_field($_GET['l_type']);
    $img_id = sanitize_text_field($_GET['img']);

    $pod = pods($type, $letter_id);

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    $pod->remove_from('images', $img_id);
    $pod->save;
    $delete = wp_delete_attachment($img_id, true);
    wp_die($delete);
}
add_action('wp_ajax_delete_image', 'delete_image');


function change_metadata()
{
    $data = key($_POST);
    $data = json_decode($data);
    var_dump($data);
    die();
    $result = [];

    if (!array_key_exists('l_type', $_POST) || !array_key_exists('letter', $_POST) || !array_key_exists('img', $_POST)) {
        wp_send_json_error('Not found', 404);
    }

    $letter_id = sanitize_text_field($_POST['letter']);
    $type = sanitize_text_field($_POST['l_type']);
    $img_id = sanitize_text_field($_POST['img']);

    $pod = pods($type, $letter_id);

    if (!$pod->exists()) {
        wp_send_json_error('Not found', 404);
    }

    $pod->remove_from('images', $img_id);
    $pod->save;
    $delete = wp_delete_attachment($img_id, true);
    wp_die($delete);
}
add_action('wp_ajax_change_metadata', 'change_metadata');
