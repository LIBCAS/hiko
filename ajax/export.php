<?php

function export_persons()
{
    if (!array_key_exists('type', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $type = sanitize_text_field($_GET['type']);

    $format = sanitize_text_field($_GET['format']);

    if ($format != 'csv') {
        wp_send_json_error('Format not found', 404);
    }

    global $wpdb;

    $fields = 'name, surname, forename, birth_year, death_year, note, profession, nationality, gender, type';

    $query = "SELECT {$fields} FROM {$wpdb->prefix}pods_{$type} ORDER BY `name`";

    array_to_csv_download(
        $wpdb->get_results($query, ARRAY_A),
        "export-$type.csv"
    );

    wp_die();
}
add_action('wp_ajax_export_persons', 'export_persons');
