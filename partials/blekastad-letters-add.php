<?php

$languages = file_get_contents(get_template_directory_uri() . '/assets/data/languages.json');
$languages = json_decode($languages);


if (array_key_exists('new_post', $_POST) && $_POST['new_post'] == 1) {
    $people_mentioned = [];
    $authors = [];
    $recipients = [];
    $languages = '';
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
            $languages .= test_input($lang) . ';';
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
    $keywords = implode(';', $keywords);

    $post_data = [
        'l_number' => test_input($_POST['l_number']),
        'date_year' => test_input($_POST['date_year']),
        'date_month' => test_input($_POST['date_month']),
        'date_day' => test_input($_POST['date_day']),
        'date_marked' => test_input($_POST['date_marked']),
        'l_author' => $authors,
        'l_author_marked' => test_input($_POST['l_author_marked']),
        'author_uncertain' => get_form_checkbox_val('author_uncertain', $_POST),
        'author_inferred' => get_form_checkbox_val('author_inferred', $_POST),
        'recipient' => $recipients,
        'recipient_marked' => test_input($_POST['recipient_marked']),
        'recipient_inferred' => get_form_checkbox_val('recipient_inferred', $_POST),
        'recipient_uncertain' => get_form_checkbox_val('recipient_uncertain', $_POST),
        'origin' => test_input($_POST['origin']),
        'origin_marked' => test_input($_POST['origin_marked']),
        'origin_inferred' => get_form_checkbox_val('origin_inferred', $_POST),
        'origin_uncertain' => get_form_checkbox_val('origin_uncertain', $_POST),
        'dest' => test_input($_POST['dest']),
        'dest_marked' => test_input($_POST['dest_marked']),
        'dest_uncertain' => get_form_checkbox_val('dest_uncertain', $_POST),
        'dest_inferred' => get_form_checkbox_val('dest_inferred', $_POST),
        'languages' => $languages,
        'keywords' => $keywords,
        'abstract' => test_input($_POST['abstract']),
        'incipit' => test_input($_POST['incipit']),
        'explicit' => test_input($_POST['explicit']),
        'people_mentioned' => $people_mentioned,
        'people_mentioned_notes' => test_input($_POST['people_mentioned_notes']),
        'notes_public' => test_input($_POST['notes_public']),
        'notes_private' => test_input($_POST['notes_private']),
        'rel_rec_name' => test_input($_POST['rel_rec_name']),
        'rel_rec_url' => test_input($_POST['rel_rec_url']),
        'ms_manifestation' => test_input($_POST['ms_manifestation']),
        'repository' => test_input($_POST['repository']),
        'name' => test_input($_POST['description']),
        'status' => test_input($_POST['status']),
    ];

    $params = [
        'pod' => 'bl_letter',
        'data' => $post_data
    ];

    $new_letter = pods_api()->save_pod_item($params);

    if (is_wp_error($new_letter)) {
        echo alert($result->get_error_message(), 'warning');
    } else {
        echo alert('Uloženo', 'success');
        frontend_refresh();
    }
}

?>

