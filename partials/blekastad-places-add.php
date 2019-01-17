<?php

$countries = file_get_contents(get_template_directory_uri() . '/assets/data/countries.json');
$countries = json_decode($countries);


if (array_key_exists('new_post', $_POST) && $_POST['new_post'] == 1) {
    $params = [
        'pod' => 'bl_place',
        'data' => [
            'name' => test_input($_POST['place']),
            'country' => test_input($_POST['country']),
        ]
    ];

    $new_place = pods_api()->save_pod_item($params);

    if (is_wp_error($new_place)) {
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
                    <input type="text" class="form-control form-control-sm" name="place" required>
                    <small class="form-text text-muted">
                        modern format
                    </small>
                </div>
                <div class="form-group">
                    <label for="country">Country</label>
                    <select class="custom-select custom-select-sm slim-select" id="country" name="country">
                        <option disabled selected value>---</option>
                        <?php foreach ($countries as $country) : ?>
                            <option value="<?= $country->name; ?>">
                                <?= $country->name; ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>
            </fieldset>
            <div class="form-group">
                <input type="hidden" name="new_post" value="1">
                <input class="btn btn-primary" type="submit" value="Uložit">
            </div>
        </form>
    </div>
</div>
