<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="<?= home_url('/blekastad/letters/') ?>">HIKO – Blekastad</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url('/blekastad/letters/') ?>">
                        Dopisy
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url('/blekastad/persons/') ?>">
                        Lidé
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url('/blekastad/places/') ?>">
                        Místa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= home_url('/location/') ?>">
                        Uložení
                    </a>
                </li>
            </ul>
            <?php require 'menu-login.php'; ?>
        </div>
    </div>
</nav>
