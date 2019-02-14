<div id="location">

    <div v-if="loading" class="progress my-5">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 65%">
        </div>
    </div>

    <div v-if="!loading" class="section my-5" id="repository">

        <h3>Instituce / repozitáře</h3>

        <button @click="insertItem('repository', 'Nový repozitář', 'add', null)" type="button" class="btn btn-primary btn-sm my-2">Přidat novou instituci</button>

        <table class="table-sm table-bordered table-hover table-striped mt-3">
            <thead>
                <tr>
                    <td style="width:10%">Akce</td>
                    <td>Název</td>
                </tr>
            </thead>
            <tbody>
                <tr v-for="rep in repositories" :key="rep.id">
                    <td>
                        <ul class="list-unstyled mb-0">
                            <li>
                                <span @click="insertItem('repository', 'Upravit: ' + rep.name, 'edit', rep.id)" class="text-success pointer py-1">Upravit</span>
                            </li>
                            <li>
                                <span @click="deleteItem(rep.name, rep.id)" class="text-success pointer py-1">Smazat</span>
                            </li>
                        </ul>
                    </td>
                    <td>{{ rep.name }}</td>
                </tr>
            </tbody>
        </table>

    </div>

    <div v-if="!loading" class="section my-5" id="collection">

        <h3>Sbírky / fondy</h3>

        <button @click="insertItem('collection', 'Nová sbírka', 'add', null)" type="button" class="btn btn-primary btn-sm my-2">Přidat novou instituci</button>

        <table class="table-sm table-bordered table-hover table-striped mt-3">
            <thead>
                <tr>
                    <td style="width:10%">Akce</td>
                    <td>Název</td>
                </tr>
            </thead>
            <tbody>
                <tr v-for="coll in collections" :key="coll.id">
                    <td>
                        <ul class="list-unstyled mb-0">
                            <li>
                                <span @click="insertItem('collection', 'Upravit: ' + coll.name, 'edit', coll.id)" class="text-success pointer py-1">Upravit</span>
                            </li>
                            <li>
                                <span @click="deleteItem(coll.name, coll.id)" class="text-success pointer py-1">Smazat</span>
                            </li>
                        </ul>
                    </td>
                    <td>{{ coll.name }}</td>
                </tr>
            </tbody>
        </table>

    </div>

    <div v-if="!loading" class="section my-5" id="archive">

        <h3>Archivy</h3>

        <button @click="insertItem('archive', 'Nový archiv', 'add', null)" type="button" class="btn btn-primary btn-sm my-2">Přidat novou instituci</button>

        <table class="table-sm table-bordered table-hover table-striped mt-3">
            <thead>
                <tr>
                    <td style="width:10%">Akce</td>
                    <td>Název</td>
                </tr>
            </thead>
            <tbody>
                <tr v-for="a in archives" :key="a.id">
                    <td>
                        <ul class="list-unstyled mb-0">
                            <li>
                                <span @click="insertItem('archive', 'Upravit: ' + a.name, 'edit', a.id)" class="text-success pointer py-1">Upravit</span>
                            </li>
                            <li>
                                <span @click="deleteItem(a.name, a.id)" class="text-success pointer py-1">Smazat</span>
                            </li>
                        </ul>
                    </td>
                    <td>{{ a.name }}</td>
                </tr>
            </tbody>
        </table>

    </div>

    <div v-if="error" class="alert alert-warning">
        {{ error }}
    </div>

</div>
