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
                        <a class="dropdown-item" href="<?= home_url('/blekastad/persons/') ?>">Lidé</a>
                        <a class="dropdown-item" href="<?= home_url('/blekastad/places/') ?>">Místa</a>
                    </div>
                </li>
            </ul>
            <?php require 'menu-login.php'; ?>
        </div>
    </div>
</nav>
