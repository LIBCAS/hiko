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
    /*
    $authors = [];
    $recipients = [];
    $people_mentioned = [];
    $authors_full = $pod->field('l_author');
    $recipients_full = $pod->field('recipient');
    $people_mentioned_full = $pod->field('l_author');

    if (!empty($authors_full)) {
    $author_index = 0;
    foreach ($authors_full as $a) {
    $authors[$author_index]['id'] = $a['id'];
    $authors[$author_index]['name'] = $a['name'];
    $author_index++;
  }
}

if (!empty($recipients_full)) {
$recipient_index = 0;
foreach ($recipients_full as $r) {
$recipients[$recipient_index]['id'] = $r['id'];
$recipients[$recipient_index]['name'] = $r['name'];
$recipient_index++;
}
}

if (!empty($people_mentioned_full)) {
$people_mentioned_index = 0;
foreach ($people_mentioned_full as $p) {
$people_mentioned[$people_mentioned_index]['id'] = $p['id'];
$people_mentioned[$people_mentioned_index]['name'] = $p['name'];
$people_mentioned_index++;
}
}

$results[$index]['l_number'] = $pod->field('l_number');
$results[$index]['date_year'] = $pod->field('date_year');
$results[$index]['date_month'] = $pod->field('date_month');
$results[$index]['date_day'] = $pod->field('date_day');
$results[$index]['date_marked'] = $pod->field('date_marked');
$results[$index]['date_uncertain'] = (int) $pod->field('date_uncertain');
$results[$index]['l_author'] = $authors;
$results[$index]['l_author_marked'] = $pod->field('l_author_marked');
$results[$index]['author_uncertain'] = (int) $pod->field('author_uncertain');
$results[$index]['author_inferred'] = (int) $pod->field('author_inferred');
$results[$index]['recipient'] = $recipients;
$results[$index]['recipient_marked'] = $pod->field('recipient_marked');
$results[$index]['recipient_inferred'] = (int) $pod->field('recipient_inferred');
$results[$index]['recipient_uncertain'] = (int) $pod->field('recipient_uncertain');
$results[$index]['recipient_notes'] = $pod->field('recipient_notes');
$results[$index]['origin'][$pod->field('origin')['id']] = $pod->field('origin')['name'];
$results[$index]['origin_marked'] = $pod->field('origin_marked');
$results[$index]['origin_inferred'] = (int) $pod->field('origin_inferred');
$results[$index]['origin_uncertain'] = (int) $pod->field('origin_uncertain');
$results[$index]['dest'][$pod->field('dest')['id']] = $pod->field('dest')['name'];
$results[$index]['dest_marked'] = $pod->field('dest_marked');
$results[$index]['dest_uncertain'] = (int) $pod->field('dest_uncertain');
$results[$index]['dest_inferred'] = (int) $pod->field('dest_inferred');
$results[$index]['languages'] = $pod->field('languages');
$results[$index]['keywords'] = $pod->field('keywords');
$results[$index]['abstract'] = $pod->field('abstract');
$results[$index]['incipit'] = $pod->field('incipit');
$results[$index]['explicit'] = $pod->field('explicit');
$results[$index]['people_mentioned'] = $people_mentioned;
$results[$index]['people_mentioned_notes'] = $pod->field('people_mentioned_notes');
$results[$index]['notes_public'] = $pod->field('notes_public');
$results[$index]['rel_rec_name'] = $pod->field('rel_rec_name');
$results[$index]['rel_rec_url'] = $pod->field('rel_rec_url');
$results[$index]['ms_manifestation'] = $pod->field('ms_manifestation');
$results[$index]['repository'] = $pod->field('repository');
$results[$index]['name'] = $pod->field('name');
$results[$index]['status'] = $pod->field('status');
//$results[$index]['images'] = $images_sorted;
$results[$index]['notes_private'] = $pod->field('notes_private');
*/
$col++;
$index++;
}

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

header('Content-Type: Application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '-export.xlsx"');
header('Content-Description: File Transfer');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
$writer->save('php://output');
}
add_action('wp_ajax_export_letters', 'export_letters');
