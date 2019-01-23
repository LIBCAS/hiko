<?php

$persons = pods(
    'bl_person',
    [
        'orderby'=> 't.surname ASC',
        'limit' => -1
    ]
);

$persons_filtered = [];
$index = 0;
while ($persons->fetch()) {
    $authors = $persons->field('letter_author');
    $recipients = $persons->field('letter_recipient');
    $mentioned = $persons->field('letter_people_mentioned');
    $persons_filtered[$index]['id'] = $persons->display('id');
    $persons_filtered[$index]['name'] = $persons->display('name');
    $persons_filtered[$index]['birth'] = $persons->field('birth_year');
    $persons_filtered[$index]['death'] = $persons->field('death_year');
    $persons_filtered[$index]['relationships'] = sum_array_length([$authors, $recipients, $mentioned]);
    $index++;
}
$persons_json = json_encode($persons_filtered, JSON_UNESCAPED_UNICODE);
?>

<div class="mb-3">
    <a href="<?= home_url('blekastad/persons-add'); ?>" class="btn btn-lg btn-primary">Přidat novou osobu</a>
</div>

<div id="datatable-persons">
    <v-client-table :data="tableData" :columns="columns" :options="options">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <a :href="'<?= home_url('blekastad/persons-add/?edit='); ?>' + props.row.id">Upravit</a>
            </li>
            <li>
                <a v-if="props.row.relationships == 0" :href="'#delete-' + props.row.id" @click="deletePerson(props.row.id)">Odstranit</a>
            </li>
        </ul>

        <span slot="dates" slot-scope="props"> {{ props.row.birth + '–' + props.row.death  }}</span>

    </v-client-table>
</div>

<script id="persons-data" type="application/json">
    <?= $persons_json; ?>
</script>
