<?php

/* Template Name: Náhled dopisu */

if (!is_in_editor_role()) {
    die('Nemáte oprávnění zobrazit tuto stránku');
}

if (!array_key_exists('l_type', $_GET) || !array_key_exists('letter', $_GET)) {
    die('Nepodařilo se načíst požadovaný dopis');
}

$letter_id = sanitize_text_field($_GET['letter']);
$letter_type = sanitize_text_field($_GET['l_type']);
$pod = pods($letter_type, $letter_id);

if (!$pod->exists()) {
    die('Nepodařilo se načíst požadovaný dopis');
}

if ($letter_type == 'bl_letter') {
    $letter_path = 'blekastad';
} elseif ($letter_type == 'demo_letter') {
    $letter_path = 'demo';
} elseif ($letter_type == 'tgm_letter') {
    $letter_path = 'tgm';
} else {
    die('Nenalezeno');
}

$link_dashboard = home_url("/{$letter_path}/letters/");
$link_letter_edit = home_url("/{$letter_path}/letters-add/?edit=$letter_id");
$link_letter_img = home_url("/{$letter_path}/letters-media/?l_type={$letter_type}&letter={$letter_id}");


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
                <div v-if="loading">
                    Načítám
                </div>
                <div class="letter-single" v-else>
                    <h3> Preview: {{ title }}</h3>
                    <div class="my-5">
                        <h5>Dates</h5>
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td style="width: 20%;">
                                        Letter date
                                    </td>
                                    <td>
                                        <span>
                                            {{ day ? day : '?' }}. {{ month ? month : '?'}}. {{ year ? year : '????' }}
                                        </span>
                                        <span v-if="date_is_range">
                                            – {{ day2 ? day2 : '?' }}. {{ month2 ? month2 : '?'}}. {{ year2 ? year2 : '????' }}
                                        </span>

                                        <span v-if="date_uncertain" class="d-block">
                                            <small><em>Uncertain date</em></small>
                                        </span>

                                        <span v-if="date_inferred" class="d-block">
                                            <small><em>Inferred date</em></small>
                                        </span>

                                        <span v-if="date_approximate" class="d-block">
                                            <small><em>Approximate date</em></small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="date_marked">
                                    <td>
                                        Date as marked
                                    </td>
                                    <td>
                                        {{ date_marked }}
                                    </td>
                                </tr>
                                <tr v-if="date_note">
                                    <td>
                                        Notes on date
                                    </td>
                                    <td>
                                        {{ date_note }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-5">
                        <h5>People</h5>
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td style="width: 20%;">Author</td>
                                    <td>
                                        <span v-for="a in author" class="d-block" :title="a.title">
                                            {{ a.marked }}
                                        </span>
                                        <span v-if="author_uncertain" class="d-block">
                                            <small><em>Uncertain author</em></small>
                                        </span>
                                        <span v-if="author_inferred" class="d-block">
                                            <small><em>Inferred author</em></small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="author_note">
                                    <td>
                                        Notes on author
                                    </td>
                                    <td>
                                        {{ author_note }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Recipient</td>
                                    <td>
                                        <span v-for="r in recipient" class="d-block" :title="r.title">
                                            {{ r.marked }}
                                        </span>
                                        <span v-if="recipient_uncertain" class="d-block">
                                            <small><em>Uncertain recipient</em></small>
                                        </span>
                                        <span v-if="recipient_inferred" class="d-block">
                                            <small><em>Inferred recipient</em></small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recipient_notes">
                                    <td>
                                        Notes on recipient
                                    </td>
                                    <td>
                                        {{ recipient_notes }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Mentioned people</td>
                                    <td>
                                        <span v-for="m in mentioned" class="d-block"> {{ m }}</span>
                                    </td>
                                </tr>
                                <tr v-if="people_mentioned_notes">
                                    <td>Notes on mentioned people</td>
                                    <td>{{ people_mentioned_notes }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-5">
                        <h5>Places</h5>
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td style="width: 20%;">Origin</td>
                                    <td>
                                        <span v-for="o in origin" class="d-block" :title="o.title">
                                            {{ o.marked }}
                                        </span>
                                        <span class="d-block" v-if="origin_uncertain">
                                            <small><em>Uncertain origin</em></small>
                                        </span>
                                        <span class="d-block" v-if="origin_inferred">
                                            <small><em>Inferred origin</em></small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="origin_note">
                                    <td>Notes on origin</td>
                                    <td>
                                        {{ origin_note }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Destination</td>
                                    <td>
                                        <span v-for="d in destination" class="d-block" :title="d.title">
                                            {{ d.marked }}
                                        </span>
                                        <span class="d-block" v-if="dest_uncertain">
                                            <small><em>Uncertain destination</em></small>
                                        </span>
                                        <span class="d-block" v-if="dest_inferred">
                                            <small><em>Inferred destination</em></small>
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="dest_note">
                                    <td>Notes on destination</td>
                                    <td>
                                        {{ dest_note }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-5">
                        <h5>Content</h5>
                        <table class="table">
                            <tbody>
                                <tr v-if="abstract">
                                    <td style="width: 20%;">Abstract</td>
                                    <td>{{ abstract }}</td>
                                </tr>
                                <tr v-if="incipit">
                                    <td style="width: 20%;">Incipit</td>
                                    <td v-html="incipit"></td>
                                </tr>
                                <tr v-if="explicit">
                                    <td style="width: 20%;">Explicit</td>
                                    <td v-html="explicit"></td>
                                </tr>
                                <tr v-if="languages.length > 0">
                                    <td style="width: 20%;">Languages</td>
                                    <td>
                                        <span v-for="lang in languages" class="d-block">
                                            {{ lang }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="keywords.length > 0">
                                    <td>Keywords</td>
                                    <td>
                                        <span v-for="keyword in keywords" class="d-block">
                                            {{ keyword }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="notes_public">
                                    <td>Notes on letter</td>
                                    <td>{{ notes_public }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="Object.keys(related_resources).length > 0" class="mb-5">
                        <h5>Related resources</h5>
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>
                                        <a v-for="rr in related_resources" :href="rr.link" target="_blank">
                                            {{ rr.title }}
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="gallery" class="mb-5 d-flex flex-wrap" v-if="images">
                        <div v-for="i in images">
                            <a :href="i.img.large" :data-caption="i.description">
                                <figure class="figure">
                                    <img :src="i.img.thumb" class="figure-img img-thumbnail" :alt="i.description" style="width:150px;max-width:150px">
                                    <figcaption class="figure-caption">{{ i.description }}</figcaption>
                                </figure>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <a href="<?= $link_letter_edit ?>">Upravit dopis</a>
                <br>
                <a href="<?= $link_letter_img ?>">Obrazové přílohy</a>
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
