<?php

$languages = file_get_contents(get_template_directory_uri() . '/assets/data/languages.json');
$languages = json_decode($languages);

$action = 'new';
if (array_key_exists('edit', $_GET)) {
    $action = 'edit';
}

if (array_key_exists('save_post', $_POST)) {
    $people_mentioned = [];
    $authors = [];
    $recipients = [];
    $langs = '';
    $keywords = '';

    if (array_key_exists('l_author', $_POST)) {
        foreach ($_POST['l_author'] as $author) {
            $authors[] = test_input($author);
        }
    }

    if (array_key_exists('recipient', $_POST)) {
        foreach ($_POST['recipient'] as $recipient) {
            $recipients[] = test_input($recipient);
        }
    }

    if (array_key_exists('people_mentioned', $_POST)) {
        foreach ($_POST['people_mentioned'] as $people) {
            $people_mentioned[] = test_input($people);
        }
    }

    if (array_key_exists('languages', $_POST)) {
        foreach ($_POST['languages'] as $lang) {
            $langs .= test_input($lang) . ';';
        }
    }

    if (array_key_exists('keywords', $_POST)) {
        foreach ($_POST['keywords'] as $kw) {
            $keywords[] = test_input($kw);
        }
    }
    $keywords = array_filter(
        $keywords,
        'get_nonempty_value'
    );

    $data = test_postdata([
        'l_number' => 'l_number',
        'date_year' => 'date_year',
        'date_month' => 'date_month',
        'date_day' => 'date_day',
        'date_marked' => 'date_marked',
        'l_author_marked' => 'l_author_marked',
        'recipient_marked' => 'recipient_marked',
        'recipient_notes' => 'recipient_notes',
        'origin' => 'origin',
        'origin_marked' => 'origin_marked',
        'dest' => 'dest',
        'dest_marked' => 'dest_marked',
        'abstract' => 'abstract',
        'incipit' => 'incipit',
        'explicit' => 'explicit',
        'people_mentioned_notes' => 'people_mentioned_notes',
        'notes_public' => 'notes_public',
        'notes_private' => 'notes_private',
        'rel_rec_name' => 'rel_rec_name',
        'rel_rec_url' => 'rel_rec_url',
        'ms_manifestation' => 'ms_manifestation',
        'repository' => 'repository',
        'name' => 'description',
        'status' => 'status',
    ]);
    $data['date_uncertain'] = get_form_checkbox_val('date_uncertain', $_POST);
    $data['author_uncertain'] = get_form_checkbox_val('author_uncertain', $_POST);
    $data['author_inferred'] = get_form_checkbox_val('author_inferred', $_POST);
    $data['recipient_inferred'] = get_form_checkbox_val('recipient_inferred', $_POST);
    $data['recipient_uncertain'] = get_form_checkbox_val('recipient_uncertain', $_POST);
    $data['origin_inferred'] = get_form_checkbox_val('origin_inferred', $_POST);
    $data['origin_uncertain'] = get_form_checkbox_val('origin_uncertain', $_POST);
    $data['dest_uncertain'] = get_form_checkbox_val('dest_uncertain', $_POST);
    $data['dest_inferred'] = get_form_checkbox_val('dest_inferred', $_POST);
    $data['l_author'] = $authors;
    $data['recipient'] = $recipients;
    $data['languages'] = $langs;
    $data['keywords'] = implode(';', $keywords);
    $data['people_mentioned'] = $people_mentioned;


    $new_pod = '';

    if ($action == 'new') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => 'bl_letter',
            'data' => $data
        ]);
    } elseif ($action == 'edit') {
        $new_pod = pods_api()->save_pod_item([
            'pod' => 'bl_letter',
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
<div id="letter-add-form">

    <div class="alert alert-warning" :class="{ 'd-none' : error == false }">
        Požadovaná položka nebyla nalezena. Pro vytvoření nového dopisu použijte <a href="?">tento odkaz</a>.
    </div>

    <div class="card bg-light" :class="{ 'd-none' : error == true }">
        <div class="card-body">
            <form method="post" id="letter-form" onkeypress="return event.keyCode!=13">
                <fieldset>
                    <div class="form-group required">
                        <label for="number">Letter number</label>
                        <input v-model="l_number" type="text" class="form-control form-control-sm" id="l_number" name="l_number" required>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Dates of letter</legend>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="date_year">Year</label>
                                <input v-model="year" type="number" name="date_year" class="form-control form-control-sm" min="1500" max="2020">
                                <small class="form-text text-muted">
                                    format YYYY, e.g. 1660
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="date_month">Month</label>
                                <input v-model="month" type="number" name="date_month" class="form-control form-control-sm" min="1" max="12">
                                <small class="form-text text-muted">
                                    format MM, e.g. 1
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="date_day">Day</label>
                                <input v-model="day" type="number" name="date_day" class="form-control form-control-sm" min="1" max="31">
                                <small class="form-text text-muted">
                                    format DD, e.g. 8
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date_as_marked">Date as marked on letter</label>
                        <input v-model="date_marked" type="text" name="date_marked" class="form-control form-control-sm">
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="date_uncertain" class="form-check-input" type="checkbox" id="date_uncertain" name="date_uncertain">
                        <label class="form-check-label" for="date_uncertain">
                            Date uncertain
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Author</legend>
                    <div class="form-group">
                        <label for="author">Author <span class="pointer oi oi-reload pl-1" data-source="persons" @click="regenerateSelectData"></span></label>
                        <select multiple class="custom-select custom-select-sm slim-select" name="l_author[]" id="author" v-model="author">
                            <option v-for="person in persons" :value="person.id">
                                {{ person.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="author_as_marked">Author as marked</label>
                        <input v-model="author_as_marked" type="text" name="l_author_marked" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            author's name as written in letter
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="author_inferred" class="form-check-input" type="checkbox" id="author_inferred" name="author_inferred">
                        <label class="form-check-label" for="author_inferred">
                            Author inferred
                        </label>
                        <small class="form-text text-muted">
                            author name not specified but can be deduced from the content of letter or related materials
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="author_uncertain" class="form-check-input" type="checkbox" id="author_uncertain" name="author_uncertain">
                        <label class="form-check-label" for="author_uncertain">
                            Author uncertain
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Recipient</legend>
                    <div class="form-group">
                        <label for="author">Recipient <span class="pointer oi oi-reload pl-1" data-source="persons" @click="regenerateSelectData"></span></label>

                        <select multiple class="custom-select custom-select-sm slim-select" name="recipient[]" id="recipient" v-model="recipient">
                            <option v-for="person in persons" :value="person.id">
                                {{ person.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="recipient_as_marked">Recipient as marked</label>
                        <input v-model="recipient_marked" type="text" name="recipient_marked" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            recipient's name as written in letter
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="recipient_inferred" class="form-check-input" type="checkbox" id="recipient_inferred" name="recipient_inferred">
                        <label class="form-check-label" for="recipient_inferred">
                            Recipient inferred
                        </label>
                        <small class="form-text text-muted">
                            recipient not specified but deduced from content of letter or related materials
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="recipient_uncertain" class="form-check-input" type="checkbox" id="recipient_uncertain" name="recipient_uncertain">
                        <label class="form-check-label" for="recipient_uncertain">
                            Recipient uncertain
                        </label>
                    </div>
                </fieldset>

                <div class="form-group">
                    <label for="recipient_notes">Notes on recipient</label>
                    <textarea v-model="recipient_notes" name="recipient_notes" class="form-control form-control-sm"></textarea>
                </div>

                <fieldset>
                    <legend>Origin</legend>
                    <div class="form-group">
                        <label for="origin">Origin <span class="pointer oi oi-reload pl-1" data-source="places" @click="regenerateSelectData"></span></label>
                        <select v-model="origin" class="custom-select custom-select-sm slim-select" name="origin" id="origin">
                            <option selected value="">---</option>
                            <option v-for="place in places" :value="place.id">
                                {{ place.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="origin_marked">Origin as marked</label>
                        <input v-model="origin_marked" type="text" name="origin_marked" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            origin name as written in letter
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="origin_inferred" class="form-check-input" type="checkbox" id="origin_inferred" name="origin_inferred">
                        <label class="form-check-label" for="origin_inferred">
                            Origin inferred
                        </label>
                        <small class="form-text text-muted">
                            origin not specified but deduced from letter content or related materials
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="origin_uncertain" class="form-check-input" type="checkbox" id="origin_uncertain" name="origin_uncertain">
                        <label class="form-check-label" for="origin_uncertain">
                            Origin uncertain
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Destination</legend>
                    <div class="form-group">
                        <label for="dest">Destination <span class="pointer oi oi-reload pl-1" data-source="places" @click="regenerateSelectData"></span></label>
                        <select v-model="destination" class="custom-select custom-select-sm slim-select" id="dest" name="dest">
                            <option selected value="">---</option>
                            <option v-for="place in places" :value="place.id">
                                {{ place.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dest_marked">Destination as marked</label>
                        <input v-model="dest_marked" type="text" name="dest_marked" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            destination name as written in letter
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="dest_inferred" class="form-check-input" type="checkbox" id="dest_inferred" name="dest_inferred">
                        <label class="form-check-label" for="dest_inferred">
                            Destination inferred
                        </label>
                        <small class="form-text text-muted">
                            destination not specified but deduced from letter content or related materials
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="dest_uncertain" class="form-check-input" type="checkbox" id="dest_uncertain" name="dest_uncertain">
                        <label class="form-check-label" for="dest_uncertain">
                            Destination uncertain
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Content</legend>
                    <div class="form-group">
                        <label for="languages">Languages</label>
                        <select v-model="languages" multiple class="custom-select custom-select-sm slim-select" id="languages" name="languages[]">
                            <?php foreach ($languages as $lang) : ?>
                                <option value="<?= strtolower($lang->name); ?>"><?= strtolower($lang->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group keywords">
                        <label for="keywords">Keywords</label>
                        <div v-for="kw in keywords" class="input-group input-group-sm mb-1">
                            <input v-model="kw.value" type="text" name="keywords[]" @keyup.enter="addNewKeyword" class="form-control form-control-sm">
                            <div class="input-group-append">
                                <button class="btn btn-sm btn-outline-danger btn-remove" type="button" @click="removeKeyword(kw)">
                                    <span class="oi oi-x"></span>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info mt-2" @click="addNewKeyword">
                            <span class="oi oi-plus"></span> Add
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="abstract">Abstract</label>
                        <textarea v-model="abstract" name="abstract" class="form-control form-control-sm"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="incipit">Incipit</label>
                        <textarea v-model="incipit" name="incipit" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            exact words opening body of letter, e.g. opening sentence or first 10 words, but not opening salutation
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="explicit">Explicit</label>
                        <textarea v-model="explicit" name="explicit" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            exact words which close the body of the letter, e.g. closing sentence or closing 10 words, but not closing salutation
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="people_mentioned">People mentioned <span class="pointer oi oi-reload pl-1" data-source="persons" @click="regenerateSelectData"></span></label>
                        <select v-model="mentioned" multiple class="custom-select custom-select-sm slim-select" name="people_mentioned[]" id="people_mentioned">
                            <option v-for="person in persons" :value="person.id">
                                {{ person.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="people_mentioned_notes">Notes on people mentioned</label>
                        <textarea v-model="people_mentioned_notes" name="people_mentioned_notes" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            exact words which close the body of the letter, e.g. closing sentence or closing 10 words, but not closing salutation
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="notes_public">Notes on letter for public display</label>
                        <textarea v-model="notes_public" name="notes_public" class="form-control form-control-sm"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes_private">Editor' notes </label>
                        <textarea v-model="notes_private" name="notes_private" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            internal, hidden editors' notes for EMLO back-end use only. Preface each note with a ‘q’ followed by the initials of the person the note is intended for, e.g. ‘qml’. End each note with your own initials.
                        </small>
                    </div>
                </fieldset>


                <fieldset>
                    <legend>Related resource</legend>
                    <div class="form-group">
                        <label for="rel_rec_name">Related resource name</label>
                        <input v-model="rel_rec_name" type="text" name="rel_rec_name" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            descriptor e.g. 'Printed copy (Tamizey de Larroque) on the Internet Archive'
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="rel_rec_url">Related resource url</label>
                        <input v-model="rel_rec_url" type="url" name="rel_rec_url" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            permanent/short URL to letter-related online resource
                        </small>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Repositories and versions</legend>
                    <div class="form-group">
                        <label for="ms_manifestation">MS manifestation</label>
                        <select v-model="ms_manifestation" class="custom-select custom-select-sm slim-select" name="ms_manifestation" id="ms_manifestation">
                            <option value="ALS">MS Letter</option>
                            <option value="S">MS Copy</option>
                            <option value="D">MS Draft</option>
                            <option value="E">Extract</option>
                            <option value="O">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="repository">Repository</label>
                        <input v-model="repository" type="text" name="repository" class="form-control form-control-sm">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Description</legend>
                    <div class="form-group required">
                        <label for="description">Description
                            <span class="pointer oi oi-transfer pl-1" @click="getTitle"></span>
                        </label>
                        <textarea v-model="title" name="description" class="form-control form-control-sm" required>{{ title }}</textarea>

                    </div>
                </fieldset>

                <fieldset class="form-group">
                    <legend>Status</legend>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input v-model="status" type="radio" class="form-check-input" name="status" value="draft" checked>
                            Private
                        </label>
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input v-model="status" type="radio" class="form-check-input" name="status" value="publish">
                            Public
                        </label>
                    </div>

                </fieldset>

                <?php if ($action == 'new') : ?>
                    <input type="hidden" name="save_post" value="new">
                <?php else : ?>
                    <input type="hidden" name="save_post" value="edit">
                <?php endif; ?>

                <input type="submit" value="Uložit" class="btn btn-primary">
                <a v-if="edit" :href="imgUrl" class="btn btn-secondary" target="_blank">Obrazové přílohy</a>
            </form>
        </div>
    </div>
</div>
<script id="people" type="application/json">
<?php
echo json_encode(
    get_persons_names('bl_person'),
    JSON_UNESCAPED_UNICODE
);
?>
</script>

<script id="places" type="application/json">
<?php
echo json_encode(
    get_places_names('bl_place'),
    JSON_UNESCAPED_UNICODE
);
?>
</script>
