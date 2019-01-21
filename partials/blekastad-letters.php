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
    $authors = [];
    $recipients = [];
    $authors_related = $letters_pods->field('l_author');
    $recipients_related = $letters_pods->field('recipient');

    if (!empty($authors_related)) {
        foreach ($authors_related as $rel_author) {
            $authors[] = $rel_author['name'];
        }
    }

    if (!empty($recipients_related)) {
        foreach ($recipients_related as $rel_recipient) {
            $recipients[] = $rel_recipient['name'];
        }
    }

    $letters[$index]['id'] = $letters_pods->display('id');
    $letters[$index]['number'] = $letters_pods->field('l_number');
    $letters[$index]['day'] = $letters_pods->field('date_day');
    $letters[$index]['month'] = $letters_pods->field('date_month');
    $letters[$index]['year'] = $letters_pods->field('date_year');
    $letters[$index]['author'] = $authors;
    $letters[$index]['recipient'] = $recipients;
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
         <span slot="author" slot-scope="props">
             <ul class="list-unstyled">
                <li v-for="author in props.row.author"> {{ author }}</li>
             </ul>
          </span>
          <span slot="recipient" slot-scope="props">
              <ul class="list-unstyled">
                 <li v-for="recipient in props.row.recipient"> {{ recipient }}</li>
              </ul>
           </span>
    </v-client-table>
</div>


<script id="letters-data" type="application/json">
    <?= $letters; ?>
</script>
