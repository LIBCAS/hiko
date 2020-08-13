<?php

$pods_types = get_hiko_post_types_by_url();
$keyword_type = $pods_types['keyword'];

?>

<div class="mb-3">
    <button type="button" class="btn btn-lg btn-primary" onclick="addKeyword('<?= $keyword_type; ?>', 'add', null)">
        Přidat nové klíčové slovo
    </button>
</div>
<div id="datatable-keywords"></div>
