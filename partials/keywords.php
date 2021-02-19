<?php
$pods_types = get_hiko_post_types_by_url();
$keyword_type = $pods_types['keyword'];
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col">
            <div class="mb-3 d-flex justify-content-between">
                <button type="button" class="btn btn-lg btn-primary" onclick="addKeyword('<?= $keyword_type; ?>', 'add', null)">
                    Přidat nové klíčové slovo
                </button>
                <button class="btn btn-outline-primary btn-lg" type="button" id="export-keywords">
                    Exportovat
                </button>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col">
            <div id="datatable-keywords"></div>
        </div>
    </div>
</div>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col">
            <div class="mb-3 d-flex justify-content-between">
                <button type="button" class="btn btn-lg btn-primary" onclick="addCategory('<?= $keyword_type; ?>', 'add', null)">
                    Přidat novou kategorii
                </button>
                <button class="btn btn-outline-primary btn-lg" type="button" id="export-categories">
                    Exportovat
                </button>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col">
            <div id="datatable-categories"></div>
        </div>
    </div>
</div>
