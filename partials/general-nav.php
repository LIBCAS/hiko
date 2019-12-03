<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="<?= home_url() ?>">HIKO</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="blekastad-dd" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Blekastad
                    </a>
                    <div class="dropdown-menu" aria-labelledby="blekastad-dd">
                        <a class="dropdown-item" href="<?= home_url('/blekastad/letters/') ?>">Dopisy</a>
                        <a class="dropdown-item" href="<?= home_url('/blekastad/persons/') ?>">Lidé a instituce</a>
                        <a class="dropdown-item" href="<?= home_url('/blekastad/places/') ?>">Místa</a>
                        <a class="dropdown-item" href="<?= home_url('/blekastad/keywords/') ?>">Klíčová slova</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="demo-tgm" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Korespondence TGM
                    </a>
                    <div class="dropdown-menu" aria-labelledby="demo-tgm">
                        <a class="dropdown-item" href="<?= home_url('/tgm/letters/') ?>">Dopisy</a>
                        <a class="dropdown-item" href="<?= home_url('/tgm/persons/') ?>">Lidé</a>
                        <a class="dropdown-item" href="<?= home_url('/tgm/places/') ?>">Místa</a>
                        <a class="dropdown-item" href="<?= home_url('/tgm/keywords/') ?>">Klíčová slova</a>

                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="demo-dd" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Zkušební DB
                    </a>
                    <div class="dropdown-menu" aria-labelledby="demo-dd">
                        <a class="dropdown-item" href="<?= home_url('/demo/letters/') ?>">Dopisy</a>
                        <a class="dropdown-item" href="<?= home_url('/demo/persons/') ?>">Lidé</a>
                        <a class="dropdown-item" href="<?= home_url('/demo/places/') ?>">Místa</a>
                        <a class="dropdown-item" href="<?= home_url('/demo/keywords/') ?>">Klíčová slova</a>
                    </div>
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
