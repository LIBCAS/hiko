<?php

/* Template Name: Náhled dopisu */

if (!has_user_permission('bl_editor')) {
    die('Nemáte oprávnění zobrazit tuto stránku');
}
if (array_key_exists('l_type', $_GET) && array_key_exists('letter', $_GET)) {
    $letter_id = sanitize_text_field($_GET['letter']);
    $letter_type = sanitize_text_field($_GET['l_type']);
    $pod = pods($letter_type, $letter_id);
} else {
    die('Nepodařilo se načíst požadovaný dopis');
}
if (!$pod->exists()) {
    die('Nepodařilo se načíst požadovaný dopis');
}


?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Náhled dopisu <?= $letter_id; ?></title>
        <link rel="stylesheet" href="<?= get_template_directory_uri() ?>/assets/css/preview.css">
        <script type="text/javascript">
            var ajaxUrl = '<?= admin_url('admin-ajax.php'); ?>';
            var homeUrl = '<?= home_url(); ?>';
        </script>
    </head>
    <body>
        <div class="container-fluid">
            <div id="letter-preview" class="row main-content mb-5">
                <div class="col-md-9">
                    <div class="letter-single">
                        <h3>22. 4. 1999:  Klaus Schaller (Bochum)  →  Milada Blekastad (Oslo)</h3>
                        <div class="my-5">
                            <h5>Data</h5>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td style="width: 20%;">
                                            Datum dopisu
                                        </td>
                                        <td>
                                            22. 4. 1999
                                        </td>
                                    </tr>
                                    <!---->
                                </tbody>
                            </table>
                        </div>
                        <div class="mb-5">
                            <h5>Lidé</h5>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td style="width: 20%;">Autor</td>
                                        <td>
                                            <span>Klaus Schaller</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Příjemce</td>
                                        <td>
                                            <span>Milada Blekastad</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mb-5">
                            <h5>Místa</h5>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td style="width: 20%;">Počáteční místo</td>
                                        <td>
                                            <span>Bochum</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Místo určení</td>
                                        <td>
                                            <span>Oslo</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mb-5">
                            <h5>Obsah</h5>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td style="width: 20%;">Jazyk</td>
                                        <td><span>german</span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Incipit</td>
                                        <td>Herzlichen Dank für Ihren ausführlichen Brief</td>
                                    </tr>

                                    <tr>
                                        <td>Zmíněné osoby</td>
                                        <td>
                                            <ul class="list-unstyled">
                                                <li>Comenius</li>
                                            </ul>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
        <script src="https://unpkg.com/axios@0.18.0/dist/axios.min.js"></script>
        <script src="<?= get_template_directory_uri(); ?>/assets/dist/custom.min.js?v=<?= filemtime(get_template_directory() . '/assets/dist/custom.min.js'); ?>"></script>
    </body>
</html>
