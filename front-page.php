<?php

get_header();

require 'partials/general-nav.php';

$cardsData = [
    'Korespondence Milady Blekastad' => 'blekastad',
    'Korespondence TGM' => 'tgm',
    'Zkušební DB' => 'demo',
];

?>

<div class="container mt-5">
    <div class="row" style="min-height: 75vh;">
        <?php foreach ($cardsData as $key => $value) : ?>
            <div class="col-md">
                <div class="card mb-3 mw-400">
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
            </div>
        <?php endforeach; ?>
    </div>
</div>


<?php get_footer();
