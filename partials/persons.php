<?php

$pods_types = get_hiko_post_types_by_url();
$path = $pods_types['path'];
$profession_type = $pods_types['profession'];
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col">
            <h1 class="mb-3">Osoby a instituce</h1>
            <div class="mb-3 d-flex justify-content-between">
                <a href="<?= home_url($path . '/persons-add'); ?>" class="btn btn-lg btn-primary">Přidat novou osobu / instituci</a>
                <div x-data="{ opened: false }" class="dropdown d-inline-block" x-cloak>
                    <button @click="opened = !opened" class="btn btn-outline-primary btn-lg dropdown-toggle" type="button">
                        Exportovat
                    </button>
                    <div x-bind:class="{ 'd-block': opened }" @click.away="opened = false" class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href=" <?= admin_url('admin-ajax.php') . '?action=export_persons&format=csv&type=' . $pods_types['path'] ?>">
                            Lidé a instituce
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col">
            <div id="datatable-persons" class="mx-auto" style="max-width: 1300px;"></div>
        </div>
    </div>
</div>

