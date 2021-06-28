<?php
/* Template Name: Prohlížeč dopisů */

if (!isset($_GET['l_type'])) {
    die('Nemáte oprávnění zobrazit tuto stránku');
}

$letter_type = sanitize_text_field($_GET['l_type']);
$post_types = get_types_by_letter()[$letter_type];

if (!has_user_permission($post_types['editor'])) {
    die('Nemáte oprávnění zobrazit tuto stránku');
}
$edit_link = home_url($post_types['path'] . '/letters-add?edit=');
$letters = list_all_letters_meta($post_types); ?>

<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dopisy | HIKO </title>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #f0f0f0;
            color: #222;
        }

        h1,
        article {
            max-width: 700px;
            margin: 0 auto;
        }

        article {
            margin-bottom: 40px;
        }

        h2,
        h3 {
            font-size: 1em;
            line-height: 1em;
        }

        h3 {
            margin-bottom: 8px;
        }

        ul {
            margin: 0 0 1em;
        }

        button {
            border: 1px dashed purple;
            color: purple;
            font-weight: bold;
            margin-right: 8px;
            padding: 0 4px;
        }
    </style>
</head>

<body>
    <h1><?= $post_types['title'] ?></h1>
    <?php foreach ($letters as $letter) : ?>
        <article x-data="{ show: true }">
            <h2>
                <button type="button" @click="show = !show" x-text="show ? '–' : '+'"></button>
                <a href="<?= $edit_link . $letter['ID'] ?>" target="_blank">
                    <?= $letter['name'] ?> (ID:<?= $letter['ID'] ?>)
                </a>
            </h2>
            <div x-show="show">
                <h3>
                    Date
                </h3>
                <ul>
                    <li>
                        <?= format_letter_date($letter['date_day'], $letter['date_month'], $letter['date_year']); ?>
                    </li>
                    <?php if ($letter['date_is_range'] == 1) : ?>
                        <li>
                            Date is range: <?= format_letter_date($letter['range_day'], $letter['range_month'], $letter['range_year']); ?>
                        </li>
                    <?php endif; ?>
                    <?php if ($letter['date_uncertain'] == 1) : ?>
                        <li>
                            Uncertain date
                        </li>
                    <?php endif; ?>
                    <?php if ($letter['date_inferred'] == 1) : ?>
                        <li>
                            Inferred date
                        </li>
                    <?php endif; ?>
                    <?php if ($letter['date_approximate'] == 1) : ?>
                        <li>
                            Approximate date
                        </li>
                    <?php endif; ?>
                    <?php if ($letter['date_marked']) : ?>
                        <li>
                            Date as marked: <?= $letter['date_marked']; ?>
                        </li>
                    <?php endif; ?>
                    <?php if ($letter['date_note']) : ?>
                        <li>
                            Notes on date: <?= $letter['date_note']; ?>
                        </li>
                    <?php endif; ?>
                </ul>
                <h3>
                    Authors
                </h3>
                <?php foreach ($letter['authors'] as $author) : ?>
                    <ul>
                        <li>
                            <em><?= $author['name'] ?></em>
                        </li>
                        <?php if (!empty($author['marked'])) : ?>
                            <li>
                                Marked as: <?= $author['marked'] ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endforeach; ?>
                <?php if ($letter['author_note'] || $letter['author_uncertain'] || $letter['author_inferred']) : ?>
                    <ul>
                        <?php if ($letter['author_note']) :  ?>
                            <li>
                                Notes on authors: <?= $letter['author_note']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($letter['author_uncertain']) :  ?>
                            <li>
                                Uncertain author
                            </li>
                        <?php endif; ?>
                        <?php if ($letter['author_inferred']) :  ?>
                            <li>
                                Inferred author
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                <h3>
                    Recipients
                </h3>
                <?php foreach ($letter['recipients'] as $recipient) : ?>
                    <ul>
                        <li>
                            <em><?= $recipient['name'] ?></em>
                        </li>
                        <?php if (!empty($recipient['marked'])) : ?>
                            <li>
                                Marked as: <?= $recipient['marked'] ?>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($recipient['salutation'])) : ?>
                            <li>
                                Salutation: <?= $recipient['salutation'] ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endforeach; ?>
                <?php if ($letter['recipient_notes'] || $letter['recipient_uncertain'] || $letter['recipient_inferred']) : ?>
                    <ul>
                        <?php if ($letter['recipient_notes']) :  ?>
                            <li>
                                Notes on recipients: <?= $letter['recipient_notes']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($letter['recipient_uncertain']) :  ?>
                            <li>
                                Uncertain recipient
                            </li>
                        <?php endif; ?>
                        <?php if ($letter['recipient_inferred']) :  ?>
                            <li>
                                Inferred recipient
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                <h3>
                    Origin
                </h3>
                <?php foreach ($letter['origins'] as $origin) : ?>
                    <ul>
                        <li>
                            <em><?= $origin['name'] ?></em>
                        </li>
                        <?php if (!empty($origin['marked'])) : ?>
                            <li>
                                Marked as: <?= $origin['marked'] ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endforeach; ?>
                <?php if ($letter['origin_note'] || $letter['origin_uncertain'] || $letter['origin_inferred']) : ?>
                    <ul>
                        <?php if ($letter['origin_note']) :  ?>
                            <li>
                                Notes on origin: <?= $letter['origin_note']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($letter['origin_uncertain']) :  ?>
                            <li>
                                Uncertain origin
                            </li>
                        <?php endif; ?>
                        <?php if ($letter['origin_inferred']) :  ?>
                            <li>
                                Inferred origin
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                <h3>
                    Destination
                </h3>
                <?php foreach ($letter['destinations'] as $destination) : ?>
                    <ul>
                        <li>
                            <em><?= $destination['name'] ?></em>
                        </li>
                        <?php if (!empty($destination['marked'])) : ?>
                            <li>
                                Marked as: <?= $destination['marked'] ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endforeach; ?>
                <?php if ($letter['dest_note'] || $letter['dest_uncertain'] || $letter['dest_inferred']) : ?>
                    <ul>
                        <?php if ($letter['dest_note']) :  ?>
                            <li>
                                Notes on destination: <?= $letter['dest_note']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($letter['dest_uncertain']) :  ?>
                            <li>
                                Uncertain destination
                            </li>
                        <?php endif; ?>
                        <?php if ($letter['dest_inferred']) :  ?>
                            <li>
                                Inferred destination
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                <h3>
                    Manifestations and repositories
                </h3>
                <ul>
                    <?php foreach ($letter['copies'] as $copy) : ?>
                        <?php if ($copy['l_number']) : ?>
                            <li>
                                Letter number: <?= $copy['l_number']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($copy['repository']) : ?>
                            <li>
                                Repository: <?= $copy['repository']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($copy['archive']) : ?>
                            <li>
                                Archive: <?= $copy['archive']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($copy['collection']) : ?>
                            <li>
                                Collection: <?= $copy['collection']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($copy['signature']) : ?>
                            <li>
                                Signature: <?= $copy['signature']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($copy['location_note']) : ?>
                            <li>
                                Notes on location: <?= $copy['location_note']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($copy['type']) : ?>
                            <li>
                                Document type: <?= $copy['type']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($copy['preservation']) : ?>
                            <li>
                                Preservation: <?= $copy['preservation']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($copy['copy']) : ?>
                            <li>
                                Type of copy: <?= $copy['copy']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($copy['manifestation_notes']) : ?>
                            <li>
                                Notes on nanifestation: <?= $copy['manifestation_notes']; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php if ($letter['abstract']) : ?>
                    <h3>
                        Abstract
                    </h3>
                    <ul>
                        <li>
                            <?= $letter['abstract'] ?>

                        </li>
                    </ul>
                <?php endif; ?>
                <?php if ($letter['incipit']) : ?>
                    <h3>
                        Incipit
                    </h3>
                    <ul>
                        <li>
                            <?= $letter['incipit'] ?>

                        </li>
                    </ul>
                <?php endif; ?>
                <?php if ($letter['explicit']) : ?>
                    <h3>
                        Explicit
                    </h3>
                    <ul>
                        <li>
                            <?= $letter['explicit'] ?>
                        </li>
                    </ul>
                <?php endif; ?>
                <?php if ($letter['people_mentioned_notes'] || $letter['pm']) : ?>
                    <h3>
                        People mentioned
                    </h3>
                    <ul>
                        <?php foreach ((array) $letter['pm'] as $person) : ?>
                            <li>
                                <?= $person ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($letter['people_mentioned_notes']) : ?>
                        <ul>
                            <li>
                                Note on people mentioned: <?= $letter['people_mentioned_notes'] ?>
                            </li>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($letter['languages']) : ?>
                    <h3>
                        Languages
                    </h3>
                    <ul>
                        <?php foreach (explode(';', $letter['languages']) as $lang) : ?>
                            <li>
                                <?= $lang; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($letter['keyword']) : ?>
                    <h3>
                        Keywords
                    </h3>
                    <ul>
                        <?php foreach ((array) $letter['keyword'] as $kw) : ?>
                            <li>
                                <?= $kw ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($letter['related_resources']) : ?>
                    <h3>
                        Related resources
                    </h3>
                    <ul>
                        <?php foreach ((array) $letter['related_resources'] as $rr) : ?>
                            <li>
                                <?= $rr['title'] ?>
                                <?php if ($rr['link']) : ?>
                                    <a href="<?= $rr['link']; ?>" target="_blank">
                                        <?= $rr['link']; ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($letter['notes_public'] || $letter['notes_private']) : ?>
                    <h3>
                        Notes
                    </h3>
                    <ul>
                        <?php if ($letter['notes_public']) : ?>
                            <li>
                                <?= $letter['notes_public']; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($letter['notes_private']) : ?>
                            <li>
                                <?= $letter['notes_private']; ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($letter['copyright']) : ?>
                    <h3>
                        Copyright
                    </h3>
                    <ul>
                        <li>
                            <?= $letter['copyright']; ?>
                        </li>
                    </ul>
                <?php endif; ?>
                <h3>
                    Status: <?= $letter['status']; ?>
                </h3>
            </div>
        </article>
    <?php endforeach; ?>
</body>

</html>
