<?php
$error = false;

if (!array_key_exists('l_type', $_GET)) {
    $error = true;
} elseif (!array_key_exists('letter', $_GET)) {
    $error = true;
} else {
    $pod = pods('bl_letter', $_GET['letter']);

    if (!$pod->exists()) {
        $error = true;
    }
}
?>


<?php if ($error) : ?>
    <div class="alert alert-warning">
        Nepodařilo se načíst požadovaný dopis.
    </div>
<?php else : ?>
    <div class="" id="media-handler">
        <h3>Dopis: <?= $pod->field('name'); ?></h3>
        <div class="section mb-5">
            <h4>Nahrát obrazové přílohy</h4>
            <div id="drag-drop-area"></div>
        </div>
        <div class="section mb-5">
            <h4>Upravit nahrané obrazové přílohy</h4>

        </div>
    </div>
<?php endif; ?>
