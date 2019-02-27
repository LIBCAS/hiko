<?php
$pods_types = get_hiko_post_types_by_url();
$letter_type = $pods_types['letter'];
$person_type = $pods_types['person'];
$place_type = $pods_types['place'];
$path = $pods_types['path'];
$languages = get_languages();

$action = 'new';
if (array_key_exists('edit', $_GET)) {
    $action = 'edit';
}

if (array_key_exists('save_post', $_POST)) {
    echo save_hiko_letter($letter_type, $action, $path);
}

?>
<div id="letter-add-form">

    <div v-if="loading" class="progress">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 65%">
        </div>
    </div>

    <div class="alert alert-warning d-none" :class="{ 'd-block' : error != false }">
        Požadovaná položka nebyla nalezena. Pro vytvoření nového dopisu použijte <a href="?">tento odkaz</a>.
    </div>

    <div class="card bg-light d-none" :class="{ 'd-block' : formVisible }">
        <div class="card-body">
            <form method="post" id="letter-form" onkeypress="return event.keyCode!=13">
                <fieldset>
                    <legend>Dates of letter</legend>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="date_year">Year</label>
                                <input v-model="letter.date_year" type="number" name="date_year" class="form-control form-control-sm" min="0" max="2020">
                                <small class="form-text text-muted">
                                    format YYYY, e.g. 1660
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="date_month">Month</label>
                                <input v-model="letter.date_month" type="number" name="date_month" class="form-control form-control-sm" min="0" max="12">
                                <small class="form-text text-muted">
                                    format MM, e.g. 1
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="date_day">Day</label>
                                <input v-model="letter.date_day" type="number" name="date_day" class="form-control form-control-sm" min="0" max="31">
                                <small class="form-text text-muted">
                                    format DD, e.g. 8
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date_as_marked">Date as marked on letter</label>
                        <input v-model="letter.date_marked" type="text" name="date_marked" class="form-control form-control-sm">
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.date_uncertain" class="form-check-input" type="checkbox" id="date_uncertain" name="date_uncertain">
                        <label class="form-check-label" for="date_uncertain">
                            Date uncertain
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.date_approximate" class="form-check-input" type="checkbox" id="date_approximate" name="date_approximate">
                        <label class="form-check-label" for="date_approximate">
                            Date approximate
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.date_is_range" class="form-check-input" type="checkbox" id="date_is_range" name="date_is_range">
                        <label class="form-check-label" for="date_is_range">
                            Date is range
                        </label>
                    </div>

                    <div class="row" v-show="letter.date_is_range">
                        <div class="col">
                            <div class="form-group">
                                <label for="range_year">Year 2</label>
                                <input v-model="letter.range_year" type="number" name="range_year" class="form-control form-control-sm" min="0" max="2020">
                                <small class="form-text text-muted">
                                    2nd date, if range
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="range_month">Month 2</label>
                                <input v-model="letter.range_month" type="number" name="range_month" class="form-control form-control-sm" min="0" max="12">
                                <small class="form-text text-muted">
                                    2nd date, if range
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="range_day">Day 2</label>
                                <input v-model="letter.range_day" type="number" name="range_day" class="form-control form-control-sm" min="0" max="31">
                                <small class="form-text text-muted">
                                    2nd date, if range
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="date_note">Notes on date</label>
                        <textarea v-model="letter.date_note" name="date_note" class="form-control form-control-sm"></textarea>
                    </div>

                </fieldset>

                <fieldset>
                    <legend>Author</legend>
                    <div class="form-group">
                        <label for="author">Author <span class="pointer oi oi-reload pl-1" @click="regenerateSelectData('persons', $event)"></span></label>
                        <select multiple v-model="letter.author" class="custom-select custom-select-sm slim-select" name="l_author[]" id="author">
                            <option v-for="person in persons" :value="person.id">
                                {{ person.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="author_as_marked">Author as marked</label>
                        <input v-model="letter.author_as_marked" type="text" name="l_author_marked" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            author's name as written in letter
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.author_inferred" class="form-check-input" type="checkbox" id="author_inferred" name="author_inferred">
                        <label class="form-check-label" for="author_inferred">
                            Author inferred
                        </label>
                        <small class="form-text text-muted">
                            author name not specified but can be deduced from the content of letter or related materials
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.author_uncertain" class="form-check-input" type="checkbox" id="author_uncertain" name="author_uncertain">
                        <label class="form-check-label" for="author_uncertain">
                            Author uncertain
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="author_note">Notes on author</label>
                        <textarea v-model="letter.author_note" name="author_note" class="form-control form-control-sm"></textarea>
                    </div>

                </fieldset>

                <fieldset>
                    <legend>Recipient</legend>
                    <div class="form-group">
                        <label for="author">Recipient <span class="pointer oi oi-reload pl-1" @click="regenerateSelectData('persons', $event)"></span></label>

                        <select v-model="letter.recipient" multiple class="custom-select custom-select-sm slim-select" name="recipient[]" id="recipient">
                            <option v-for="person in persons" :value="person.id">
                                {{ person.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="recipient_as_marked">Recipient as marked</label>
                        <input v-model="letter.recipient_marked" type="text" name="recipient_marked" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            recipient's name as written in letter
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.recipient_inferred" class="form-check-input" type="checkbox" id="recipient_inferred" name="recipient_inferred">
                        <label class="form-check-label" for="recipient_inferred">
                            Recipient inferred
                        </label>
                        <small class="form-text text-muted">
                            recipient not specified but deduced from content of letter or related materials
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.recipient_uncertain" class="form-check-input" type="checkbox" id="recipient_uncertain" name="recipient_uncertain">
                        <label class="form-check-label" for="recipient_uncertain">
                            Recipient uncertain
                        </label>
                    </div>
                </fieldset>

                <div class="form-group">
                    <label for="recipient_notes">Notes on recipient</label>
                    <textarea v-model="letter.recipient_notes" name="recipient_notes" class="form-control form-control-sm"></textarea>
                </div>

                <fieldset>
                    <legend>Origin</legend>
                    <div class="form-group">
                        <label for="origin">Origin <span class="pointer oi oi-reload pl-1" @click="regenerateSelectData('places', $event)"></span></label>
                        <select multiple v-model="letter.origin" class="custom-select custom-select-sm slim-select" name="origin[]" id="origin">
                            <option v-for="place in places" :value="place.id">
                                {{ place.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="origin_marked">Origin as marked</label>
                        <input v-model="letter.origin_marked" type="text" name="origin_marked" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            origin name as written in letter
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.origin_inferred" class="form-check-input" type="checkbox" id="origin_inferred" name="origin_inferred">
                        <label class="form-check-label" for="origin_inferred">
                            Origin inferred
                        </label>
                        <small class="form-text text-muted">
                            origin not specified but deduced from letter content or related materials
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.origin_uncertain" class="form-check-input" type="checkbox" id="origin_uncertain" name="origin_uncertain">
                        <label class="form-check-label" for="origin_uncertain">
                            Origin uncertain
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="origin_note">Notes on origin</label>
                        <textarea v-model="letter.origin_note" name="origin_note" class="form-control form-control-sm"></textarea>
                    </div>

                </fieldset>

                <fieldset>
                    <legend>Destination</legend>
                    <div class="form-group">
                        <label for="dest">Destination <span class="pointer oi oi-reload pl-1" @click="regenerateSelectData('places', $event)"></span></label>
                        <select multiple v-model="letter.destination" class="custom-select custom-select-sm slim-select" id="dest" name="dest[]">
                            <option v-for="place in places" :value="place.id">
                                {{ place.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dest_marked">Destination as marked</label>
                        <input v-model="letter.dest_marked" type="text" name="dest_marked" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            destination name as written in letter
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.dest_inferred" class="form-check-input" type="checkbox" id="dest_inferred" name="dest_inferred">
                        <label class="form-check-label" for="dest_inferred">
                            Destination inferred
                        </label>
                        <small class="form-text text-muted">
                            destination not specified but deduced from letter content or related materials
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input v-model="letter.dest_uncertain" class="form-check-input" type="checkbox" id="dest_uncertain" name="dest_uncertain">
                        <label class="form-check-label" for="dest_uncertain">
                            Destination uncertain
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="dest_note">Notes on destination</label>
                        <textarea v-model="letter.dest_note" name="dest_note" class="form-control form-control-sm"></textarea>
                    </div>

                </fieldset>

                <fieldset>
                    <legend>Content</legend>
                    <div class="form-group">
                        <label for="languages">Languages</label>
                        <select v-model="letter.languages" multiple class="custom-select custom-select-sm slim-select" id="languages" name="languages[]">
                            <?php foreach ($languages as $lang) : ?>
                                <option value="<?= strtolower($lang->name); ?>"><?= strtolower($lang->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group keywords">
                        <label for="keywords">Keywords</label>
                        <div v-for="kw in letter.keywords" class="input-group input-group-sm mb-1">
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
                        <textarea v-model="letter.abstract" name="abstract" class="form-control form-control-sm"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="incipit">Incipit</label>
                        <textarea v-model="letter.incipit" name="incipit" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            exact words opening body of letter, e.g. opening sentence or first 10 words, but not opening salutation
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="explicit">Explicit</label>
                        <textarea v-model="letter.explicit" name="explicit" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            exact words which close the body of the letter, e.g. closing sentence or closing 10 words, but not closing salutation
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="people_mentioned">People mentioned <span class="pointer oi oi-reload pl-1" @click="regenerateSelectData('persons', $event)"></span></label>
                        <select v-model="letter.mentioned" multiple class="custom-select custom-select-sm slim-select" name="people_mentioned[]" id="people_mentioned">
                            <option v-for="person in persons" :value="person.id">
                                {{ person.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="people_mentioned_notes">Notes on people mentioned</label>
                        <textarea v-model="letter.people_mentioned_notes" name="people_mentioned_notes" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            exact words which close the body of the letter, e.g. closing sentence or closing 10 words, but not closing salutation
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="notes_public">Notes on letter for public display</label>
                        <textarea v-model="letter.notes_public" name="notes_public" class="form-control form-control-sm"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes_private">Editor' notes </label>
                        <textarea v-model="letter.notes_private" name="notes_private" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            internal, hidden editors' notes for EMLO back-end use only. Preface each note with a ‘q’ followed by the initials of the person the note is intended for, e.g. ‘qml’. End each note with your own initials.
                        </small>
                    </div>
                </fieldset>


                <fieldset>
                    <legend>Related resource</legend>
                    <div class="form-group">
                        <label for="rel_rec_name">Related resource name</label>
                        <input v-model="letter.rel_rec_name" type="text" name="rel_rec_name" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            descriptor e.g. 'Printed copy (Tamizey de Larroque) on the Internet Archive'
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="rel_rec_url">Related resource url</label>
                        <input v-model="letter.rel_rec_url" type="url" name="rel_rec_url" class="form-control form-control-sm">
                        <small class="form-text text-muted">
                            permanent/short URL to letter-related online resource
                        </small>
                    </div>
                </fieldset>

                <fieldset>
                    <div class="form-group">
                        <label for="number">Letter number</label>
                        <input v-model="letter.l_number" type="text" class="form-control form-control-sm" id="l_number" name="l_number">
                    </div>
                    <legend>Repositories and versions</legend>
                    <div class="form-group">
                        <label for="ms_manifestation">MS manifestation</label>
                        <select v-model="letter.ms_manifestation" class="custom-select custom-select-sm slim-select" name="ms_manifestation" id="ms_manifestation">
                            <option value="">---</option>
                            <option value="ALS">MS Letter</option>
                            <option value="S">MS Copy</option>
                            <option value="D">MS Draft</option>
                            <option value="E">Extract</option>
                            <option value="O">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="repository">Repository <span class="pointer oi oi-reload pl-1" @click="regenerateSelectData('locations', $event)"></span></label>
                        <input v-model="letter.repository" list="repositories" type="text" name="repository" class="form-control form-control-sm">
                        <datalist id="repositories">
                            <option v-for="rep in repositories"> {{ rep.name }} </option>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="archive">Archive</label>
                        <input v-model="letter.archive" list="archives" type="text" name="archive" class="form-control form-control-sm">
                        <datalist id="archives">
                            <option v-for="a in archives"> {{ a.name }} </option>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="collection">Collection</label>
                        <input v-model="letter.collection" list="collections" type="text" name="collection" class="form-control form-control-sm">
                        <datalist id="collections">
                            <option v-for="c in collections"> {{ c.name }} </option>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="signature">Signature</label>
                        <input v-model="letter.signature" type="text" name="signature" class="form-control form-control-sm">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Description</legend>
                    <div class="form-group required">
                        <label for="description">Description
                            <span class="pointer oi oi-transfer pl-1" @click="getTitle"></span>
                        </label>
                        <textarea v-model="title" name="description" class="form-control form-control-sm" required>{{ title }}</textarea>
                        {{ letter.title }}
                    </div>
                </fieldset>

                <fieldset class="form-group">
                    <legend>Status</legend>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input v-model="letter.status" type="radio" class="form-check-input" name="status" value="draft" required checked>
                            Private
                        </label>
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input v-model="letter.status" type="radio" class="form-check-input" name="status" value="publish">
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
                <a v-if="edit" :href="previewUrl" class="btn btn-secondary" target="_blank">Náhled</a>
            </form>
        </div>
    </div>
</div>

<?= display_persons_and_places($person_type, $place_type); ?>
