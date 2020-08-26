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
            <div class="mb-3 d-flex justify-content-between">
                <a href="<?= home_url($path . '/letters-add'); ?>" class="btn btn-lg btn-primary">Přidat nový dopis</a>
                <div class="dropdown d-inline-block" id="export" v-cloak>
                    <button @click="openDD = !openDD" v-show="actions.length" class="btn btn-outline-primary btn-lg dropdown-toggle" type="button">
                        Exportovat
                    </button>
                    <div :class="{ 'd-block': openDD }" class="dropdown-menu dropdown-menu-right">
                        <a v-for="action in actions" class="dropdown-item" :href="action.url">{{action.title}}</a>
                    </div>
                </div>
            </div>
            <div id="custom-filters" class="d-none mb-2">
                <?php if ($is_supervisor) : ?>
                    <select id="editors-letters-filter" class="custom-select custom-select-sm w-auto" autocomplete="off">
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
                        <div class="form-check form-check-inline ml-2">
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
