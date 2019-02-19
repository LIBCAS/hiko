<?php if (is_user_logged_in()) : ?>

<div class="ml-auto d-flex align-items-center">
    <div class="mr-1">
        <span class="d-block"><?= get_shortened_name(); ?></span>

    </div>
    <a href="<?= wp_logout_url(home_url()); ?>" class="btn btn-outline-warning">
        Odhlásit se
    </a>
</div>

<?php else : ?>

    <a href="<?= home_url('/login/'); ?>" class="btn btn-outline-success my-2 my-sm-0 ml-auto">Přihlásit se</a>

<?php endif; ?>
