<?php

$pods_types = get_hiko_post_types_by_url();
$profession_type = $pods_types['profession'];

?>

<div class="mb-3">
    <button type="button" class="btn btn-lg btn-primary" onclick="addProfession('<?= $profession_type; ?>', 'add', null)">
        Přidat novou profesi
    </button>
</div>
<div id="datatable-profession"></div>
