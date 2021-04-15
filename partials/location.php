<div class="my-3 alert alert-warning mw-400">
    Seznam uložení je sdílený mezi všemi zapojenými korespondencemi.
</div>
<div x-data="locationForm()" x-init="fetch()">
    <div x-show="loading" class="my-5 progress">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 65%">
        </div>
    </div>
    <div x-show="!loading" class="mb-5 section" id="repository">
        <h3>Instituce / repozitáře</h3>
        <button @click="insertItem('repository', 'Nový repozitář', 'add', '', null)" type="button" class="my-2 btn btn-primary btn-sm">
            Přidat novou instituci
        </button>
        <table class="mt-3 table-sm table-bordered table-hover table-striped">
            <thead>
                <tr>
                    <td style="width:10%">Akce</td>
                    <td>Název</td>
                </tr>
            </thead>
            <tbody>
                <template x-for="repository in repositories" :key="repository.id">
                    <tr>
                        <td>
                            <ul class="mb-0 list-unstyled">
                                <li>
                                    <span @click="insertItem('repository', 'Upravit', 'edit', repository.name, repository.id)" class="py-1 text-info pointer">
                                        Upravit
                                    </span>
                                </li>
                                <li>
                                    <span @click="deleteItem(repository.name, repository.id)" class="py-1 text-danger pointer">
                                        Smazat
                                    </span>
                                </li>
                            </ul>
                        </td>
                        <td x-html="repository.name"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div x-show="!loading" class="my-5 section" id="collection">
        <h3>Sbírky / fondy</h3>
        <button @click="insertItem('collection', 'Nová sbírka', 'add', '', null)" type="button" class="my-2 btn btn-primary btn-sm">
            Přidat novou sbírku
        </button>
        <table class="mt-3 table-sm table-bordered table-hover table-striped">
            <thead>
                <tr>
                    <td style="width:10%">Akce</td>
                    <td>Název</td>
                </tr>
            </thead>
            <tbody>
                <template x-for="collection in collections" :key="collection.id">
                    <tr>
                        <td>
                            <ul class="mb-0 list-unstyled">
                                <li>
                                    <span @click="insertItem('collection', 'Upravit', 'edit', collection.name, collection.id)" class="py-1 text-info pointer">
                                        Upravit
                                    </span>
                                </li>
                                <li>
                                    <span @click="deleteItem(collection.name, collection.id)" class="py-1 text-danger pointer">
                                        Smazat
                                    </span>
                                </li>
                            </ul>
                        </td>
                        <td x-html="collection.name"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div x-show="!loading" class="my-5 section" id="archive">
        <h3>Archivy</h3>
        <button @click="insertItem('archive', 'Nový archiv', 'add', '', null)" type="button" class="my-2 btn btn-primary btn-sm">
            Přidat nový archiv
        </button>
        <table class="mt-3 table-sm table-bordered table-hover table-striped">
            <thead>
                <tr>
                    <td style="width:10%">Akce</td>
                    <td>Název</td>
                </tr>
            </thead>
            <tbody>
                <template x-for="archive in archives" :key="archive.id">
                    <tr>
                        <td>
                            <ul class="mb-0 list-unstyled">
                                <li>
                                    <span @click="insertItem('archive', 'Upravit', 'edit', archive.name, archive.id)" class="py-1 text-info pointer">
                                        Upravit
                                    </span>
                                </li>
                                <li>
                                    <span @click="deleteItem(archive.name, archive.id)" class="py-1 text-danger pointer">
                                        Smazat
                                    </span>
                                </li>
                            </ul>
                        </td>
                        <td x-html="archive.name"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
