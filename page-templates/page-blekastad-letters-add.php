<?php

/* Template Name: Blekastad - dopisy, přidání */

get_header();

?>

<?php require_once get_template_directory() . '/partials/blekastad-nav.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center" style="min-height: 75vh;">
        <div class="col-lg-6 col-md-8">
            <?php if (user_has_role('administrator') || user_has_role('blekastad_editor')) : ?>
                <?php if (array_key_exists('edit', $_GET)) : ?>
                    <h1>Editovat dopis</h1>
                <?php else : ?>
                    <h1>Nový dopis</h1>
                <?php endif; ?>
                <?php require_once get_template_directory() . '/partials/blekastad-letters-add.php'; ?>
            <?php else : ?>
                <div class="alert alert-warning">
                    Pro zobrazení nemáte patřičná oprávnění.
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php get_footer();