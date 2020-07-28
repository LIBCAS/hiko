<?php

$pods_types = get_hiko_post_types_by_url();
$path = $pods_types['path'];
?>

<div class="mb-3 d-flex justify-content-between">
    <a href="<?= home_url($path . '/persons-add'); ?>" class="btn btn-lg btn-primary">Přidat novou osobu / instituci</a>
    <div class="dropdown d-inline-block" id="export-person">
        <button @click="openDD = !openDD" v-show="actions.length" class="btn btn-outline-primary btn-lg dropdown-toggle" type="button">
            Exportovat
        </button>
        <div :class="{ 'd-block': openDD }" class="dropdown-menu dropdown-menu-right">
            <a v-for="action in actions" class="dropdown-item" :href="action.url">{{action.title}}</a>
        </div>
    </div>
</div>

<div id="datatable-persons">
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
                <a :href="'<?= home_url($path . '/persons-add/?edit='); ?>' + props.row.id">Upravit</a>
            </li>
            <li v-if="props.row.relationships == 0" >
                <span class="is-link py-1" @click="deletePerson(props.row.id)">
                    Odstranit
                </span>
            </li>
        </ul>
        <span slot="name" slot-scope="props">
            <strong v-html="props.row.name"></strong>
            <span v-show="props.row.type != 'institution'">
                ({{ props.row.birth + '–' + props.row.death }})
            </span>
        </span>
        <ul v-if="props.row.alternatives" slot="alternatives" slot-scope="props" class="list-unstyled">
            <li v-for="(name, index) in props.row.alternatives" :key="index" v-html="name"></li>
            <li v-if="props.row.alternatives.length > 0" >
                <span class="is-link py-1" @click="removeEmptyNameAlternatives(props.row.id)">
                <span class="spinner spinner-border spinner-border-sm d-none"></span>
                    Odstranit nepoužité varianty jména
                </span>
            </li>
        </ul>
        <span slot="type" slot-scope="props">
            {{ props.row.type ? props.row.type : 'person' }}
        </span>
    </v-client-table>
</div>
