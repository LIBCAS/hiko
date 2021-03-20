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
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="letters-dd" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Korespondence
                    </a>
                    <div class="dropdown-menu" aria-labelledby="letters-dd">
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
