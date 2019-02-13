<?php

/* Template Name: Společné údaje o uložení */

get_header();

?>

<?php require_once get_template_directory() . '/partials/general-nav.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center" style="min-height: 75vh;">
        <div class="col">
            <h1>Údaje o uložení</h1>

            <?php if (user_has_role('administrator') || user_has_role('blekastad_editor')) : ?>
                <?php require_once get_template_directory() . '/partials/location.php'; ?>
            <?php else : ?>
                <div class="alert alert-warning mw-400">
                    Pro zobrazení nemáte patřičná oprávnění.
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php get_footer();
