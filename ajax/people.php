<?php

function list_bl_people_simple()
{
    echo json_encode(
        get_persons_names('bl_person'),
        JSON_UNESCAPED_UNICODE
    );
    wp_die();
}
add_action('wp_ajax_list_bl_people_simple', 'list_bl_people_simple');
