<?php

$pods_types = get_hiko_post_types_by_url();
$letter_type = $pods_types['letter'];
$path = $pods_types['path'];

$is_supervisor = get_user_meta(get_current_user_id(), 'supervisor', true) == '1';

if ($is_supervisor) {
    $editors = get_editors_by_role($pods_types['editor']);
}

?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col">
            <h1 class="mb-3">Dopisy</h1>
            <div class="d-flex justify-content-between align-items-start">
                <a href="<?= home_url($path . '/letters-add'); ?>" class="btn btn-lg btn-primary">Přidat nový dopis</a>
                <div class="flex-column d-flex align-items-end">
                    <div x-data="{ opened: false }" class="dropdown d-inline-block" x-cloak>
                        <button @click="opened = !opened" class="btn btn-outline-primary btn-lg dropdown-toggle" type="button">
                            Exportovat
                        </button>
                        <div class="d-flex">
                            <div x-bind:class="{ 'd-block': opened }" @click.away="opened = false" class="dropdown-menu dropdown-menu-right">
                                <?php if ($path === 'tgm') : ?>
                                    <a class="dropdown-item" href=" <?= admin_url('admin-ajax.php') . '?action=export_palladio_tgm' ?>">
                                        Palladio – vše
                                    </a>
                                    <a class="dropdown-item" href=" <?= admin_url('admin-ajax.php') . '?action=export_palladio_masaryk&format=csv&from=1' ?>">
                                        Palladio – dopisy od TGM
                                    </a>
                                    <a class="dropdown-item" href=" <?= admin_url('admin-ajax.php') . '?action=export_palladio_masaryk&format=csv&from=0' ?>">
                                        Palladio – dopisy pro TGM
                                    </a>
                                <?php else : ?>
                                    <a class="dropdown-item" href=" <?= admin_url('admin-ajax.php') . '?action=export_palladio&format=csv&type=' . $path ?>">
                                        Palladio – vše
                                    </a>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                    <a href="<?= home_url('browse-letters/?l_type=' . $letter_type) ?>" class="mt-2">
                        Zobrazit všechny dopisy
                    </a>
                </div>
            </div>
            <div id="custom-filters" class="mb-2 d-none">
                <?php if ($is_supervisor) : ?>
                    <select id="editors-letters-filter" class="w-auto custom-select custom-select-sm" autocomplete="off">
                        <option selected="true" value="all">Všichni editoři</option>
                        <?php foreach ($editors as $editor) : ?>
                            <option value="<?= $editor->first_name . ' ' . $editor->last_name; ?>">
                                <?= $editor->first_name . ' ' . $editor->last_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <div id="my-letters-filter">
                        Zobrazit
                        <div class="ml-2 form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="letters-filter" id="my" value="my">
                            <label class="form-check-label" for="my">mé dopisy</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="letters-filter" id="all" value="all" checked>
                            <label class="form-check-label" for="all">všechny dopisy</label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col">
            <div id="datatable-letters" class="mx-auto" style="max-width: 1300px;"></div>
        </div>
    </div>
</div>

<script id="categories-data" type="application/json">
    <?= json_encode(list_keywords($pods_types['keyword'], 1, false)); ?>
</script>
