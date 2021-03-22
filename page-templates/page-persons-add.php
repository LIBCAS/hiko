<?php

/* Template Name: Osoby, přidání */

get_header();

$pods_types = get_hiko_post_types_by_url();
$editor = $pods_types['editor'];
output_current_type_script($pods_types);
require_once get_template_directory() . '/partials/custom-nav.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center" style="min-height: 75vh;">

        <div class="col-lg-6 col-md-8">
            <?php if (has_user_permission($editor)) : ?>
                <?php if (array_key_exists('edit', $_GET)) : ?>
                    <h1>Editovat osobu / instituci</h1>
                <?php else : ?>
                    <h1>Nová osoba / instituce</h1>
                <?php endif; ?>
                <?php require_once get_template_directory() . '/partials/persons-add.php'; ?>
            <?php else : ?>
                <div class="alert alert-warning mw-400">
                    Pro zobrazení nemáte patřičná oprávnění.
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php get_footer();
