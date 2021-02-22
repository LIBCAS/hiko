<?php

get_header();

require 'partials/general-nav.php';

$cardsData = [
    'Korespondence Milady Blekastad' => 'blekastad',
    'Korespondence TGM' => 'tgm',
    'Korespondence Amanda Polana' => 'pol',
    'Korespondence Aloise Musila' => 'musil',
    'Korespondence Philippa Sachse' => 'sachs',
    'Zkušební DB' => 'demo',
];
?>

<div class="container mt-5">
    <?php foreach ($cardsData as $key => $value) : ?>
        <div class="mb-3 mr-3 align-top card d-inline-block" style="width:320px">
            <h3 class="card-header"><?= $key; ?></h3>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <a href="<?= home_url("/$value/letters/"); ?>" class="card-link">Dopisy</a>
                </li>
                <li class="list-group-item">
                    <a href="<?= home_url("/$value/persons/"); ?>" class="card-link">Lidé a instituce</a>
                </li>
                <li class="list-group-item">
                    <a href="<?= home_url("/$value/places/"); ?>" class="card-link">Místa</a>
                </li>
                <li class="list-group-item">
                    <a href="<?= home_url("/$value/keywords/"); ?>" class="card-link">Klíčová slova</a>
                </li>
                <li class="list-group-item">
                    <a href="<?= home_url("/$value/profession/"); ?>" class="card-link">Profese</a>
                </li>
            </ul>
        </div>
    <?php endforeach; ?>
</div>

<?php get_footer();
