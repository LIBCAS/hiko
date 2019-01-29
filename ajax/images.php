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

    $id = $_GET['letter'];
    $type = $_GET['l_type'];

    if ($type != 'blekastad') {
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
                wp_send_json_success();
            }
        }
    }
    wp_send_json_error('error', 500);
}
add_action('wp_ajax_handle_img_uploads', 'handle_img_uploads');


function handle_bl_image()
{
    if (!has_user_permission('blekastad_editor')) {
        echo '403';
        wp_die();
    }

    wp_die();
}

add_action('wp_ajax_handle_bl_image', 'handle_bl_image');
