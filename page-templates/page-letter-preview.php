<?php

/* Template Name: Náhled dopisu */

if (!has_user_permission('bl_editor')) {
    die('Nemáte oprávnění zobrazit tuto stránku');
}
if (array_key_exists('l_type', $_GET) && array_key_exists('letter', $_GET)) {
    $letter_id = sanitize_text_field($_GET['letter']);
    $letter_type = sanitize_text_field($_GET['l_type']);
    $pod = pods($letter_type, $letter_id);
    if ($letter_type == 'bl_letter') {
        $link_dashboard = home_url('/blekastad/letters/');
        $link_letter_edit = home_url('/blekastad/letters-add/?edit=' . $letter_id);
    } else {
        $link_dashboard = '#';
        $link_letter_edit = '#';
    }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/baguettebox.js@1.11.0/dist/baguetteBox.min.css">
    <link rel="stylesheet" href="<?= get_template_directory_uri() ?>/assets/css/preview.css">
    <script type="text/javascript">
    var ajaxUrl = '<?= admin_url('admin-ajax.php'); ?>';
    var homeUrl = '<?= home_url(); ?>';
    </script>
</head>
<body>
    <div class="container-fluid">
        <div id="letter-preview" class="row main-content my-5">
            <div class="col-md-9">
                <div class="" v-if="loading">
                    Načítám
                </div>
                <div class="letter-single" v-else>
                    <h3> Náhled: {{ title }}</h3>
                    <div class="my-5">
                        <h5>Data</h5>
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td style="width: 20%;">
                                        Datum dopisu
                                    </td>
                                    <td>
                                        {{ day ? day : '?' }}. {{ month ? month : '?'}}. {{ year ? year : '????' }}
                                        <span v-if="date_uncertain">
                                            <br>
                                            <small>(Nejisté datum)</small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="date_marked">
                                    <td style="width: 20%;">
                                        Datum uvedené v dopise
                                    </td>
                                    <td>
                                        {{ date_marked }}
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
                                        <span v-for="a in author" class="d-block"> {{ a }}</span>
                                        <span v-if="author_uncertain" class="d-block">
                                            <small>(Nejistý autor)</small>
                                        </span>
                                        <span v-if="author_inferred" class="d-block">
                                            <small>(Autor vyvozený z obsahu dopisu)</small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="author_as_marked">
                                    <td style="width: 20%;">
                                        Autor uvedený v dopise
                                    </td>
                                    <td>
                                        {{ author_as_marked }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="width: 20%;">Příjemce</td>
                                    <td>
                                        <span v-for="r in recipient" class="d-block"> {{ r }}</span>
                                        <span v-if="recipient_uncertain" class="d-block">
                                            <small>(Nejistý příjemce)</small>
                                        </span>
                                        <span v-if="recipient_inferred" class="d-block">
                                            <small>(Příjemce vyvozený z obsahu dopisu)</small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recipient_marked">
                                    <td style="width: 20%;">
                                        Příjemce uvedený v dopise
                                    </td>
                                    <td>
                                        {{ recipient_marked }}
                                    </td>
                                </tr>
                                <tr v-if="recipient_notes">
                                    <td style="width: 20%;">
                                        Poznámky o příjemci
                                    </td>
                                    <td>
                                        {{ recipient_notes }}
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
                                        <span class="d-block"> {{ origin }}</span>
                                        <span class="d-block" v-if="origin_uncertain">
                                            <small>(Nejisté počáteční místo)</small>
                                        </span>
                                        <span class="d-block" v-if="origin_inferred">
                                            <small>(Počáteční místo vyvozeno z obsahu dopisu)</small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="origin_marked">
                                    <td>Počáteční místo uvedené v dopise</td>
                                    <td>
                                        <span> {{ origin_marked }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="width: 20%;">Místo určení</td>
                                    <td>
                                        <span class="d-block"> {{ destination }}</span>
                                        <span class="d-block" v-if="dest_uncertain">
                                            <small>(Nejisté místo určení)</small>
                                        </span>
                                        <span class="d-block" v-if="dest_inferred">
                                            <small>(Místo určení vyvozeno z obsahu dopisu)</small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="dest_marked">
                                    <td>Místo určení uvedené v dopise</td>
                                    <td>
                                        <span> {{ dest_marked }}</span>
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
                                    <td>
                                        <span v-for="lang in languages" class="d-block"> {{ lang }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Klíčová slova</td>
                                    <td>
                                        <span v-for="keyword in keywords" class="d-block"> {{ keyword }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Abstrakt</td>
                                    <td>{{ abstract }}</td>
                                </tr>

                                <tr>
                                    <td>Incipit</td>
                                    <td v-html="incipit"></td>
                                </tr>

                                <tr>
                                    <td>Explicit</td>
                                    <td v-html="explicit"></td>
                                </tr>

                                <tr>
                                    <td>Zmíněné osoby</td>
                                    <td>
                                        <span v-for="m in mentioned" class="d-block"> {{ m }}</span>
                                    </td>
                                </tr>

                                <tr v-if="people_mentioned_notes">
                                    <td>Poznámky o zmíněných osobách</td>
                                    <td>{{ people_mentioned_notes }}</td>
                                </tr>

                                <tr v-if="notes_public">
                                    <td>Poznámky k dopisu</td>
                                    <td>{{ notes_public }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="rel_rec_name" class="mb-5">
                        <h5>Související zdroje</h5>
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>
                                        <a :href="rel_rec_url" target="_blank">{{ rel_rec_name }}</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="gallery" class="mb-5 row" v-if="images">
                        <div class="col" style="width:150px" v-for="i in images" >
                            <a :href="i.img.large" :data-caption="i.description">
                                <figure class="figure" >
                                    <img :src="i.img.thumb" class="figure-img img-thumbnail" :alt="i.description" style="width:150px;max-width:150px">
                                    <figcaption class="figure-caption">{{ i.description }}</figcaption>
                                </figure>
                            </a>
                        </div>

                        <?php /* ?>
                        <a v-for="i in images" :href="i.img.large" :data-caption="i.description">
                        <img :src="i.img.thumb" class="figure-img img-thumbnail" :alt="i.description">
                        </a>
                        <?php /*/ ?>
                    </div>

                </div>
            </div>

            <div class="col-md-3">
                <a href="<?= $link_letter_edit ?>">Upravit dopis</a>
                <br>
                <a href="<?= $link_dashboard ?>">Přehled dopisů</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/axios@0.18.0/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/baguettebox.js@1.11.0/dist/baguetteBox.min.js"></script>
    <script src="<?= get_template_directory_uri(); ?>/assets/dist/custom.min.js?v=<?= filemtime(get_template_directory() . '/assets/dist/custom.min.js'); ?>"></script>
</body>
</html>
