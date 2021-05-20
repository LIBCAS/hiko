<?php
$pods_types = get_hiko_post_types_by_url();
$path = $pods_types['path'];
$title = $pods_types['title'];
?>

<nav x-data="{ openedMenu: false, openedDD: false }" class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="<?= home_url($path) ?>"><?= $title ?></a>
        <button @click="openedMenu = !openedMenu" class="navbar-toggler" type="button" aria-controls="navbarSupportedContent" x-bind:aria-expanded="openedMenu" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent" :class="{ 'show': openedMenu }">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url($path . '/letters/') ?>">
                        Dopisy
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url($path . '/persons/') ?>">
                        Lidé / instituce
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url($path . '/places/') ?>">
                        Místa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url($path . '/keywords/') ?>">
                        Klíčová slova
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url($path . '/profession/') ?>">
                        Profese
                    </a>
                </li>
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
