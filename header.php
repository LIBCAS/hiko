<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historická korespondence</title>

    <link rel="stylesheet" href="<?= get_template_directory_uri(); ?>/assets/open-iconic/font/css/open-iconic-bootstrap.min.css">
    <?php if (is_page_template('page-templates/page-images.php')) : ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uppy@1.0.0/dist/uppy.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vue-multiselect@2.1.6/dist/vue-multiselect.min.css">
    <link rel="stylesheet" href="<?= get_template_directory_uri() . '/assets/dist/main.css?ver=' . filemtime(get_template_directory() . '/assets/dist/main.css'); ?>">

    <script type="text/javascript">
        var ajaxUrl = '<?= admin_url('admin-ajax.php'); ?>';
        var homeUrl = '<?= home_url(); ?>';
    </script>
    <?php wp_head(); ?>
    <?php require_once 'partials/favicon.php'; ?>
</head>

<body <?php body_class(); ?>>
