<?php

$pods_types = get_hiko_post_types_by_url();
$letter_type = $pods_types['letter'];
$person_type = $pods_types['person'];
$place_type = $pods_types['place'];
$path = $pods_types['path'];

$action = 'new';
if (array_key_exists('edit', $_GET)) {
    $action = 'edit';
}

if (array_key_exists('save_post', $_POST)) {
    echo save_hiko_letter($letter_type, $action, $path);
}

?>

<div class="list-group list-group-sm mw-200 sticky-content">
    <a class="list-group-item list-group-item-action" href="#a-dates">Dates</a>
    <a class="list-group-item list-group-item-action" href="#a-author">Author</a>
    <a class="list-group-item list-group-item-action" href="#a-recipient">Recipient</a>
    <a class="list-group-item list-group-item-action" href="#a-origin">Origin</a>
    <a class="list-group-item list-group-item-action" href="#a-destination">Destination</a>
    <a class="list-group-item list-group-item-action" href="#a-content">Content</a>
    <a class="list-group-item list-group-item-action" href="#a-related-resource">Related resource</a>
    <a class="list-group-item list-group-item-action" href="#a-manifestations">Manifestations</a>
    <a class="list-group-item list-group-item-action" href="#a-repositories-and-versions">Repositories and versions</a>
    <a class="list-group-item list-group-item-action" href="#a-description">Description</a>
    <a class="list-group-item list-group-item-action" href="#a-status">Status</a>
</div>


