<?php

$action = 'new';

if (array_key_exists('edit', $_GET)) {
    $action = 'edit';
}


if (array_key_exists('save_post', $_POST)) {
    $data = test_postdata([
        'name' => 'fullname',
        'surname' => 'surname',
        'forename' => 'forename',
        'birth_year' => 'birth_year',
        'death_year' => 'death_year',
        'emlo' => 'emlo',
        'note' => 'note'
    ]);

    $new_pod = '';

    if ($action == 'new') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => 'demo_person',
            'data' => $data
        ]);
    } elseif ($action == 'edit') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => 'demo_person',
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
<div id="person-name">

    <div class="alert alert-warning" :class="{ 'd-none' : error == false }">
        Požadovaná položka nebyla nalezena. Pro vytvoření nové osoby použijte <a href="?">tento odkaz</a>.
    </div>

    <div class="card bg-light" :class="{ 'd-none' : error == true }">
        <div class="card-body">
            <form name="persons" method="post" onkeypress="return event.keyCode!=13">
                <fieldset>
                    <div class="form-group required">
                        <label for="last_name">Surname</label>
                        <input v-model="lastName" type="text" class="form-control form-control-sm" name="surname" required>
                    </div>
                    <div class="form-group">
                        <label for="first_name">Forename</label>
                        <input v-model="firstName" type="text" class="form-control form-control-sm" name="forename">
                    </div>
                </fieldset>

                <fieldset>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="date_year">Birth year</label>
                                <input v-model="dob" type="number" name="birth_year" class="form-control form-control-sm" max="2020">
                                <small class="form-text text-muted">
                                    format YYYY, e.g. 1660
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="date_year">Death year</label>
                                <input v-model="dod"  type="number" name="death_year" class="form-control form-control-sm" min="0" max="2030">
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
                        <input v-model="emlo" type="text" class="form-control form-control-sm" name="emlo">
                    </div>
                </fieldset>

                <fieldset>
                    <div class="form-group">
                        <label for="note">Note on person</label>
                        <textarea v-model="note" type="text" class="form-control form-control-sm" name="note"></textarea>
                    </div>
                </fieldset>

                <div class="form-group">
                    <?php if ($action == 'new') : ?>
                        <input type="hidden" name="save_post" value="new">
                    <?php else : ?>
                        <input type="hidden" name="save_post" value="edit">
                    <?php endif; ?>
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
</div>
