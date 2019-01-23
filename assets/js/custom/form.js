/* global SlimSelect Vue axios ajaxUrl */


if (document.getElementById('letter-form')) {
    new Vue({
        el: '#letter-form',
        data: {
            author: [],
            recipient: [],
            origin: '',
            destination: '',
            day: '',
            month: '',
            year: '',
            title: '',
            persons: JSON.parse(document.querySelector('#people').innerHTML),
            places: JSON.parse(document.querySelector('#places').innerHTML)
        },
        methods: {
            getTitle: function() {
                let authors = [];
                let recipients = [];
                for (let i = 0; i < this.author.length; i++) {
                    authors.push(getNameById(this.persons, this.author[i]));
                }
                for (let i = 0; i < this.recipient.length; i++) {
                    recipients.push(getNameById(this.persons, this.recipient[i]));
                }

                let origin = getNameById(this.places, this.origin);
                let destination = getNameById(this.places, this.destination);

                let date = this.day + '. ' + this.month + '. ' + this.year;
                let from = authors.join('; ') + ' (' + origin + ')';
                let to = recipients + ' (' + destination + ')';

                this.title = date + ' ' + from + ' to ' + to;
                return;
            },

            regenerateSelectData: function(event) {
                let type = event.target.dataset.source;
                let vueInstance = this;
                if (type == 'persons') {
                    event.target.classList.add('rotate');
                    axios
                        .get(ajaxUrl + '?action=list_bl_people_simple')
                        .then(function(response) {
                            vueInstance.persons = response.data;
                        })
                        .catch(function(error) {
                            console.log(error);
                        })
                        .then(function() {
                            event.target.classList.remove('rotate');
                        });
                } else if (type == 'places') {
                    return;
                } else {
                    return;
                }
            }
        }
    });

    Array.prototype.forEach.call(document.querySelectorAll('.slim-select'), function(selected) {
        if (selected.id) {
            new SlimSelect({
                select: '#' + selected.id
            });
        }
    });
}


if (document.getElementById('places-form')) {
    new Vue({
        el: '#places-form',
        data: {
            place: '',
            country: ''
        },
        methods: {
            getInitialData: function(id) {
                let self = this;
                axios
                    .get(ajaxUrl + '?action=list_bl_place_single&pods_id=' + id)
                    .then(function(response) {
                        if (response.data == '404') {
                            self.error = true;
                        } else {
                            self.place = response.data.name;
                            self.country = response.data.country;
                        }

                    })
                    .catch(function() {
                        self.error = true;
                    });
            }
        },

        mounted: function() {
            let url = new URL(window.location.href);
            if (url.searchParams.get('edit')) {
                this.getInitialData(url.searchParams.get('edit'));
            }
        }
    });

    Array.prototype.forEach.call(document.querySelectorAll('.slim-select'), function(selected) {
        if (selected.id) {
            new SlimSelect({
                select: '#' + selected.id
            });
        }
    });
}

if (document.getElementById('person-name')) {
    new Vue({
        el: '#person-name',
        data: {
            firstName: '',
            lastName: '',
            emlo: '',
            dob: '',
            dod: '',
            error: false
        },

        computed: {
            fullName: function() {
                let fullName;
                fullName = this.capitalize(this.lastName).trim() + ', ' + this.capitalize(this.firstName).trim();
                return fullName.trim();
            },
            personsFormValidated: function() {
                if (this.firstName == '' || this.lastName == '' || this.fullName.length < 8) {
                    return false;
                }
                return true;
            }
        },
        methods: {
            capitalize: function(str) {
                return str.charAt(0).toUpperCase() + str.slice(1);
            },

            getInitialData: function(id) {
                let self = this;
                axios
                    .get(ajaxUrl + '?action=list_bl_people_single&pods_id=' + id)
                    .then(function(response) {
                        if (response.data == '404') {
                            self.error = true;
                        } else {
                            self.firstName = response.data.forename;
                            self.lastName = response.data.surname;
                            self.emlo = response.data.emlo;
                            self.dob = response.data.birth_year;
                            self.dod = response.data.death_year;
                        }

                    })
                    .catch(function(error) {
                        self.error = true;
                        console.log(error);
                    });
            }
        },

        mounted: function() {
            let url = new URL(window.location.href);
            if (url.searchParams.get('edit')) {
                this.getInitialData(url.searchParams.get('edit'));
            }
        }
    });
}

if (document.getElementById('add-new-keyword')) {
    document.querySelector('#add-new-keyword').addEventListener('click', function() {
        addNewInput(this);
    });
}

if (document.querySelector('.keywords input')) {
    document.querySelector('.keywords input').addEventListener('keyup', function(e) {
        clickButton(e);
    });
}


function addNewInput(el) {
    var newInput = `<div class="input-group input-group-sm mb-1">
    <input type="text" name="keywords[]" class="form-control form-control-sm">
        <div class="input-group-append">
            <button class="btn btn-sm btn-outline-danger btn-remove" type="button">
                <span class="oi oi-x"></span>
            </button>
        </div>
    </div>`;
    el.insertAdjacentHTML('beforebegin', newInput);

    el.previousSibling.querySelector('input').focus();

    el.previousSibling.querySelector('.btn-remove').addEventListener('click', function() {
        removeSecondParent(this);
    });

    el.previousSibling.querySelector('input').addEventListener('keyup', function(e) {
        clickButton(e);
    });
    return;
}

function clickButton(e) {
    e.preventDefault();
    if (e.keyCode === 13) {
        document.querySelector('#add-new-keyword').click();
    }
}

function getNameById(data, id) {
    var filtered = data.filter(function(line) {
        return line.id == id;
    });

    if (filtered.length == 0) {
        return false;
    }

    return filtered[0].name;
}

function removeSecondParent(el) {
    el.parentNode.parentNode.parentNode.removeChild(el.parentNode.parentNode);
    return;
}
