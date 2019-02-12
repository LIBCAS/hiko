<?php

$fields = [
    't.id AS id',
    't.l_number AS number',
    't.date_day AS day',
    't.date_month AS month',
    't.date_year AS year',
    't.status AS state',
    't.created',
];

$fields = implode(', ', $fields);

$letters_pods = pods(
    'bl_letter',
    [
        'select' => $fields,
        'orderby'=> 't.created DESC',
        'limit' => -1
    ]
);

$letters = [];
$index = 0;
while ($letters_pods->fetch()) {
    $authors = [];
    $recipients = [];
    $origins = [];
    $destinations = [];

    $authors_related = $letters_pods->field('l_author');
    $recipients_related = $letters_pods->field('recipient');
    $origins_related = $letters_pods->field('origin');
    $destinations_related = $letters_pods->field('dest');

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

    if (!empty($origins_related)) {
        foreach ($origins_related as $o) {
            $origins[] = $o['name'];
        }
    }

    if (!empty($destinations_related)) {
        foreach ($destinations_related as $d) {
            $destinations[] = $d['name'];
        }
    }

    $letters[$index]['id'] = $letters_pods->field('id');
    $letters[$index]['number'] = $letters_pods->field('number');
    $letters[$index]['day'] = $letters_pods->field('day');
    $letters[$index]['month'] = $letters_pods->field('month');
    $letters[$index]['year'] = $letters_pods->field('year');
    $letters[$index]['author'] = $authors;
    $letters[$index]['recipient'] = $recipients;
    $letters[$index]['origin'] = $origins;
    $letters[$index]['dest'] = $destinations;
    $letters[$index]['status'] = $letters_pods->field('state');
    $index++;
}

$letters = json_encode($letters, JSON_UNESCAPED_UNICODE);

?>

<div class="mb-3">
    <a href="<?= home_url('blekastad/letters-add'); ?>" class="btn btn-lg btn-primary">Přidat nový dopis</a>
    <button type="button" class="btn btn-lg btn-secondary d-none" @click="exportLetters('bl_letter')" id="export">Exportovat</button>
</div>

<div id="datatable-letters">
    <v-client-table :data="tableData" :columns="columns" :options="options"  v-if="tableData">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <a :href="'<?= home_url('blekastad/letters-add/?edit='); ?>' + props.row.id">Upravit</a>
            </li>
            <li>
                <a :href="'<?= home_url('blekastad/letters-media/?l_type=bl_letter&letter='); ?>' + props.row.id">Obrazové přílohy</a>
            </li>
            <li>
                <a :href="'<?= home_url('letter-preview/?l_type=bl_letter&letter='); ?>' + props.row.id">Náhled</a>
            </li>
            <li>
                <a :href="'#delete-' + props.row.id" @click="deleteLetter(props.row.id)">Odstranit</a>
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

           <span slot="origin" slot-scope="props">
               <ul class="list-unstyled">
                  <li v-for="o in props.row.origin"> {{ o }}</li>
               </ul>
            </span>

            <span slot="dest" slot-scope="props">
                <ul class="list-unstyled">
                   <li v-for="d in props.row.dest"> {{ d }}</li>
                </ul>
             </span>

             <span slot="status" slot-scope="props">
                 <span v-if="props.row.status"> {{ props.row.status }}</span>
                 <span v-else class="text-danger">Ke kontrole</span>
              </span>
    </v-client-table>
</div>


<script id="letters-data" type="application/json">
    <?= $letters; ?>
</script>
