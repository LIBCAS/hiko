<?php

$pods_types = get_hiko_post_types_by_url();
$place_type = $pods_types['place'];
$path = $pods_types['path'];

$places_json = json_encode(
    get_places_table_data($place_type),
    JSON_UNESCAPED_UNICODE
);
?>

<div class="mb-3 d-flex justify-content-between">
    <a href="<?= home_url($path . '/places-add'); ?>" class="btn btn-lg btn-primary">Přidat nové místo</a>
    <div class="dropdown d-inline-block" id="export-place">
        <button @click="openDD = !openDD" v-show="actions.length" class="btn btn-outline-primary btn-lg dropdown-toggle" type="button">
            Exportovat
        </button>
        <div :class="{ 'd-block': openDD }" class="dropdown-menu dropdown-menu-right">
            <a v-for="action in actions" class="dropdown-item" :href="action.url">{{action.title}}</a>
        </div>
    </div>
</div>

<div id="datatable-places">
    <v-client-table :data="tableData" :columns="columns" :options="options">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <a :href="'<?= home_url($path . '/places-add/?edit='); ?>' + props.row.id">Upravit</a>
            </li>
            <li>
                <a v-if="!props.row.relationships" :href="'#delete-' + props.row.id" @click="deletePlace(props.row.id)">Odstranit</a>
            </li>
        </ul>
        <span slot="city" slot-scope="props">
            <span v-html="props.row.city"></span>
        </span>
        <span slot="latlong" slot-scope="props">
            <a v-if="props.row.latlong" :href="props.row.latlong | mapLink" target="_blank">
                {{ props.row.latlong }}
            </a>
        </span>
    </v-client-table>
</div>

<script id="places-data" type="application/json">
    <?= $places_json; ?>
</script>
