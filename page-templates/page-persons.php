<?php

/* Template Name: Osoby */

get_header();

$pods_types = get_hiko_post_types_by_url();
$editor = $pods_types['editor'];
output_current_type_script($pods_types);
require_once get_template_directory() . '/partials/custom-nav.php';

if (has_user_permission($editor)) :
    require_once get_template_directory() . '/partials/persons.php';
else : ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col">
                <div class="alert alert-warning mw-400">
                    Pro zobrazení nemáte patřičná oprávnění.
                </div>
            </div>
        </div>
    </div>
    <?php
endif;

get_footer();

