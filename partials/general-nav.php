<?php
$navData = [
    'Blekastad' => 'blekastad',
    'Korespondence TGM' => 'tgm',
    'Amandus Polanus' => 'pol',
    'Alois Musil' => 'musil',
    'Sachs' => 'sachs',
    'Marci' => 'marci',
    'Wirth' => 'wirth',
    'JAK' => 'jak',
    'Pobělohorští učenci' => 'pbu',
    'Kalivoda' => 'kal',
    'Zkušební DB' => 'demo',
];
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container" x-data="{ openedMenu: false, openedDD: false }">
        <a class="navbar-brand" href="<?= home_url() ?>">HIKO</a>
        <button @click="openedMenu = !openedMenu" class="navbar-toggler" type="button" aria-controls="navbarSupportedContent" x-bind:aria-expanded="openedMenu" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent" :class="{ 'show': openedMenu }">
            <ul class="navbar-nav">
                <li class="nav-item dropdown" :class="{ 'show': openedDD }">
                    <a @click="openedDD = !openedDD" class="nav-link dropdown-toggle" href="#" id="letters-dd" role="button" aria-haspopup="true" x-bind:aria-expanded="openedDD">
                        Korespondence
                    </a>
                    <div x-show="openedDD" class="dropdown-menu" :class="{ 'show': openedDD }" aria-labelledby="letters-dd">
                        <?php foreach ($navData as $key => $value) : ?>
                            <a class="dropdown-item" href="<?= home_url('#' . $value) ?>">
                                <?= $key; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
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
