<?php
$navData = [
    'Blekastad' => 'blekastad',
    'Korespondence TGM' => 'tgm',
    'Amandus Polanus' => 'pol',
    'Zkušební DB' => 'demo',
];
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="<?= home_url() ?>">HIKO</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav">
                <?php foreach ($navData as $key => $value) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="<?= $value; ?>-dd" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?= $key; ?>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="blekastad-dd">
                            <a class="dropdown-item" href="<?= home_url('/' . $value . '/letters/') ?>">Dopisy</a>
                            <a class="dropdown-item" href="<?= home_url('/' . $value . '/persons/') ?>">Lidé a instituce</a>
                            <a class="dropdown-item" href="<?= home_url('/' . $value . '/places/') ?>">Místa</a>
                            <a class="dropdown-item" href="<?= home_url('/' . $value . '/keywords/') ?>">Klíčová slova</a>
                        </div>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url('/location/') ?>">
                        Uložení
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url('/napoveda/') ?>">
                        Nápověda
                    </a>
                </li>
            </ul>
            <?php require 'menu-login.php'; ?>
        </div>
    </div>
</nav>
