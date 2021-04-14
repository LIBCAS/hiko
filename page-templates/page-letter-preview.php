<?php

/* Template Name: Náhled dopisu */

if (!is_in_editor_role() || !array_key_exists('l_type', $_GET) || !array_key_exists('letter', $_GET)) {
    die('Nemáte oprávnění zobrazit tuto stránku');
}

$letter_id = (int) $_GET['letter'];
$letter_type = $_GET['l_type'];
$letter_path = get_types_by_letter()[$letter_type]['handle'];
$letter = get_letter(get_types_by_letter()[$letter_type], $letter_id, '', true);
if (empty($letter)) {
    die('Dopis nebyl nalezen');
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
    <link rel="stylesheet" href="<?= get_template_directory_uri() . '/assets/dist/main.css?ver=' . filemtime(get_template_directory() . '/assets/dist/main.css'); ?>">
</head>

<body>
    <div class="container-fluid">
        <div class="my-5 row main-content">
            <div class="col-md-9">
                <h1 class="mb-5 h2">
                    <?= $letter['name'] ?>
                </h1>
                <h2 class="h4">Dates</h2>
                <table class="table mb-6 table-sm">
                    <tbody>
                        <tr class="align-baseline">
                            <td class="py-2 w-25">
                                Letter date
                            </td>
                            <td class="py-2">
                                <?php
                                echo format_letter_date($letter['date_day'], $letter['date_month'], $letter['date_year']);
                                if ($letter['date_is_range']) {
                                    echo ' – ' . format_letter_date($letter['range_day'], $letter['range_month'], $letter['range_year']);
                                }
                                ?>
                                <?php if ($letter['date_uncertain']) : ?>
                                    <small class="d-block"><em>Uncertain date</em></small>
                                <?php endif; ?>
                                <?php if ($letter['date_inferred']) : ?>
                                    <small class="d-block"><em>Inferred date</em></small>
                                <?php endif; ?>
                                <?php if ($letter['date_approximate']) : ?>
                                    <small class="d-block"><em>Approximate date</em></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($letter['date_marked']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2">
                                    Date as marked
                                </td>
                                <td class="py-2">
                                    <?= $letter['date_marked']; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($letter['date_note']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2">
                                    Notes on date
                                </td>
                                <td class="py-2">
                                    <?= $letter['date_note']; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h2 class="h4">People</h2>
                <table class="table mb-6 table-sm">
                    <tbody>
                        <?php if (!empty($letter['l_author'])) : ?>
                            <tr class="align-baseline">
                                <td class="py-2 w-25">Author</td>
                                <td class="py-2">
                                    <ul class="pl-0 mb-0">
                                        <?php foreach ($letter['l_author'] as $author) : ?>
                                            <?= format_letter_object($author); ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php if ($letter['author_uncertain']) : ?>
                                        <small class="d-block"><em>Uncertain author</em></small>
                                    <?php endif; ?>
                                    <?php if ($letter['author_inferred']) : ?>
                                        <small class="d-block"><em>Inferred author</em></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($letter['author_note']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2">
                                    Notes on author
                                </td>
                                <td class="py-2">
                                    <?= $letter['author_note']; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($letter['recipient'])) : ?>
                            <tr class="align-baseline">
                                <td class="py-2 w-25">Recipient</td>
                                <td class="py-2">
                                    <ul class="pl-0 mb-0">
                                        <?php foreach ($letter['recipient'] as $recipient) : ?>
                                            <?= format_letter_object($recipient); ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php if ($letter['recipient_uncertain']) : ?>
                                        <small class="d-block"><em>Uncertain recipient</em></small>
                                    <?php endif; ?>
                                    <?php if ($letter['recipient_inferred']) : ?>
                                        <small class="d-block"><em>Inferred recipient</em></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($letter['recipient_notes']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2">
                                    Notes on recipient
                                </td>
                                <td class="py-2">
                                    <?= $letter['recipient_notes']; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($letter['people_mentioned'])) : ?>
                            <tr class="align-baseline">
                                <td class="py-2">Mentioned people</td>
                                <td class="py-2">
                                    <ul class="pl-0 mb-0">
                                        <?php foreach ($letter['people_mentioned'] as $person) : ?>
                                            <li class="mb-1">
                                                <?= $person['name']; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($letter['people_mentioned_notes']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2">
                                    Notes on mentioned people
                                </td>
                                <td class="py-2">
                                    <?= $letter['people_mentioned_notes']; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h2 class="h4">Places</h2>
                <table class="table mb-6 table-sm">
                    <tbody>
                        <?php if (!empty($letter['origin'])) : ?>
                            <tr class="align-baseline">
                                <td class="py-2 w-25">Origin</td>
                                <td class="py-2">
                                    <ul class="pl-0 mb-0">
                                        <?php foreach ($letter['origin'] as $origin) : ?>
                                            <?= format_letter_object($origin); ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php if ($letter['origin_uncertain']) : ?>
                                        <small class="d-block"><em>Uncertain origin</em></small>
                                    <?php endif; ?>
                                    <?php if ($letter['origin_inferred']) : ?>
                                        <small class="d-block"><em>Inferred origin</em></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($letter['origin_note']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2">
                                    Notes on origin
                                </td>
                                <td class="py-2">
                                    <?= $letter['origin_note']; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($letter['dest'])) : ?>
                            <tr class="align-baseline">
                                <td class="py-2 w-25">Destination</td>
                                <td class="py-2">
                                    <ul class="pl-0 mb-0">
                                        <?php foreach ($letter['dest'] as $destination) : ?>
                                            <?= format_letter_object($destination); ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php if ($letter['dest_uncertain']) : ?>
                                        <small class="d-block"><em>Uncertain destination</em></small>
                                    <?php endif; ?>
                                    <?php if ($letter['dest_inferred']) : ?>
                                        <small class="d-block"><em>Inferred destination</em></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($letter['dest_note']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2">
                                    Notes on destination
                                </td>
                                <td class="py-2">
                                    <?= $letter['dest_note']; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h2 class="h4">Content</h2>
                <table class="table mb-6 table-sm">
                    <tbody>
                        <?php if ($letter['abstract']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2 w-25">Abstract</td>
                                <td class="py-2"><?= $letter['abstract']; ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($letter['incipit']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2 w-25">Incipit</td>
                                <td class="py-2"><?= $letter['incipit']; ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($letter['explicit']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2 w-25">Explicit</td>
                                <td class="py-2"><?= $letter['explicit']; ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($letter['languages'])) : ?>
                            <tr class="align-baseline">
                                <td class="py-2 w-25">Languages</td>
                                <td class="py-2">
                                    <ul class="pl-0 mb-0">
                                        <?php foreach ($letter['languages'] as $lang) : ?>
                                            <li class="mb-1">
                                                <?= $lang; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($letter['keywords'])) : ?>
                            <tr class="align-baseline">
                                <td class="py-2">Keywords</td>
                                <td class="py-2">
                                    <?php foreach (array_values($letter['keywords']) as $kw) : ?>
                                        <li class="mb-1">
                                            <?= $kw['name']; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($letter['notes_public']) : ?>
                            <tr class="align-baseline">
                                <td class="py-2 w-25">Notes on letter</td>
                                <td class="py-2"><?= $letter['notes_public']; ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h2 class="h4">Repositories and versions</h2>
                <?php foreach ($letter['copies'] as $c) : ?>
                    <table class="table mb-6 table-sm">
                        <tbody>
                            <?php if ($c['l_number']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Letter number</td>
                                    <td class="py-2"><?= $c['l_number']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($c['repository']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Repository</td>
                                    <td class="py-2"><?= $c['repository']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($c['archive']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Archive</td>
                                    <td class="py-2"><?= $c['archive']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($c['collection']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Collection</td>
                                    <td class="py-2"><?= $c['collection']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($c['signature']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Signature</td>
                                    <td class="py-2"><?= $c['signature']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($c['location_note']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Note on location</td>
                                    <td class="py-2"><?= $c['location_note']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($c['type']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Document type</td>
                                    <td class="py-2"><?= $c['type']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($c['preservation']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Preservation</td>
                                    <td class="py-2"><?= $c['preservation']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($c['copy']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Type of copy</td>
                                    <td class="py-2"><?= $c['copy']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($c['manifestation_notes']) : ?>
                                <tr class="align-baseline">
                                    <td class="py-2 w-25">Notes on nanifestation</td>
                                    <td class="py-2"><?= $c['manifestation_notes']; ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>

                <?php if (!empty($letter['related_resources'])) : ?>
                    <h2 class="h4">Related resources</h2>
                    <table class="table mb-6 table-sm">
                        <tbody>
                            <tr class="align-baseline">
                                <td class="py-2">
                                    <?php foreach ($letter['related_resources'] as $resource) : ?>
                                        <li class="mb-1">
                                            <?php if (!empty($resource['link'])) : ?>
                                                <a href="<?= $resource['link']; ?>" target="_blank">
                                                    <?= $resource['title']; ?>
                                                </a>
                                            <?php else : ?>
                                                <?= $resource['title']; ?>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
                <div class="flex-wrap d-flex" id="gallery">
                    <?php foreach ($letter['images'] as $img) : ?>
                        <a href="<?= $img['img']['large']; ?>" caption="<?= $img['description']; ?>" class="m-1">
                            <img src="<?= $img['img']['thumb']; ?>" class="p-1 border" alt="<?= $img['description']; ?>" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-3">
                <a href="<?= home_url("/{$letter_path}/letters-add/?edit=$letter_id") ?>">Upravit dopis</a>
                <br>
                <a href="<?= home_url("/{$letter_path}/letters-media/?l_type={$letter_type}&letter={$letter_id}"); ?>">Obrazové přílohy</a>
                <br>
                <a href="<?= home_url("/{$letter_path}/letters/") ?>">Přehled dopisů</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/baguettebox.js@1.11.0/dist/baguetteBox.min.js"></script>
    <script>
        baguetteBox.run('#gallery')
    </script>
</body>

</html>
