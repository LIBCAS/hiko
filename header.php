<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historická korespondence</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slim-select@1.18.6/dist/slimselect.min.css">
    <link rel="stylesheet" href="<?= get_template_directory_uri(); ?>/assets/open-iconic/font/css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="https://transloadit.edgly.net/releases/uppy/v0.29.1/dist/uppy.min.css">
    <link rel="stylesheet" href="<?= get_template_directory_uri() . '/assets/dist/main.min.css?ver=' . filemtime(get_template_directory() . '/assets/dist/main.min.css'); ?>">
    <script type="text/javascript">
        var ajaxUrl = '<?= admin_url('admin-ajax.php'); ?>';
    </script>
    <?php wp_head(); ?>
</head>

<body>