<div id="letter-add-form" v-cloak>

    <div v-if="loading" class="progress">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 65%">
        </div>
    </div>

    <div class="alert alert-warning d-none" :class="{ 'd-block' : error != false }">
        Požadovaná položka nebyla nalezena. Pro vytvoření nového dopisu použijte <a href="?">tento odkaz</a>.
    </div>

    <div class="card bg-light d-none" :class="{ 'd-block' : formVisible }">
        <div class="card-body">
            <form @submit="validateForm" method="post" id="letter-form" onkeypress="return event.keyCode!=13">
                <fieldset id="a-dates">
                    <legend>Dates of letter</legend>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="date_year">Year</label>
                                <input v-validate="'between:0,2020'" data-vv-name="'Year'" v-model="letter.date_year" type="number" name="date_year" class="form-control form-control-sm" min="0" max="2020">
                                <small class="form-text text-muted">
                                    format YYYY, e.g. 1660
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="date_month">Month</label>
                                <input v-validate="'between:1,12'" data-vv-name="'Month'" v-model="letter.date_month" type="number" name="date_month" class="form-control form-control-sm" min="0" max="12">
                                <small class="form-text text-muted">
                                    format MM, e.g. 1
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="date_day">Day</label>
                                <input v-validate="'between:1,31'" data-vv-name="'Day'" v-model="letter.date_day" type="number" name="date_day" class="form-control form-control-sm" min="0" max="31">
                                <small class="form-text text-muted">
                                    format DD, e.g. 8
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date_as_marked">Date as marked on letter</label>
                        <input :value="decodeHTML(letter.date_marked)" @input="letter.date_marked = $event.target.value" type="text" name="date_marked" class="form-control form-control-sm">
                    </div>

                    <div class="mb-3 form-check">
                        <input v-model="letter.date_uncertain" class="form-check-input" type="checkbox" id="date_uncertain" name="date_uncertain">
                        <label class="form-check-label" for="date_uncertain">
                            Date uncertain
                        </label>
                    </div>

                    <div class="mb-3 form-check">
                        <input v-model="letter.date_approximate" class="form-check-input" type="checkbox" id="date_approximate" name="date_approximate">
                        <label class="form-check-label" for="date_approximate">
                            Date approximate
                        </label>
                    </div>

                    <div class="mb-3 form-check">
                        <input v-model="letter.date_inferred" class="form-check-input" type="checkbox" id="date_inferred" name="date_inferred">
                        <label class="form-check-label" for="date_inferred">
                            Date inferred
                        </label>
                    </div>

                    <div class="mb-3 form-check">
                        <input v-model="letter.date_is_range" class="form-check-input" type="checkbox" id="date_is_range" name="date_is_range">
                        <label class="form-check-label" for="date_is_range">
                            Date is range
                        </label>
                    </div>

                    <div class="row" v-show="letter.date_is_range">
                        <div class="col">
                            <div class="form-group">
                                <label for="range_year">Year 2</label>
                                <input v-validate="'between:0,2020'" data-vv-name="'Year 2'" v-model="letter.range_year" type="number" name="range_year" class="form-control form-control-sm" min="0" max="2020">
                                <small class="form-text text-muted">
                                    2nd date, if range
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="range_month">Month 2</label>
                                <input v-validate="'between:1,12'" data-vv-name="'Month 2'" v-model="letter.range_month" type="number" name="range_month" class="form-control form-control-sm" min="0" max="12">
                                <small class="form-text text-muted">
                                    2nd date, if range
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="range_day">Day 2</label>
                                <input v-validate="'between:1,31'" data-vv-name="'Day 2'" v-model="letter.range_day" type="number" name="range_day" class="form-control form-control-sm" min="0" max="31">
                                <small class="form-text text-muted">
                                    2nd date, if range
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="date_note">Notes on date</label>
                        <textarea :value="decodeHTML(letter.date_note)" @input="letter.date_note = $event.target.value" name="date_note" class="form-control form-control-sm"></textarea>
                    </div>

                </fieldset>

                <fieldset id="a-author">
                    <legend>Author</legend>
                    <div v-for="(a, index) in letter.author" :data-key="a.id != '' ? 'a-' + a.id.value : a.key" :key="a.id != '' ? 'a-' + a.id.value : a.key" class="px-2 py-3 my-2 border rounded">
                        <button @click="removeObjectMeta(index, 'author')" type="button" class="close text-danger" aria-label="Remove author">
                            <span title="Remove author">&times;</span>
                        </button>
                        <div class="form-group required">
                            <label :for="'author-' + index">Author</label>
                            <span class="pl-1 pointer oi oi-reload" @click="regenerateSelectData('persons', $event)" title="Update persons"></span>
                            <multiselect v-model="a.id" :options="personsData" options-limit="5000" label="label" track-by="value" :required="true">
                            </multiselect>
                            <input type="hidden" :value="a.id.value" v-validate="'required'" data-vv-name="'Author'" name="l_author[]">
                        </div>
                        <div class="form-group">
                            <label for="marked">Author as marked</label>
                            <input data-vv-name="'Author as marked'" :value="decodeHTML(a.marked)" @input="a.marked = $event.target.value" type="text" class="form-control form-control-sm">
                            <small class="form-text text-muted">
                                author's name as written in letter
                            </small>
                        </div>
                    </div>

                    <button type="button" @click="addPersonMeta('author')" class="my-2 btn btn-sm btn-outline-info">
                        <span class="oi oi-plus"></span>
                        Add author
                    </button>

                    <div class="mb-3 form-check">
                        <input v-model="letter.author_inferred" class="form-check-input" type="checkbox" id="author_inferred" name="author_inferred">
                        <label class="form-check-label" for="author_inferred">
                            Author inferred
                        </label>
                        <small class="form-text text-muted">
                            author name not specified but can be deduced from the content of letter or related materials
                        </small>
                    </div>

                    <div class="mb-3 form-check">
                        <input v-model="letter.author_uncertain" class="form-check-input" type="checkbox" id="author_uncertain" name="author_uncertain">
                        <label class="form-check-label" for="author_uncertain">
                            Author uncertain
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="author_note">Notes on author</label>
                        <textarea :value="decodeHTML(letter.author_note)" @input="letter.author_note = $event.target.value" name="author_note" class="form-control form-control-sm"></textarea>
                    </div>
                </fieldset>

                <fieldset id="a-recipient">
                    <legend>Recipient</legend>
                    <div v-for="(r, index) in letter.recipient" :data-key="r.id != '' ? 'r-' + r.id.value : r.key" :key="r.id != '' ? 'r-' + r.id.value : r.key" class="px-2 py-3 my-2 border rounded">
                        <button @click="removeObjectMeta(index, 'recipient')" type="button" class="close text-danger" aria-label="Remove author">
                            <span title="Remove recipient">&times;</span>
                        </button>
                        <div class="form-group required">
                            <label :for="'recipient-' + index">Recipient</label>
                            <span class="pl-1 pointer oi oi-reload" @click="regenerateSelectData('persons', $event)" title="Update persons"></span>
                            <multiselect v-model="r.id" :options="personsData" options-limit="5000" label="label" track-by="value" :required="true">
                            </multiselect>
                            <input v-validate="'required'" data-vv-name="'Recipient'" type="hidden" :value="r.id.value" name="recipient[]">
                        </div>

                        <div class="form-group">
                            <label for="recipient_as_marked">Recipient as marked</label>
                            <input data-vv-name="'Recipient as marked'" :value="decodeHTML(r.marked)" @input="r.marked = $event.target.value" type="text" class="form-control form-control-sm">
                            <small class="form-text text-muted">
                                recipient's name as written in letter
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="salutation">Salutation</label>
                            <input :value="decodeHTML(r.salutation)" @input="r.salutation = $event.target.value" type="text" class="form-control form-control-sm">
                        </div>
                    </div>
                    <button type="button" @click="addPersonMeta('recipient')" class="my-2 btn btn-sm btn-outline-info">
                        <span class="oi oi-plus"></span>
                        Add recipient
                    </button>

                    <div class="mb-3 form-check">
                        <input v-model="letter.recipient_inferred" class="form-check-input" type="checkbox" id="recipient_inferred" name="recipient_inferred">
                        <label class="form-check-label" for="recipient_inferred">
                            Recipient inferred
                        </label>
                        <small class="form-text text-muted">
                            recipient not specified but deduced from content of letter or related materials
                        </small>
                    </div>

                    <div class="mb-3 form-check">
                        <input v-model="letter.recipient_uncertain" class="form-check-input" type="checkbox" id="recipient_uncertain" name="recipient_uncertain">
                        <label class="form-check-label" for="recipient_uncertain">
                            Recipient uncertain
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="recipient_notes">Notes on recipient</label>
                        <textarea :value="decodeHTML(letter.recipient_notes)" @input="letter.recipient_notes = $event.target.value" name="recipient_notes" class="form-control form-control-sm"></textarea>
                    </div>
                </fieldset>

                <fieldset id="a-origin">
                    <legend>Origin</legend>

                    <div v-for="(o, index) in letter.origin" :data-key="o.id != '' ? 'o-' + o.id.value : o.key" :key="o.id != '' ? 'o-' + o.id.value : o.key" class="px-2 py-3 my-2 border rounded">
                        <button @click="removeObjectMeta(index, 'origin')" type="button" class="close text-danger" aria-label="Remove origin">
                            <span title="Remove origin">&times;</span>
                        </button>
                        <div class="form-group required">
                            <label :for="'origin-' + index">Origin</label>
                            <span class="pl-1 pointer oi oi-reload" @click="regenerateSelectData('places', $event)" title="Update places"></span>
                            <multiselect v-model="o.id" :options="placesData" options-limit="5000" label="label" track-by="value" :required="true">
                            </multiselect>
                            <input v-validate="'required'" data-vv-name="'Origin'" type="hidden" :value="o.id.value" name="origin[]">
                        </div>
                        <div class="form-group">
                            <label for="marked">Origin as marked</label>
                            <input data-vv-name="'Origin as marked'" :value="decodeHTML(o.marked)" @input="o.marked = $event.target.value" type="text" class="form-control form-control-sm">
                            <small class="form-text text-muted">
                                origin name as written in letter
                            </small>
                        </div>
                    </div>

                    <button type="button" @click="addPlaceMeta('origin')" class="my-2 btn btn-sm btn-outline-info">
                        <span class="oi oi-plus"></span>
                        Add origin
                    </button>

                    <div class="mb-3 form-check">
                        <input v-model="letter.origin_inferred" class="form-check-input" type="checkbox" id="origin_inferred" name="origin_inferred">
                        <label class="form-check-label" for="origin_inferred">
                            Origin inferred
                        </label>
                        <small class="form-text text-muted">
                            origin not specified but deduced from letter content or related materials
                        </small>
                    </div>

                    <div class="mb-3 form-check">
                        <input v-model="letter.origin_uncertain" class="form-check-input" type="checkbox" id="origin_uncertain" name="origin_uncertain">
                        <label class="form-check-label" for="origin_uncertain">
                            Origin uncertain
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="origin_note">Notes on origin</label>
                        <textarea :value="decodeHTML(letter.origin_note)" @input="letter.origin_note = $event.target.value" name="origin_note" class="form-control form-control-sm"></textarea>
                    </div>

                </fieldset>

                <fieldset id="a-destination">
                    <legend>Destination</legend>

                    <div v-for="(d, index) in letter.destination" :data-key="d.id != '' ? 'd-' + d.id.value : d.key" :key="d.id != '' ? 'd-' + d.id.value : d.key" class="px-2 py-3 my-2 border rounded">
                        <button @click="removeObjectMeta(index, 'destination')" type="button" class="close text-danger" aria-label="Remove origin">
                            <span title="Remove destination">&times;</span>
                        </button>
                        <div class="form-group required">
                            <label :for="'destination-' + index">Destination</label>
                            <span class="pl-1 pointer oi oi-reload" @click="regenerateSelectData('places', $event)" title="Update places"></span>
                            <multiselect v-model="d.id" :options="placesData" options-limit="5000" label="label" track-by="value" :required="true">
                            </multiselect>
                            <input v-validate="'required'" data-vv-name="'Destination'" type="hidden" :value="d.id.value" name="dest[]">
                        </div>
                        <div class="form-group">
                            <label for="marked">Destination as marked</label>
                            <input data-vv-name="'Destination as marked'" :value="decodeHTML(d.marked)" @input="d.marked = $event.target.value" type="text" class="form-control form-control-sm">
                            <small class="form-text text-muted">
                                destination name as written in letter
                            </small>
                        </div>
                    </div>

                    <button type="button" @click="addPlaceMeta('destination')" class="my-2 btn btn-sm btn-outline-info">
                        <span class="oi oi-plus"></span>
                        Add destination
                    </button>

                    <div class="mb-3 form-check">
                        <input v-model="letter.dest_inferred" class="form-check-input" type="checkbox" id="dest_inferred" name="dest_inferred">
                        <label class="form-check-label" for="dest_inferred">
                            Destination inferred
                        </label>
                        <small class="form-text text-muted">
                            destination not specified but deduced from letter content or related materials
                        </small>
                    </div>

                    <div class="mb-3 form-check">
                        <input v-model="letter.dest_uncertain" class="form-check-input" type="checkbox" id="dest_uncertain" name="dest_uncertain">
                        <label class="form-check-label" for="dest_uncertain">
                            Destination uncertain
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="dest_note">Notes on destination</label>
                        <textarea :value="decodeHTML(letter.dest_note)" @input="letter.dest_note = $event.target.value" name="dest_note" class="form-control form-control-sm"></textarea>
                    </div>

                </fieldset>

                <fieldset id="a-content">
                    <legend>Content</legend>

                    <div class="form-group">
                        <label for="languages">Languages</label>
                        <multiselect v-model="letter.languages" :options="languages" options-limit="5000" label="label" track-by="value" :multiple="true">
                        </multiselect>
                        <input type="hidden" :value="getObjectValues(letter.languages).join(';')" name="languages">
                    </div>

                    <div class="form-group">
                        <label for="keywords">Keywords <span class="pl-1 pointer oi oi-reload" @click="regenerateKeywords($event)"></span></label>
                        <multiselect v-model="letter.keywords" :options="keywords" options-limit="5000" label="label" track-by="value" :multiple="true">
                        </multiselect>
                        <input type="hidden" :value="getObjectValues(letter.keywords).join(';')" name="keywords">
                    </div>

                    <div class="form-group">
                        <label for="category">Category <span class="pl-1 pointer oi oi-reload" @click="regenerateKeywords($event)"></span></label>
                        <multiselect v-model="letter.category" :options="category" options-limit="5000" label="label" track-by="value" :multiple="true">
                        </multiselect>
                        <input type="hidden" :value="getObjectValues(letter.category).join(';')" name="category">
                    </div>

                    <div class="form-group">
                        <label for="abstract">Abstract</label>
                        <textarea :value="decodeHTML(letter.abstract)" @input="letter.abstract = $event.target.value" name="abstract" class="form-control form-control-sm"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="incipit">Incipit</label>
                        <textarea :value="decodeHTML(letter.incipit)" @input="letter.incipit = $event.target.value" name="incipit" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            exact words opening body of letter, e.g. opening sentence or first 10 words, but not opening salutation
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="explicit">Explicit</label>
                        <textarea :value="decodeHTML(letter.explicit)" @input="letter.explicit = $event.target.value" name="explicit" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            exact words which close the body of the letter, e.g. closing sentence or closing 10 words, but not closing salutation
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="people_mentioned">People mentioned <span class="pl-1 pointer oi oi-reload" @click="regenerateSelectData('persons', $event)"></span></label>
                        <multiselect v-model="letter.mentioned" :options="personsData" options-limit="5000" label="label" track-by="value" :multiple="true">
                        </multiselect>
                        <input type="hidden" :value="getObjectValues(letter.mentioned)" name="people_mentioned">
                    </div>

                    <div class="form-group">
                        <label for="people_mentioned_notes">Notes on people mentioned</label>
                        <textarea :value="decodeHTML(letter.people_mentioned_notes)" @input="letter.people_mentioned_notes = $event.target.value" name="people_mentioned_notes" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            exact words which close the body of the letter, e.g. closing sentence or closing 10 words, but not closing salutation
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="notes_public">Notes on letter for public display</label>
                        <textarea :value="decodeHTML(letter.notes_public)" @input="letter.notes_public = $event.target.value" name="notes_public" class="form-control form-control-sm"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes_private">Editor' notes </label>
                        <textarea :value="decodeHTML(letter.notes_private)" @input="letter.notes_private = $event.target.value" name="notes_private" class="form-control form-control-sm"></textarea>
                        <small class="form-text text-muted">
                            internal, hidden editors' notes for EMLO back-end use only. Preface each note with a ‘q’ followed by the initials of the person the note is intended for, e.g. ‘qml’. End each note with your own initials.
                        </small>
                    </div>
                </fieldset>

                <fieldset id="a-related-resource">
                    <legend>Related resource</legend>

                    <div v-for="rr, index in letter.related_resources" class="px-2 py-3 my-2 border rounded">
                        <button @click="removeObjectMeta(index, 'related_resources')" type="button" class="close text-danger" aria-label="Remove related resource">
                            <span title="Remove related resource">&times;</span>
                        </button>
                        <div class="form-group">
                            <label for="rel_rec_name">Related resource name</label>
                            <input :value="decodeHTML(rr.title)" @input="rr.title = $event.target.value" type="text" class="form-control form-control-sm">
                            <small class="form-text text-muted">
                                descriptor
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="rel_rec_url">Related resource url</label>
                            <input v-validate="{url: {require_protocol: true }}" data-vv-name="'Related resource url'" :value="decodeHTML(rr.link)" @input="rr.link = $event.target.value" type="url" class="form-control form-control-sm">
                            <small class="form-text text-muted">
                                permanent URL to online resource
                            </small>
                        </div>
                    </div>

                    <button type="button" class="mt-2 mb-4 btn btn-sm btn-outline-info" @click="addNewResource">
                        <span class="oi oi-plus"></span> Add
                    </button>

                    <input type="hidden" :value="resources" name="related_resources">
                </fieldset>

                <fieldset id="a-manifestations">
                    <legend>Manifestations</legend>
                    <div class="form-group">
                        <label for="ms_manifestation">MS manifestation (EMLO)</label>
                        <multiselect v-model="letter.ms_manifestation" :options="manifestations" label="label" track-by="value">
                        </multiselect>
                        <input type="hidden" :value="letter.ms_manifestation.value" name="ms_manifestation">
                    </div>

                    <div class="form-group">
                        <label for="document_type">Document type</label>
                        <multiselect v-model="letter.document_type" :options="documentTypes" label="label" track-by="value">
                        </multiselect>
                    </div>

                    <div class="form-group">
                        <label for="preservation">Preservation</label>
                        <multiselect v-model="letter.preservation" :options="preservation" label="label" track-by="value">
                        </multiselect>
                    </div>

                    <div class="form-group">
                        <label for="copy">Type of copy</label>
                        <multiselect v-model="letter.copy" :options="copy" label="label" track-by="value">
                        </multiselect>
                    </div>

                    <input type="hidden" :value="documentTypesData" name="document_type">

                    <div class="form-group">
                        <label for="manifestation_notes">Notes on manifestation</label>
                        <textarea :value="decodeHTML(letter.manifestation_notes)" @input="letter.manifestation_notes = $event.target.value" name="manifestation_notes" class="form-control form-control-sm">{{ letter.manifestation_notes }}</textarea>
                    </div>

                </fieldset>

                <fieldset id="a-repositories-and-versions">
                    <legend>Repositories and versions</legend>
                    <div class="form-group">
                        <label for="number">Letter number</label>
                        <input :value="decodeHTML(letter.l_number)" @input="letter.l_number = $event.target.value" type="text" class="form-control form-control-sm" id="l_number" name="l_number">
                    </div>

                    <div class="form-group">
                        <label for="repository">Repository <span class="pl-1 pointer oi oi-reload" @click="regenerateSelectData('locations', $event)"></span></label>
                        <input :value="decodeHTML(letter.repository)" @input="letter.repository = $event.target.value" list="repositories" type="text" name="repository" class="form-control form-control-sm">
                        <datalist id="repositories">
                            <option v-for="rep in repositories" v-html="rep.name"></option>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="archive">Archive</label>
                        <input :value="decodeHTML(letter.archive)" @input="letter.archive = $event.target.value" list="archives" type="text" name="archive" class="form-control form-control-sm">
                        <datalist id="archives">
                            <option v-for="a in archives" v-html="a.name"></option>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="collection">Collection</label>
                        <input :value="decodeHTML(letter.collection)" @input="letter.collection = $event.target.value" list="collections" type="text" name="collection" class="form-control form-control-sm">
                        <datalist id="collections">
                            <option v-for="c in collections" v-html="c.name"></option>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="signature">Signature</label>
                        <input :value="decodeHTML(letter.signature)" @input="letter.signature = $event.target.value" type="text" name="signature" class="form-control form-control-sm">
                    </div>

                    <div class="form-group">
                        <label for="location_note">Notes on location</label>
                        <textarea :value="decodeHTML(letter.location_note)" @input="letter.location_note = $event.target.value" name="location_note" class="form-control form-control-sm">{{ letter.location_note }}</textarea>
                    </div>
                </fieldset>

                <fieldset id="a-description">
                    <legend>Description</legend>
                    <div class="form-group required">
                        <label for="description">Description</label>
                        <span class="pl-1 pointer oi oi-transfer" @click="title = getTitle()"></span>
                        <textarea v-validate="'required'" data-vv-name="'Description'" :value="decodeHTML(title)" @input="title = $event.target.value" name="description" class="form-control form-control-sm" required>{{ title }}</textarea>
                        {{ letter.title }}

                        <small class="form-text text-muted">
                            "DD. MM. YYYY Author (Origin) to Recipient (Destination)", vygenerovat pomocí ikonky
                        </small>
                    </div>
                </fieldset>

                <fieldset id="a-status" class="form-group">
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

                <input type="hidden" :value="decodeHTML(placesMeta)" name="places_meta">
                <input type="hidden" :value="decodeHTML(participantsMeta)" name="authors_meta">

                <div v-if="errors.all().length > 0" class="alert alert-danger">
                    <ul class="px-2 m-0">
                        <li v-for="error in errors.all()">{{ error }}</li>
                    </ul>
                </div>

                <?php if ($action == 'new') : ?>
                    <input type="hidden" name="save_post" value="new">
                <?php else : ?>
                    <input type="hidden" name="save_post" value="edit">
                <?php endif; ?>

                <input :disabled="errors.all().length > 0" type="submit" value="Uložit" class="btn btn-primary">
                <a v-if="edit" :href="imgUrl" class="btn btn-secondary" target="_blank">Obrazové přílohy</a>
                <a v-if="edit" :href="previewUrl" class="btn btn-secondary" target="_blank">Náhled</a>
            </form>
        </div>
    </div>
</div>

<?= display_persons_and_places($person_type, $place_type); ?>
<?= get_json_languages(); ?>
