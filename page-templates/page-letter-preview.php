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

$question_icon =  get_template_directory_uri() . '/assets/open-iconic/svg/question-mark.svg';
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
    <style>
        .badge {
            border-radius: 50%;
            border: 2px solid;
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            line-height: 1;
            padding: .2em .4em;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div id="letter-preview" class="row main-content my-5">
            <div class="col-md-9">
                <div v-if="loading">
                    Načítám
                </div>
                <div class="letter-single" v-else>
                    <h3> Preview: <span v-html="title"></span></h3>
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
                                    <td v-html="date_marked"></td>
                                </tr>
                                <tr v-if="date_note">
                                    <td>
                                        Notes on date
                                    </td>
                                    <td v-html="date_note"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-5">
                        <h5>People</h5>
                        <table class="table">
                            <tbody>
                                <tr v-if="author.length > 0">
                                    <td style="width: 20%;">Author</td>
                                    <td>
                                        <span v-for="a in author" class="d-block">
                                            <span v-if="a.marked.length > 0">
                                                <span v-html="a.marked"></span>
                                                <span v-if="a.marked != a.title" class="badge pointer" :title="decodeHTML(a.title)">
                                                    ?
                                                </span>
                                            </span>
                                            <span v-else>
                                                <span v-html="a.title"></span>
                                            </span>
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
                                        <span v-html="author_note"></span>
                                    </td>
                                </tr>
                                <tr v-if="recipient.length > 0">
                                    <td>Recipient</td>
                                    <td>
                                        <span v-for="r in recipient" class="d-block">
                                            <span v-if="r.marked.length > 0">
                                                <span v-html="r.marked"></span>
                                                <span v-if="r.marked != r.title" class="badge pointer" :title="decodeHTML(r.title)">
                                                    ?
                                                </span>
                                            </span>
                                            <span v-else>
                                                <span v-html="r.title"></span>
                                            </span>
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
                                    <td v-html="recipient_notes"></td>
                                </tr>
                                <tr v-if="mentioned.length > 0">
                                    <td>Mentioned people</td>
                                    <td>
                                        <span v-for="m in mentioned" class="d-block" v-html="m"></span>
                                    </td>
                                </tr>
                                <tr v-if="people_mentioned_notes">
                                    <td>Notes on mentioned people</td>
                                    <td v-html="people_mentioned_notes"></td>
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
                                        <span v-for="o in origin" class="d-block">
                                            <span v-if="o.marked.length > 0">
                                                <span v-html="o.marked"></span>
                                                <span v-if="o.marked != o.title" class="badge pointer" :title="decodeHTML(o.title)">
                                                    ?
                                                </span>
                                            </span>
                                            <span v-else>
                                                <span v-html="o.title"></span>
                                            </span>
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
                                    <td v-html="origin_note"></td>
                                </tr>
                                <tr>
                                    <td>Destination</td>
                                    <td>
                                        <span v-for="d in destination" class="d-block">
                                            <span v-if="d.marked.length > 0">
                                                <span v-html="d.marked"></span>
                                                <span v-if="d.marked != d.title" class="badge pointer" :title="decodeHTML(d.title)">
                                                    ?
                                                </span>
                                            </span>
                                            <span v-else>
                                                <span v-html="d.title"></span>
                                            </span>
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
                                    <td v-html="dest_note"></td>
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
                                    <td v-html="abstract"></td>
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
                                        <span v-for="lang in languages" class="d-block" v-html="lang"></span>
                                    </td>
                                </tr>
                                <tr v-if="keywords.length > 0">
                                    <td>Keywords</td>
                                    <td>
                                        <span v-for="keyword in keywords" class="d-block" v-html="keyword"></span>
                                    </td>
                                </tr>
                                <tr v-if="notes_public">
                                    <td>Notes on letter</td>
                                    <td v-html="notes_public"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-5">
                        <h5>Repositories and versions</h5>
                        <table class="table">
                            <tbody>
                                <tr v-if="l_number">
                                    <td style="width: 20%;">
                                        Letter number
                                    </td>
                                    <td v-html="l_number"></td>
                                </tr>
                                <tr v-if="repository">
                                    <td style="width: 20%;">
                                        Repository
                                    </td>
                                    <td v-html="repository"></td>
                                </tr>
                                <tr v-if="archive">
                                    <td style="width: 20%;">
                                        Archive
                                    </td>
                                    <td v-html="archive"></td>
                                </tr>
                                <tr v-if="collection">
                                    <td style="width: 20%;">
                                        Collection
                                    </td>
                                    <td v-html="collection"></td>
                                </tr>
                                <tr v-if="signature">
                                    <td style="width: 20%;">
                                        Signature
                                    </td>
                                    <td v-html="signature"></td>
                                </tr>
                                <tr v-if="location_note">
                                    <td style="width: 20%;">
                                        Note on location
                                    </td>
                                    <td v-html="location_note"></td>
                                </tr>
                                <tr v-if="document_type">
                                    <td style="width: 20%;">
                                        Document type
                                    </td>
                                    <td v-html="document_type"></td>
                                </tr>
                                <tr v-if="preservation">
                                    <td style="width: 20%;">
                                        Preservation
                                    </td>
                                    <td v-html="preservation"></td>
                                </tr>
                                <tr v-if="copy">
                                    <td style="width: 20%;">
                                        Type of copy
                                    </td>
                                    <td v-html="copy"></td>
                                </tr>
                                <tr v-if="manifestation_notes">
                                    <td style="width: 20%;">
                                        Notes on nanifestation
                                    </td>
                                    <td v-html="manifestation_notes"></td>
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
                                        <a v-for="rr in related_resources" :href="rr.link" target="_blank" v-html="rr.title"></a>
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
                                    <figcaption class="figure-caption" v-html="i.description"></figcaption>
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
