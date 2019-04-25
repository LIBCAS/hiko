<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historická korespondence</title>

    <link rel="stylesheet" href="<?= get_template_directory_uri(); ?>/assets/open-iconic/font/css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="https://transloadit.edgly.net/releases/uppy/v0.29.1/dist/uppy.min.css">
    <link rel="stylesheet" href="https://unpkg.com/vue-select@latest/dist/vue-select.css">
    <link rel="stylesheet" href="<?= get_template_directory_uri() . '/assets/dist/main.css?ver=' . filemtime(get_template_directory() . '/assets/dist/main.css'); ?>">

    <script type="text/javascript">
        var ajaxUrl = '<?= admin_url('admin-ajax.php'); ?>';
        var homeUrl = '<?= home_url(); ?>';
    </script>
    <?php wp_head(); ?>
    <?php require_once 'partials/favicon.php'; ?>
</head>

<body>
