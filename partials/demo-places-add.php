<?php

$countries = file_get_contents(get_template_directory_uri() . '/assets/data/countries.json');
$countries = json_decode($countries);

$action = 'new';
if (array_key_exists('edit', $_GET)) {
    $action = 'edit';
}

if (array_key_exists('save_post', $_POST)) {
    $data = test_postdata([
        'name' => 'place',
        'country' => 'country',
        'note' => 'note',
    ]);

    $new_pod = '';

    if ($action == 'new') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => 'demo_place',
            'data' => $data
        ]);
    } elseif ($action == 'edit') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => 'demo_place',
            'data' => $data,
            'id' => $_GET['edit']
        ]);
    }


    if ($new_pod == '') {
        echo alert('Něco se pokazilo', 'warning');
    } elseif (is_wp_error($new_pod)) {
        echo alert($result->get_error_message(), 'warning');
    } else {
        echo alert('Uloženo', 'success');
        frontend_refresh();
    }
}

?>

<div class="card bg-light">
    <div class="card-body">
        <form name="places" id="places-form" method="post" onkeypress="return event.keyCode!=13">
            <fieldset>
                <div class="form-group required">
                    <label for="place">Primary name</label>
                    <input v-model="place" type="text" class="form-control form-control-sm" name="place" required>
                    <small class="form-text text-muted">
                        modern format
                    </small>
                </div>
                <div class="form-group">
                    <label for="country">Country</label>
                    <select v-model="country" class="custom-select custom-select-sm slim-select" id="country" name="country">
                        <option disabled selected value>---</option>
                        <?php foreach ($countries as $country) : ?>
                            <option value="<?= $country->name; ?>">
                                <?= $country->name; ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>
                <div class="form-group">
                    <label for="note">Note on place</label>
                    <textarea v-model="note" class="form-control form-control-sm" id="note" name="note"></textarea>
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
