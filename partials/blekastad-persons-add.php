<?php

if (array_key_exists('new_post', $_POST) && $_POST['new_post'] == 1) {
    $params = [
        'pod' => 'bl_person',
        'data' => [
            'name' => test_input($_POST['fullname']),
            'surname' => test_input($_POST['surname']),
            'forename' => test_input($_POST['forename']),
            'birth_year' => test_input($_POST['birth_year']),
            'death_year' => test_input($_POST['death_year']),
            'emlo' => test_input($_POST['emlo']),
        ]
    ];

    $new_person = pods_api()->save_pod_item($params);

    if (is_wp_error($new_person)) {
        echo alert($result->get_error_message(), 'warning');
    } else {
        echo alert('Uloženo', 'success');
        frontend_refresh();
    }
}

?>

<div class="card bg-light">
    <div class="card-body">
        <form name="persons" method="post" id="person-name" onkeypress="return event.keyCode!=13">
            <fieldset>
                <div class="form-group required">
                    <label for="last_name">Surname</label>
                    <input v-model="lastName" type="text" class="form-control form-control-sm" name="surname" required>
                </div>
                <div class="form-group required">
                    <label for="first_name">Forename</label>
                    <input v-model="firstName" type="text" class="form-control form-control-sm" name="forename" required>
                </div>
            </fieldset>

            <fieldset>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="date_year">Birth year</label>
                            <input type="number" name="birth_year" class="form-control form-control-sm" max="2020">
                            <small class="form-text text-muted">
                                format YYYY, e.g. 1660
                            </small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="date_year">Death year</label>
                            <input type="number" name="death_year" class="form-control form-control-sm" min="0" max="2030">
                            <small class="form-text text-muted">
                                format YYYY, e.g. 1660
                            </small>
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <div class="form-group">
                    <label for="emlo">Emlo ID</label>
                    <input type="text" class="form-control form-control-sm" name="emlo">
                </div>
            </fieldset>

            <div class="form-group">
                <input type="hidden" name="new_post" value="1">
                <div class="input-group mb-3">
                    <input v-model="fullName" name="fullname" type="text" class="form-control form-control-sm not-allowed" readonly>
                    <div class="input-group-append">
                        <input class="btn btn-primary btn-sm" type="submit" value="Uložit" :disabled="personsFormValidated === false">
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
