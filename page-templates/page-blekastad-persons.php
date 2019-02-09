<?php

/* Template Name: Blekastad - osoby */

get_header();

?>

<?php require_once get_template_directory() . '/partials/blekastad-nav.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center" style="min-height: 75vh;">

        <div class="col">
            <?php if (user_has_role('administrator') || user_has_role('blekastad_editor')) : ?>
                <h1 class="mb-3">Osoby</h1>
                <?php require_once get_template_directory() . '/partials/blekastad-persons.php'; ?>
            <?php else : ?>
                <div class="alert alert-warning mw-400">
                    Pro zobrazení nemáte patřičná oprávnění.
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php get_footer();
