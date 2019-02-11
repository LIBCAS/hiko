<?php

require_once get_template_directory() . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function export_letters()
{

    if (!array_key_exists('l_type', $_GET)) {
        wp_send_json_error('Not found', 404);
    }

    $type = sanitize_text_field($_GET['l_type']);

    if ($type == 'bl_letter' && has_user_permission('blekastad_editor')) {
        $filename = 'blekastad';
    } else {
        wp_send_json_error('Not allowed', 403);
    }

    $pod = pods(
        $type,
        [
        'orderby'=> 't.created DESC',
        'limit' => -1
        ]
    );

    $spreadsheet = new Spreadsheet();
    $spreadsheet
    ->getProperties()
    ->setCreator('HIKO')
    ->setTitle('Korespondence MB');

    $spreadsheet->setActiveSheetIndex(0)
         ->setCellValue('A1', 'Number')
         ->setCellValue('B1', 'Name')
         ->setCellValue('C1', 'Date marked');

    $results = [];
    $index = 0;
    $col = 2;
    while ($pod->fetch()) {
        $spreadsheet
        ->setActiveSheetIndex(0)
        ->setCellValueByColumnAndRow(1, $col, $pod->field('l_number'))
        ->setCellValueByColumnAndRow(2, $col, $pod->field('name'))
        ->setCellValueByColumnAndRow(3, $col, $pod->field('date_marked'));
    
        $col++;
        $index++;
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    header('Content-Type: Application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '-export.xlsx"');
    header('Content-Description: File Transfer');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    $writer->save('php://output');
    wp_die();
}
add_action('wp_ajax_export_letters', 'export_letters');
