<?php

$pods_types = get_hiko_post_types_by_url();
$letter_type = $pods_types['letter'];
$path = $pods_types['path'];

?>

<div class="mb-3 d-flex justify-content-between">
    <a href="<?= home_url($path . '/letters-add'); ?>" class="btn btn-lg btn-primary">Přidat nový dopis</a>
    <div class="dropdown d-inline-block" id="export">
        <button @click="openDD = !openDD" class="btn btn-outline-primary btn-lg dropdown-toggle" type="button">
            Exportovat
        </button>
        <div :class="{ 'd-block': openDD }" class="dropdown-menu dropdown-menu-right">
            <a v-for="action in actions" class="dropdown-item" :href="action.url">{{action.title}}</a>
        </div>
    </div>
</div>

<div id="datatable-letters">

    <div v-if="loading" class="progress">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 65%">
        </div>
    </div>
    <div v-if="error" class="alert alert-warning">
        {{ error }}
    </div>
    <v-client-table :data="tableData" :columns="columns" :options="options" v-if="tableData.length > 0">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <a :href="'<?= home_url($path . '/letters-add/?edit='); ?>' + props.row.id">Upravit</a>
            </li>
            <li>
                <a :href="'<?= home_url($path . '/letters-media/?l_type=' . $letter_type . '&letter='); ?>' + props.row.id">Obrazové přílohy</a>
            </li>
            <li>
                <a :href="'<?= home_url('letter-preview/?l_type=' . $letter_type . '&letter='); ?>' + props.row.id">Náhled</a>
            </li>
            <li>
                <span @click="showHistory(props.row.id, $event)" class="is-link py-1">
                    <span class="spinner spinner-border spinner-border-sm d-none"></span>
                    Historie úprav
                </span>
            </li>
            <li>
                <span @click="deleteLetter(props.row.id)" class="is-link py-1">
                    Odstranit
                </span>
            </li>
        </ul>

        <span slot="date" slot-scope="props">
            <span v-if="!props.row.date_year">0/</span><span v-else>{{ props.row.date_year }}/</span><!--
            --><span v-if="!props.row.date_month">0/</span><span v-else>{{ props.row.date_month }}/</span><!--
            --><span v-if="!props.row.date_day">0</span><span v-else>{{ props.row.date_day }}</span>
        </span>

        <span slot="author" slot-scope="props">
            <span v-if="!Array.isArray(props.row.author)" v-html="props.row.author"></span>
            <ul v-else class="list-unstyled">
                <li v-for="author in props.row.author" v-html="author"></li>
            </ul>
        </span>

        <span slot="recipient" slot-scope="props">
            <span v-if="!Array.isArray(props.row.recipient)" v-html="props.row.recipient"></span>
            <ul v-else class="list-unstyled">
                <li v-for="recipient in props.row.recipient" v-html="recipient"></li>
            </ul>
        </span>

        <span slot="origin" slot-scope="props">
            <span v-if="!Array.isArray(props.row.origin)" v-html="props.row.origin"></span>
            <ul v-else class="list-unstyled">
                <li v-for="o in props.row.origin" v-html="o"></li>
            </ul>
        </span>

        <span slot="dest" slot-scope="props">
            <span v-if="!Array.isArray(props.row.dest)" v-html="props.row.dest"></span>
            <ul v-else class="list-unstyled">
                <li v-for="d in props.row.dest" v-html="d"></li>
            </ul>
        </span>

        <span slot="images" slot-scope="props">
            <span v-if="props.row.images !== null">ano</span>
        </span>

        <span slot="status" slot-scope="props">
            <span v-if="props.row.status"> {{ props.row.status }}</span>
            <span v-else class="text-danger">Ke kontrole</span>
        </span>
    </v-client-table>
</div>
