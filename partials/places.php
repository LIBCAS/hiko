<?php

$pods_types = get_hiko_post_types_by_url();
$place_type = $pods_types['place'];
$path = $pods_types['path'];

$places_json = json_encode(
    get_places_table_data($place_type),
    JSON_UNESCAPED_UNICODE
);
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col">
            <h1 class="mb-3">Místa</h1>
            <div class="mb-3 d-flex justify-content-between">
                <a href="<?= home_url($path . '/places-add'); ?>" class="btn btn-lg btn-primary">Přidat nové místo</a>
                <div class="dropdown d-inline-block" id="export-place" v-cloak>
                    <button @click="openDD = !openDD" v-show="actions.length" class="btn btn-outline-primary btn-lg dropdown-toggle" type="button">
                        Exportovat
                    </button>
                    <div :class="{ 'd-block': openDD }" class="dropdown-menu dropdown-menu-right">
                        <a v-for="action in actions" class="dropdown-item" :href="action.url">{{action.title}}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col">
            <div id="datatable-places" class="mx-auto" style="max-width: 1300px;"></div>
        </div>
    </div>
</div>

<script id="places-data" type="application/json">
    <?= $places_json; ?>
</script>
