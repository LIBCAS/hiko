<?php

$pods_types = get_hiko_post_types_by_url();
$place_type = $pods_types['place'];
$path = $pods_types['path'];

$places_json = json_encode(
    list_places_with_relationships($place_type),
    JSON_UNESCAPED_UNICODE
);
?>

<div class="mb-3">
    <a href="<?= home_url($path . '/places-add'); ?>" class="btn btn-lg btn-primary">Přidat nové místo</a>
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
    </v-client-table>

</div>

<script id="places-data" type="application/json">
    <?= $places_json; ?>
</script>
