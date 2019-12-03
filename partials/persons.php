<?php

$pods_types = get_hiko_post_types_by_url();
$path = $pods_types['path'];
?>

<div class="mb-3">
    <a href="<?= home_url($path . '/persons-add'); ?>" class="btn btn-lg btn-primary">Přidat novou osobu / instituci</a>
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
            <strong>{{ props.row.name }}</strong>
            <span v-show="props.row.type != 'institution'">
                ({{ props.row.birth + '–' + props.row.death }})
            </span>
        </span>
        <ul slot="alternatives" slot-scope="props" class="list-unstyled">
            <li v-for="(name, index) in props.row.alternatives" :key="index">{{name}}</li>

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
