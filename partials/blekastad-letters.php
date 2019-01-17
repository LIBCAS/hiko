<?php

$letters_pods = pods(
    'bl_letter',
    [
        'orderby'=> 't.name ASC',
        'limit' => -1
    ]
);

$letters = [];
$index = 0;
while ($letters_pods->fetch()) {
    $letters[$index]['id'] = $letters_pods->display('id');
    $letters[$index]['number'] = $letters_pods->field('l_number');
    $letters[$index]['day'] = $letters_pods->field('date_day');
    $letters[$index]['month'] = $letters_pods->field('date_month');
    $letters[$index]['year'] = $letters_pods->field('date_year');
    $letters[$index]['author'] = get_array_name($letters_pods->field('l_author'));
    $letters[$index]['recipient'] = get_array_name($letters_pods->field('recipient'));
    $letters[$index]['origin'] = get_array_name($letters_pods->field('origin'));
    $letters[$index]['dest'] = get_array_name($letters_pods->field('dest'));
    $letters[$index]['status'] = $letters_pods->field('status');
    $index++;
}

$letters = json_encode($letters, JSON_UNESCAPED_UNICODE);

?>

<div class="mb-3">
    <a href="<?= home_url('blekastad/letters-add'); ?>" class="btn btn-lg btn-primary">Přidat nový dopis</a>
    <!--<a href="#" class="btn btn-lg btn-secondary">Exportovat</a>-->
</div>

<div id="datatable-letters">
    <v-client-table :data="tableData" :columns="columns" :options="options"  v-if="tableData">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <a :href="'#edit-' + props.row.id">Upravit</a>
            </li>
            <li>
                <a :href="'#view-' + props.row.id">Zobrazit</a>
            </li>
            <li>
                <a :href="'#delete-' + props.row.id">Odstranit</a>
            </li>
        </ul>
        <span slot="date" slot-scope="props">
            {{ props.row.year + '/' + props.row.month  + '/' + props.row.day }}
         </span>
    </v-client-table>
</div>


<script id="letters-data" type="application/json">
    <?= $letters; ?>
</script>
