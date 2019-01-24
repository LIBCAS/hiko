<?php

function handle_bl_image()
{
    if (!has_user_permission('blekastad_editor')) {
        echo '403';
        wp_die();
    }

    wp_die();
}

add_action('wp_ajax_handle_bl_image', 'handle_bl_image');
