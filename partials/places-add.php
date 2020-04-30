<?php

$pods_types = get_hiko_post_types_by_url();
$place_type = $pods_types['place'];
$action = 'new';
if (array_key_exists('edit', $_GET)) {
    $action = 'edit';
}

if (array_key_exists('save_post', $_POST)) {
    save_hiko_place($place_type, $action);
}

?>

<div class="card bg-light">
    <div class="card-body" id="places-form">
        <form name="places" method="post" onkeypress="return event.keyCode!=13">
            <fieldset>
                <div class="form-group required">
                    <label for="place">Primary name</label>
                    <input :value="decodeHTML(place)" @input="place = $event.target.value" type="text" class="form-control form-control-sm" name="place" required>
                    <small class="form-text text-muted">
                        modern format
                    </small>
                </div>
                <div class="form-group requred">
                    <label for="country">Country</label>
                    <multiselect v-model="country" :options="countries" label="label" track-by="value"></multiselect>
                    <input type="hidden" name="country" :value="country.value">
                </div>
                <div class="form-group">
                    <label for="note">Note on place</label>
                    <textarea :value="decodeHTML(note)" @input="note = $event.target.value" class="form-control form-control-sm" id="note" name="note"></textarea>
                </div>
            </fieldset>
            <fieldset>
                <legend>Coordinates <span class="oi oi-map ml-1 pointer" @click="getCoord" id="coordinates" title="Vyhledat souřadnice"></span></legend>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input v-model="lat" type="text" name="latitude" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input v-model="long" name="longitude" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </fieldset>
            <div class="form-group">
                <?php if ($action == 'new') : ?>
                    <input type="hidden" name="save_post" value="new">
                <?php else : ?>
                    <input type="hidden" name="save_post" value="edit">
                <?php endif; ?>
                <input class="btn btn-primary" type="submit" value="Uložit">
            </div>
        </form>
    </div>
</div>

<?= get_json_countries(); ?>
