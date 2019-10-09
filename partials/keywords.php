<?php

$pods_types = get_hiko_post_types_by_url();
$keyword_type = $pods_types['keyword'];

?>
<div id="datatable-keywords">
    <div class="mb-3">
        <button type="button" class="btn btn-lg btn-primary" @click="addKeyword('<?= $keyword_type; ?>', 'add', null)">Přidat nové klíčové slovo</button>
    </div>
    <div v-if="error" class="alert alert-warning">
        {{ error }}
    </div>
    <v-client-table :data="tableData" :columns="columns" :options="options" v-if="tableData.length > 0">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <span @click="addKeyword('<?= $keyword_type; ?>', 'edit', props.row.id, props.row.name, props.row.namecz)" class="text-success is-link py-1">
                    Upravit
                </span>
            </li>
            <li>
                <span @click="deleteKeyword(props.row.id)" class="is-link py-1">
                    Odstranit
                </span>
            </li>
        </ul>
    </v-client-table>
</div>
