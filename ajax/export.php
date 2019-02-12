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
         ->setCellValue('C1', 'Date marked')
         ->setCellValue('D1', 'Author')
         ->setCellValue('E1', 'Recipient')
         ->setCellValue('F1', 'Origin')
         ->setCellValue('G1', 'Destination');

    $results = [];
    $index = 0;
    $col = 2;
    while ($pod->fetch()) {
        $authors = '';
        $recipients = '';
        $origins = '';
        $destinations = '';

        $authors_full = $pod->field('l_author');
        $recipients_full = $pod->field('recipient');
        $origins_full = $pod->field('origin');
        $destinations_full = $pod->field('dest');

        if (!empty($authors_full)) {
            foreach ($authors_full as $rel_author) {
                $authors .= $rel_author['name'] . ';';
            }
        }

        if (!empty($recipients_full)) {
            foreach ($recipients_full as $rel_recipient) {
                $recipients[] = $rel_recipient['name'] . ';';
            }
        }

        if (!empty($origins_full)) {
            foreach ($origins_full as $o) {
                $origins[] = $o['name'] . ';';
            }
        }

        if (!empty($destinations_full)) {
            foreach ($destinations_full as $d) {
                $destinations[] = $d['name'] . ';';
            }
        }

        $spreadsheet
        ->setActiveSheetIndex(0)
        ->setCellValueByColumnAndRow(1, $col, $pod->field('l_number'))
        ->setCellValueByColumnAndRow(2, $col, $pod->field('name'))
        ->setCellValueByColumnAndRow(3, $col, $pod->field('date_marked'))
        ->setCellValueByColumnAndRow(4, $col, $authors)
        ->setCellValueByColumnAndRow(5, $col, $recipients)
        ->setCellValueByColumnAndRow(6, $col, $origins)
        ->setCellValueByColumnAndRow(7, $col, $destinations);
        
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
