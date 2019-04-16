<?php

$pods_types = get_hiko_post_types_by_url();
$person_type = $pods_types['person'];
$path = $pods_types['path'];

$persons_json = json_encode(
    get_persons_table_data($person_type),
    JSON_UNESCAPED_UNICODE
)
?>

<div class="mb-3">
    <a href="<?= home_url($path . '/persons-add'); ?>" class="btn btn-lg btn-primary">Přidat novou osobu</a>
</div>

<div id="datatable-persons">
    <v-client-table :data="tableData" :columns="columns" :options="options">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <a :href="'<?= home_url($path . '/persons-add/?edit='); ?>' + props.row.id">Upravit</a>
            </li>
            <li>
                <a v-if="props.row.relationships == 0" :href="'#delete-' + props.row.id" @click="deletePerson(props.row.id)">Odstranit</a>
            </li>
        </ul>
        <span slot="name" slot-scope="props">
            <strong>{{ props.row.name }}</strong> ({{ props.row.birth + '–' + props.row.death  }})
        </span>
        <ul slot="alternatives" slot-scope="props" class="list-unstyled">
            <li v-for="(name, index) in props.row.alternatives" :key="index">{{name}}</li>
        </ul>

    </v-client-table>
</div>

<script id="persons-data" type="application/json">
    <?= $persons_json; ?>
</script>
