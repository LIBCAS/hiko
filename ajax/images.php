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
    $upload_dir = wp_upload_dir();
    $new_file_dir = $upload_dir['basedir'] . '/' . $type . '/' . $id;

    if (!is_dir($new_file_dir)) {
        $nf = mkdir($new_file_dir, 0777, true);
        if (!$nf) {
            $error = error_get_last();
            echo $error['message'];
            return;
        }
    }

    $file_path = $_FILES['files']['name'][0];
    $tmp_file_name = $_FILES['files']['tmp_name'][0];

    if ($file_path) {
        $u = move_uploaded_file($tmp_file_name, $new_file_dir . '/' . $file_path);
        if (!$u) {
            $error = error_get_last();
            echo $error['message'];
        } else {
            echo 'success';
            return;
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
