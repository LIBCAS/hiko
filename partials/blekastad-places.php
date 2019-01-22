<?php

$places = pods(
    'bl_place',
    [
        'orderby'=> 't.name ASC',
        'limit' => -1
    ]
);

$places_filtered = [];
$index = 0;
while ($places->fetch()) {
    $places_filtered[$index]['id'] = $places->display('id');
    $places_filtered[$index]['city'] = $places->display('name');
    $places_filtered[$index]['country'] = $places->field('country');
    $index++;
}
$places_json = json_encode($places_filtered, JSON_UNESCAPED_UNICODE);
?>

<div class="mb-3">
    <a href="<?= home_url('blekastad/places-add'); ?>" class="btn btn-lg btn-primary">Přidat nové místo</a>
</div>

<div id="datatable-places">

    <v-client-table :data="tableData" :columns="columns" :options="options">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <a :href="'<?= home_url('blekastad/places-add/?edit='); ?>' + props.row.id">Upravit</a>
            </li>
            <li>
                <a :href="'#delete-' + props.row.id">Odstranit</a>
            </li>
        </ul>
    </v-client-table>

</div>

<script id="places-data" type="application/json">
    <?= $places_json; ?>
</script>
