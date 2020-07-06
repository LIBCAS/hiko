<?php

$pods_types = get_hiko_post_types_by_url();
$profession_type = $pods_types['profession'];

?>
<div id="datatable-profession">
    <div class="mb-3">
        <button type="button" class="btn btn-lg btn-primary" @click="addProfession('<?= $profession_type; ?>', 'add', null)">
            Přidat novou profesi
        </button>
    </div>
    <div v-if="error" class="alert alert-warning">
        {{ error }}
    </div>
    <v-client-table :data="tableData" :columns="columns" :options="options" v-if="tableData.length > 0">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <span @click="addProfession('<?= $profession_type; ?>', 'edit', props.row.id, props.row.name, props.row.namecz)" class="text-success is-link py-1">
                    Upravit
                </span>
            </li>
            <li>
                <span @click="deleteProfession(props.row.id)" class="is-link py-1">
                    Odstranit
                </span>
            </li>
        </ul>
    </v-client-table>
</div>