<div class="card bg-light">
    <div class="card-body">
        <form method="post" id="letter-form" onkeypress="return event.keyCode!=13">
            <fieldset>
                <div class="form-group required">
                    <label for="number">Letter number</label>
                    <input type="text" class="form-control form-control-sm" id="l_number" name="l_number" required>
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
                    <input type="text" name="date_marked" class="form-control form-control-sm">
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="date_uncertain" name="date_uncertain">
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
                    <input type="text" name="l_author_marked" class="form-control form-control-sm">
                    <small class="form-text text-muted">
                        author's name as written in letter
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="author_inferred" name="author_inferred">
                    <label class="form-check-label" for="author_inferred">
                        Author inferred
                    </label>
                    <small class="form-text text-muted">
                        author name not specified but can be deduced from the content of letter or related materials
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="author_uncertain" name="author_uncertain">
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
                    <input type="text" name="recipient_marked" class="form-control form-control-sm">
                    <small class="form-text text-muted">
                        recipient's name as written in letter
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="recipient_inferred" name="recipient_inferred">
                    <label class="form-check-label" for="recipient_inferred">
                        Recipient inferred
                    </label>
                    <small class="form-text text-muted">
                        recipient not specified but deduced from content of letter or related materials
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="recipient_uncertain" name="recipient_uncertain">
                    <label class="form-check-label" for="recipient_uncertain">
                        Recipient uncertain
                    </label>
                </div>
            </fieldset>

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
                    <input type="text" name="origin_marked" class="form-control form-control-sm">
                    <small class="form-text text-muted">
                        origin name as written in letter
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="origin_inferred" name="origin_inferred">
                    <label class="form-check-label" for="origin_inferred">
                        Origin inferred
                    </label>
                    <small class="form-text text-muted">
                        origin not specified but deduced from letter content or related materials
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="origin_uncertain" name="origin_uncertain">
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
                    <input type="text" name="dest_marked" class="form-control form-control-sm">
                    <small class="form-text text-muted">
                        destination name as written in letter
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="dest_inferred" name="dest_inferred">
                    <label class="form-check-label" for="dest_inferred">
                        Destination inferred
                    </label>
                    <small class="form-text text-muted">
                        destination not specified but deduced from letter content or related materials
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="dest_uncertain" name="dest_uncertain">
                    <label class="form-check-label" for="dest_uncertain">
                        Destination uncertain
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend>Content</legend>
                <div class="form-group">
                    <label for="languages">Languages</label>
                    <select multiple class="custom-select custom-select-sm slim-select" id="languages" name="languages[]">
                        <?php foreach ($languages as $lang) : ?>
                            <option value="<?= strtolower($lang->name); ?>"><?= strtolower($lang->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group keywords">
                    <label for="keywords">Keywords</label>
                    <div class="input-group input-group-sm mb-1">
                        <input type="text" name="keywords[]" class="form-control form-control-sm" >
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-info" id="add-new-keyword">
                        <span class="oi oi-plus"></span> Add
                    </button>
                </div>

                <div class="form-group">
                    <label for="abstract">Abstract</label>
                    <textarea name="abstract" class="form-control form-control-sm"></textarea>
                </div>
                <div class="form-group">
                    <label for="incipit">Incipit</label>
                    <textarea name="incipit" class="form-control form-control-sm"></textarea>
                    <small class="form-text text-muted">
                        exact words opening body of letter, e.g. opening sentence or first 10 words, but not opening salutation
                    </small>
                </div>

                <div class="form-group">
                    <label for="explicit">Explicit</label>
                    <textarea name="explicit" class="form-control form-control-sm"></textarea>
                    <small class="form-text text-muted">
                        exact words which close the body of the letter, e.g. closing sentence or closing 10 words, but not closing salutation
                    </small>
                </div>

                <div class="form-group">
                    <label for="people_mentioned">People mentioned <span class="pointer oi oi-reload pl-1" data-source="persons" @click="regenerateSelectData"></span></label>
                    <select multiple class="custom-select custom-select-sm slim-select" name="people_mentioned[]" id="people_mentioned">
                        <option v-for="person in persons" :value="person.id">
                            {{ person.name }}
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="people_mentioned_notes">Notes on people mentioned</label>
                    <textarea name="people_mentioned_notes" class="form-control form-control-sm"></textarea>
                    <small class="form-text text-muted">
                        exact words which close the body of the letter, e.g. closing sentence or closing 10 words, but not closing salutation
                    </small>
                </div>

                <div class="form-group">
                    <label for="notes_public">Notes on letter for public display</label>
                    <textarea name="notes_public" class="form-control form-control-sm"></textarea>
                </div>

                <div class="form-group">
                    <label for="notes_private">Editor' notes </label>
                    <textarea name="notes_private" class="form-control form-control-sm"></textarea>
                    <small class="form-text text-muted">
                        internal, hidden editors' notes for EMLO back-end use only. Preface each note with a ‘q’ followed by the initials of the person the note is intended for, e.g. ‘qml’. End each note with your own initials.
                    </small>
                </div>
            </fieldset>


            <fieldset>
                <legend>Related resource</legend>
                <div class="form-group">
                    <label for="rel_rec_name">Related resource name</label>
                    <input type="text" name="rel_rec_name" class="form-control form-control-sm">
                    <small class="form-text text-muted">
                        descriptor e.g. 'Printed copy (Tamizey de Larroque) on the Internet Archive'
                    </small>
                </div>
                <div class="form-group">
                    <label for="rel_rec_url">Related resource url</label>
                    <input type="text" name="rel_rec_url" class="form-control form-control-sm">
                    <small class="form-text text-muted">
                        permanent/short URL to letter-related online resource
                    </small>
                </div>
            </fieldset>

            <fieldset>
                <legend>Repositories and versions</legend>
                <div class="form-group">
                    <label for="ms_manifestation">MS manifestation</label>
                    <select class="custom-select custom-select-sm slim-select" name="ms_manifestation" id="ms_manifestation">
                        <option value="ALS">MS Letter</option>
                        <option value="S">MS Copy</option>
                        <option value="D">MS Draft</option>
                        <option value="E">Extract</option>
                        <option value="O">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="repository">Repository</label>
                    <input type="text" name="repository" class="form-control form-control-sm">
                </div>
            </fieldset>

            <fieldset>
                <legend>Description</legend>
                <div class="form-group required">
                    <label for="description">Description
                        <span class="pointer oi oi-transfer pl-1" @click="getTitle"></span>
                    </label>
                    <textarea name="description" class="form-control form-control-sm" required>{{ title }}</textarea>

                </div>
            </fieldset>

            <fieldset class="form-group">
                <legend>Status</legend>

                <div class="form-check">
                    <label class="form-check-label">
                        <input type="radio" class="form-check-input" name="status" value="draft" checked>
                        Private
                    </label>
                </div>

                <div class="form-check">
                    <label class="form-check-label">
                        <input type="radio" class="form-check-input" name="status" value="publish">
                        Public
                    </label>
                </div>

            </fieldset>

            <input type="hidden" name="new_post" value="1">
            <input type="submit" value="Uložit" class="btn btn-primary">
        </form>
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
