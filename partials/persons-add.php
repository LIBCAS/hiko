<?php

$pods_types = get_hiko_post_types_by_url();
$person_type = $pods_types['person'];
$action = 'new';
if (array_key_exists('edit', $_GET)) {
    $action = 'edit';
}

if (array_key_exists('save_post', $_POST)) {
    echo save_hiko_person($person_type, $action);
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
                        <label for="type">Type</label>
                        <select v-model="type" class="form-control form-control-sm" name="type" required>
                            <option value="person">Person</option>
                            <option value="institution">Institution</option>
                        </select>
                    </div>
                </fieldset>
                <fieldset v-if="type == 'institution'">
                    <div class="form-group required">
                        <label for="surname">Institution name</label>
                        <input :value="decodeHTML(lastName)" @input="lastName = $event.target.value" type="text" class="form-control form-control-sm" name="surname" required>
                    </div>
                </fieldset>
                <fieldset v-if="type == 'person'">
                    <div class="form-group required">
                        <label for="surname">Surname</label>
                        <input :value="decodeHTML(lastName)" @input="lastName = $event.target.value" type="text" class="form-control form-control-sm" name="surname" required>
                    </div>
                </fieldset>
                <fieldset v-show="type == 'person'">
                    <div class="form-group">
                        <label for="first_name">Forename</label>
                        <input v-model="firstName" type="text" class="form-control form-control-sm" name="forename">
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="date_year">Birth year</label>
                                <input v-model="dob" type="number" name="birth_year" class="form-control form-control-sm" max="<?= date('Y'); ?>">
                                <small class="form-text text-muted">
                                    format YYYY, e.g. 1660
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="date_year">Death year</label>
                                <input v-model="dod" type="number" name="death_year" class="form-control form-control-sm" max="<?= date('Y'); ?>">
                                <small class="form-text text-muted">
                                    format YYYY, e.g. 1660
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="profession_short">Profession (Palladio) <span class="pointer oi oi-reload pl-1" @click="regenerateProfessions($event)"></span></label>
                        <multiselect v-model="professionShort" :options="professionsPalladio" label="label" track-by="value">
                        </multiselect>
                        <input type="hidden" :value="professionShort.value" name="profession_short">
                    </div>
                    <div class="form-group">
                        <label for="profession_detailed">Professions <span class="pointer oi oi-reload pl-1" @click="regenerateProfessions($event)"></span></label>
                        <div v-for="pf, index in professionDetailed" class="border rounded my-2 py-3 px-2 d-flex align-items-start">
                            <multiselect v-model="professionDetailed[index]" :options="professions" label="label" track-by="value"></multiselect>
                            <button @click="removeProfession(index)" type="button" class="close text-danger" aria-label="Remove profession">
                                <span title="Remove profession">&times;</span>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info d-block mt-2 mb-4" @click="addNewprofession">
                            <span class="oi oi-plus"></span> Add
                        </button>
                        <input type="hidden" :value="getObjectValues(professionDetailed).join(';')" name="profession_detailed">
                    </div>
                    <div class="form-group">
                        <label for="profession">Profession (deprecated)</label>
                        <input v-model="profession" type="text" class="form-control form-control-sm" name="profession">
                    </div>
                    <div class="form-group">
                        <label for="nationality">Nationality</label>
                        <input v-model="nationality" type="text" class="form-control form-control-sm" name="nationality">
                    </div>
                    <div class="form-group">
                        <label for="nationality">Gender</label>
                        <input v-model="gender" list="genders" type="text" class="form-control form-control-sm" name="gender">
                        <datalist id="genders">
                            <option>F</option>
                            <option>M</option>
                        </datalist>
                    </div>
                </fieldset>
                <fieldset>
                    <div class="form-group">
                        <label for="emlo">Emlo ID</label>
                        <input v-model="emlo" type="text" class="form-control form-control-sm" name="emlo">
                    </div>
                    <div class="form-group">
                        <label for="note">Note on person / institution</label>
                        <textarea v-model="note" type="text" class="form-control form-control-sm" name="note"></textarea>
                    </div>
                </fieldset>
                <strong v-if="alternativeNames.length">Name as marked</strong>
                <ul class="list-unstyled">
                    <li v-for="(name, index) in alternativeNames" :key="index">
                        <span v-html="name"></span>
                    </li>
                </ul>
                <div class="form-group">
                    <input type="hidden" :value="type">
                    <?php if ($action == 'new') : ?>
                        <input type="hidden" name="save_post" value="new">
                    <?php else : ?>
                        <input type="hidden" name="save_post" value="edit">
                    <?php endif; ?>
                    <div class="input-group mb-3">
                        <input :value="decodeHTML(fullName)" name="fullname" type="text" class="form-control form-control-sm not-allowed" readonly>
                        <div class="input-group-append">
                            <input class="btn btn-primary btn-sm" type="submit" value="Uložit" :disabled="personsFormValidated === false">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
