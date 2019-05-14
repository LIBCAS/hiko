<?php

get_header();
require 'partials/general-nav.php';
$page_id = get_queried_object_id();

?>
<div class="container mt-5">
    <div class="row" style="min-height: 75vh;">
        <div class="col main-content">
            <?php while (have_posts()) : ?>
                <?php the_post(); ?>
                <h1><?php the_title(); ?></h1>
                <div class="content">
                    <?php the_content(); ?>
                </div>
                <ul>
                    <?php
                    wp_list_pages([
                        'child_of' => $page_id,
                        'title_li' => ''
                    ]);
                    ?>
                </ul>
            <?php endwhile; ?>
        </div>
    </div>


    <?php get_footer();
