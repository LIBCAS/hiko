<?php

$pods_types = get_hiko_post_types_by_url();
$path = $pods_types['path'];
$profession_type = $pods_types['profession'];
?>

<div class="mb-3 d-flex justify-content-between">
    <a href="<?= home_url($path . '/persons-add'); ?>" class="btn btn-lg btn-primary">Přidat novou osobu / instituci</a>
    <div class="dropdown d-inline-block" id="export-person" v-cloak>
        <button @click="openDD = !openDD" v-show="actions.length" class="btn btn-outline-primary btn-lg dropdown-toggle" type="button">
            Exportovat
        </button>
        <div :class="{ 'd-block': openDD }" class="dropdown-menu dropdown-menu-right">
            <a v-for="action in actions" class="dropdown-item" :href="action.url">{{action.title}}</a>
        </div>
    </div>
</div>
<div id="datatable-persons"></div>

<script id="professions" type="application/json">
    <?= get_professions_list($profession_type, false); ?>
</script>
