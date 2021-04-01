<?php
$place_type = get_hiko_post_types_by_url()['place'];
$action = array_key_exists('edit', $_GET) ? 'edit' : 'new';
$place = isset($_GET['edit']) ? get_place($place_type, (int) $_GET['edit']) : [];

if (array_key_exists('save_post', $_POST)) {
    save_place($place_type, $action);
}

show_alerts(); ?>

<?php if (isset($_GET['edit']) && empty($place['name'])) : ?>
    <div class="alert alert-warning">
        Požadovaná položka nebyla nalezena. Pro vytvoření nového místa použijte <a href="?">tento odkaz</a>.
    </div>
<?php else : ?>
    <script id="place-data" type="application/json">
        <?= json_encode($place, JSON_UNESCAPED_UNICODE) ?>
    </script>
    <div class="card bg-light" x-data="placeForm()" x-init="fetch()" x-cloak>
        <div class="card-body">
            <form id="places-form" method="post" x-on:keydown.enter.prevent x-on:submit="handleSubmit(event)" autocomplete="off">
                <fieldset>
                    <div class="form-group required">
                        <label for="place">Primary name</label>
                        <input x-model="name" type="text" class="form-control form-control-sm" name="place" required>
                        <small class="form-text text-muted">
                            modern format
                        </small>
                    </div>
                    <div class="form-group required">
                        <label for="country">Country</label>
                        <select x-model="country" id="country" name="country" required>
                            <?php foreach (get_countries() as $country) : ?>
                                <option value="<?= $country['name'] ?>" <?= $country['name'] === $place['country'] ? 'selected' : ''?>>
                                    <?= $country['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="note">Note on place</label>
                        <textarea x-html="note" class="form-control form-control-sm" id="note" name="note"></textarea>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Coordinates <span class="ml-1 oi oi-map pointer" @click="getCoord" id="coordinates" title="Vyhledat souřadnice"></span></legend>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="latitude">Latitude</label>
                                <input x-model="latitude" type="text" name="latitude" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="longitude">Longitude</label>
                                <input x-model="longitude" name="longitude" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </fieldset>
                <template x-if="errors.length > 0">
                    <ul class="alert alert-danger">
                        <template x-for="error, index in errors" :key="index">
                            <li x-text="error"></li>
                        </template>
                    </ul>
                </template>
                <ul>
                </ul>
                <div class="form-group">
                    <input type="hidden" name="save_post" value="<?= $action ?>">
                    <input class="btn btn-primary" type="submit" value="Uložit">
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
