<?php


function handle_img_uploads()
{
    $error = false;
    if (!array_key_exists('l_type', $_GET)) {
        $error = true;
    } elseif (!array_key_exists('letter', $_GET)) {
        $error = true;
    }

    $id = $_GET['letter'];
    $type = $_GET['l_type'];

    if ($type != 'blekastad') {
        $error = true;
    }

    if ($error) {
        echo 'error';
        return;
    }
    $f = $_FILES['files'];
    $upload_dir = wp_upload_dir();
    $new_file_dir = $upload_dir['basedir'] . '/' . $type . '/' . $id;
    $file_path = $f['name'][0];
    $tmp_file_name = $f['tmp_name'][0];
    $filename = $new_file_dir . '/' . $file_path;

    if (!is_dir($new_file_dir)) {
        $nf = mkdir($new_file_dir, 0777, true);
        if (!$nf) {
            $error = error_get_last();
            echo $error['message'];
            return;
        }
    }

    if ($file_path) {
        $u = move_uploaded_file($tmp_file_name, $filename);

        if (!$u) {
            $error = error_get_last();
            echo $error['message'];
        } else {
            $attachment = [
                'guid' => $upload_dir['url'] . '/'. $type . '/' . $id . '/' . basename($filename),
                'post_mime_type' => wp_check_filetype(basename($filename), null)['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content' => '',
                'post_status' => 'inherit'
            ];
            $insert = wp_insert_attachment(
                $attachment,
                $filename,
                0
            );
            if (is_wp_error($insert)) {
                echo 'error';
            } else {
                echo 'success';
                return;
            }
        }
    } else {
        echo 'error';
    }

    wp_die();
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
