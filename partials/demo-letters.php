<div class="mb-3">
    <a href="<?= home_url('demo/letters-add'); ?>" class="btn btn-lg btn-primary">Přidat nový dopis</a>
    <button type="button" class="btn btn-lg btn-secondary d-none" @click="exportLetters('demo_letter')" id="export">Exportovat</button>
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
                <a :href="'<?= home_url('demo/letters-add/?edit='); ?>' + props.row.id">Upravit</a>
            </li>
            <li>
                <a :href="'<?= home_url('demo/letters-media/?l_type=demo_letter&letter='); ?>' + props.row.id">Obrazové přílohy</a>
            </li>
            <li>
                <a :href="'<?= home_url('letter-preview/?l_type=demo_letter&letter='); ?>' + props.row.id">Náhled</a>
            </li>
            <li>
                <a :href="'#delete-' + props.row.id" @click="deleteLetter(props.row.id)">Odstranit</a>
            </li>
        </ul>

        <span slot="date" slot-scope="props">
            {{ props.row.date_year + '/' + props.row.date_month  + '/' + props.row.date_day }}
        </span>

        <span slot="author" slot-scope="props">
            <span v-if="!Array.isArray(props.row.author)">{{ props.row.author }}</span>
            <ul v-else class="list-unstyled">
                <li v-for="author in props.row.author"> {{ author }}</li>
            </ul>
        </span>

        <span slot="recipient" slot-scope="props">
            <span v-if="!Array.isArray(props.row.recipient)">{{ props.row.recipient }}</span>
            <ul v-else class="list-unstyled">
                <li v-for="recipient in props.row.recipient"> {{ recipient }}</li>
            </ul>
        </span>

        <span slot="origin" slot-scope="props">
            <span v-if="!Array.isArray(props.row.origin)">{{ props.row.origin }}</span>
            <ul v-else class="list-unstyled">
                <li v-for="o in props.row.origin"> {{ o }}</li>
            </ul>
        </span>

        <span slot="dest" slot-scope="props">
            <span v-if="!Array.isArray(props.row.dest)">{{ props.row.dest }}</span>
            <ul v-else class="list-unstyled">
                <li v-for="d in props.row.dest"> {{ d }}</li>
            </ul>
        </span>

        <span slot="status" slot-scope="props">
            <span v-if="props.row.status"> {{ props.row.status }}</span>
            <span v-else class="text-danger">Ke kontrole</span>
        </span>
    </v-client-table>
</div>
