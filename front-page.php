<?php

get_header();

require 'partials/general-nav.php';

?>

<div class="container mt-5">
    <div class="row" style="min-height: 75vh;">

        <div class="col-md">
            <div class="card mb-3 mw-400">
                <h3 class="card-header">Korespondence Milady Blekastad</h3>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?= home_url('/blekastad/letters/') ?>" class="card-link">Dopisy</a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?= home_url('/blekastad/persons/') ?>" class="card-link">Lidé</a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?= home_url('/blekastad/places/') ?>" class="card-link">Místa</a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>


<?php get_footer();
