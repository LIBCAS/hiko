"use strict";

/* global SlimSelect Vue axios ajaxUrl */
if (document.getElementById('letter-form')) {
  new Vue({
    el: '#letter-add-form',
    data: {
      error: false,
      author: [],
      author_as_marked: '',
      author_inferred: '',
      author_uncertain: '',
      recipient: [],
      recipient_marked: '',
      recipient_inferred: '',
      recipient_uncertain: '',
      recipient_notes: '',
      mentioned: [],
      origin: '',
      origin_marked: '',
      origin_inferred: '',
      origin_uncertain: '',
      destination: '',
      dest_marked: '',
      dest_inferred: '',
      dest_uncertain: '',
      day: '',
      month: '',
      year: '',
      date_marked: '',
      date_uncertain: '',
      title: '',
      l_number: '',
      languages: [],
      keywords: [],
      abstract: '',
      incipit: '',
      explicit: '',
      people_mentioned_notes: '',
      notes_public: '',
      notes_private: '',
      rel_rec_name: '',
      rel_rec_url: '',
      ms_manifestation: '',
      repository: '',
      status: '',
      persons: JSON.parse(document.querySelector('#people').innerHTML),
      places: JSON.parse(document.querySelector('#places').innerHTML)
    },
    methods: {
      getTitle: function getTitle() {
        var authors = [];
        var recipients = [];

        for (var i = 0; i < this.author.length; i++) {
          authors.push(getNameById(this.persons, this.author[i]));
        }

        for (var _i = 0; _i < this.recipient.length; _i++) {
          recipients.push(getNameById(this.persons, this.recipient[_i]));
        }

        var origin = getNameById(this.places, this.origin);
        var destination = getNameById(this.places, this.destination);
        var date = this.day + '. ' + this.month + '. ' + this.year;
        var from = authors.join('; ') + ' (' + origin + ')';
        var to = recipients + ' (' + destination + ')';
        this.title = date + ' ' + from + ' to ' + to;
        return;
      },
      getInitialData: function getInitialData(id) {
        var self = this;
        axios.get(ajaxUrl + '?action=list_public_bl_letters_single&pods_id=' + id).then(function (response) {
          if (response.data == '404') {
            self.error = true;
          } else {
            var rd = response.data;
            console.log(rd);
            self.l_number = rd.l_number;
            self.year = rd.date_year == '0' ? '' : rd.date_year;
            self.month = rd.date_month == '0' ? '' : rd.date_month;
            self.day = rd.date_day == '0' ? '' : rd.date_day;
            self.date_marked = rd.date_marked;
            self.date_uncertain = rd.date_uncertain;
            self.author = Object.keys(rd.l_author);
            self.author_as_marked = rd.l_author_marked;
            self.author_inferred = rd.author_inferred;
            self.author_uncertain = rd.author_uncertain;
            self.recipient = Object.keys(rd.recipient);
            self.recipient_marked = rd.recipient_marked;
            self.recipient_inferred = rd.recipient_inferred;
            self.recipient_uncertain = rd.recipient_uncertain;
            self.recipient_notes = rd.recipient_notes;
            self.origin = Object.keys(rd.origin)[0];
            self.origin_marked = rd.origin_marked;
            self.origin_inferred = rd.origin_inferred;
            self.origin_uncertain = rd.origin_uncertain;
            self.destination = Object.keys(rd.dest)[0];
            self.dest_marked = rd.dest_marked;
            self.dest_inferred = rd.dest_inferred;
            self.dest_uncertain = rd.dest_uncertain;
            self.languages = rd.languages.length === 0 ? [] : rd.languages.split(';');
            self.keywords = rd.keywords.length === 0 ? [] : rd.keywords.split(';');
            self.abstract = rd.abstract;
            self.incipit = rd.incipit;
            self.explicit = rd.explicit;
            self.mentioned = Object.keys(rd.people_mentioned);
            self.people_mentioned_notes = rd.people_mentioned_notes;
            self.notes_public = rd.notes_public;
            self.notes_private = rd.notes_private;
            self.rel_rec_name = rd.rel_rec_name;
            self.rel_rec_url = rd.rel_rec_url;
            self.ms_manifestation = rd.ms_manifestation;
            self.repository = rd.repository;
            self.title = rd.name;
            self.status = rd.status;
          }
        }).catch(function () {
          self.error = true;
        }).then(function () {
          self.addSlimSelect();
        });
      },
      ajaxToData: function ajaxToData(action, targetData, targetElement) {
        var self = this;
        targetElement.classList.add('rotate');
        axios.get(ajaxUrl + '?action=' + action).then(function (response) {
          self[targetData] = response.data;
        }).catch(function (error) {
          console.log(error);
        }).then(function () {
          targetElement.classList.remove('rotate');
        });
      },
      regenerateSelectData: function regenerateSelectData(event) {
        var type = event.target.dataset.source;
        var self = this;

        if (type == 'persons') {
          self.ajaxToData('list_bl_people_simple', 'persons', event.target);
        } else if (type == 'places') {
          self.ajaxToData('list_bl_places_simple', 'places', event.target);
        }
      },
      addSlimSelect: function addSlimSelect() {
        Array.prototype.forEach.call(document.querySelectorAll('.slim-select'), function (selected) {
          if (selected.id) {
            new SlimSelect({
              select: '#' + selected.id
            });
          }
        });
      },
      removeKeyword: function removeKeyword(kw) {
        this.keywords = this.keywords.filter(function (item) {
          return item !== kw;
        });
      }
    },
    mounted: function mounted() {
      var url = new URL(window.location.href);

      if (url.searchParams.get('edit')) {
        this.getInitialData(url.searchParams.get('edit'));
      } else {
        this.addSlimSelect();
      }
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
      getInitialData: function getInitialData(id) {
        var self = this;
        axios.get(ajaxUrl + '?action=list_bl_place_single&pods_id=' + id).then(function (response) {
          if (response.data == '404') {
            self.error = true;
          } else {
            self.place = response.data.name;
            self.country = response.data.country;
          }
        }).catch(function () {
          self.error = true;
        });
      }
    },
    mounted: function mounted() {
      var url = new URL(window.location.href);

      if (url.searchParams.get('edit')) {
        this.getInitialData(url.searchParams.get('edit'));
      }
    }
  });
  Array.prototype.forEach.call(document.querySelectorAll('.slim-select'), function (selected) {
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
      fullName: function fullName() {
        var fullName;
        fullName = this.capitalize(this.lastName).trim() + ', ' + this.capitalize(this.firstName).trim();
        return fullName.trim();
      },
      personsFormValidated: function personsFormValidated() {
        if (this.firstName == '' || this.lastName == '' || this.fullName.length < 8) {
          return false;
        }

        return true;
      }
    },
    methods: {
      capitalize: function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
      },
      getInitialData: function getInitialData(id) {
        var self = this;
        axios.get(ajaxUrl + '?action=list_bl_people_single&pods_id=' + id).then(function (response) {
          if (response.data == '404') {
            self.error = true;
          } else {
            self.firstName = response.data.forename;
            self.lastName = response.data.surname;
            self.emlo = response.data.emlo;
            self.dob = response.data.birth_year;
            self.dod = response.data.death_year;
          }
        }).catch(function (error) {
          self.error = true;
          console.log(error);
        });
      }
    },
    mounted: function mounted() {
      var url = new URL(window.location.href);

      if (url.searchParams.get('edit')) {
        this.getInitialData(url.searchParams.get('edit'));
      }
    }
  });
}

if (document.getElementById('add-new-keyword')) {
  document.querySelector('#add-new-keyword').addEventListener('click', function () {
    addNewInput(this);
  });
}

if (document.querySelector('.keywords input')) {
  document.querySelector('.keywords input').addEventListener('keyup', function (e) {
    clickButton(e);
  });
}

function addNewInput(el) {
  var newInput = "<div class=\"input-group input-group-sm mb-1\">\n    <input type=\"text\" name=\"keywords[]\" class=\"form-control form-control-sm\">\n        <div class=\"input-group-append\">\n            <button class=\"btn btn-sm btn-outline-danger btn-remove\" type=\"button\">\n                <span class=\"oi oi-x\"></span>\n            </button>\n        </div>\n    </div>";
  el.insertAdjacentHTML('beforebegin', newInput);
  el.previousSibling.querySelector('input').focus();
  el.previousSibling.querySelector('.btn-remove').addEventListener('click', function () {
    removeSecondParent(this);
  });
  el.previousSibling.querySelector('input').addEventListener('keyup', function (e) {
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
  var filtered = data.filter(function (line) {
    return line.id == id;
  });

  if (filtered.length == 0) {
    return false;
  }

  return filtered[0].name;
}

function removeSecondParent(el) {
  console.log(el);
  el.parentNode.parentNode.parentNode.removeChild(el.parentNode.parentNode);
  return;
}
"use strict";

/* global Vue VueTables Swal axios ajaxUrl */
var columns;
var defaultTablesOptions = {
  skin: 'table table-bordered table-hover table-striped table-sm',
  sortIcon: {
    base: 'oi pl-1',
    up: 'oi-arrow-top',
    down: 'oi-arrow-bottom',
    is: 'oi-elevator'
  },
  texts: {
    count: 'Zobrazena položka {from} až {to} z celkového počtu {count} položek |{count} položky|Jedna položka',
    first: 'První',
    last: 'Poslední',
    filter: 'Filtr: ',
    filterPlaceholder: 'Hledat',
    limit: 'Položky: ',
    page: 'Strana: ',
    noResults: 'Nenalezeno',
    filterBy: 'Filtrovat dle {column}',
    loading: 'Načítá se...',
    defaultOption: 'Vybrat {column}',
    columns: 'Columns'
  }
};

if (document.getElementById('datatable-letters')) {
  var tabledata;

  if (document.querySelector('#letters-data') !== null) {
    tabledata = JSON.parse(document.querySelector('#letters-data').innerHTML);
  } else {
    tabledata = null;
  }

  Vue.use(VueTables.ClientTable, false, false, 'bootstrap4');
  columns = ['edit', 'number', 'date', 'author', 'recipient', 'origin', 'dest', 'status'];
  new Vue({
    el: '#datatable-letters',
    data: {
      columns: columns,
      tableData: tabledata,
      options: {
        headings: {
          edit: 'Akce',
          dest: 'Destination'
        },
        skin: defaultTablesOptions.skin,
        sortable: removeElFromArr('edit', columns),
        filterable: removeElFromArr('edit', columns),
        sortIcon: defaultTablesOptions.sortIcon,
        texts: defaultTablesOptions.texts,
        dateColumns: ['date'],
        rowClassCallback: function rowClassCallback(row) {
          return 'row-' + row.id;
        }
      }
    },
    methods: {
      deleteLetter: function deleteLetter(id) {
        var self = this;
        removeItemAjax(id, 'delete_bl_letter', function () {
          self.deleteRow(id, self.tableData);
        });
      },
      deleteRow: function deleteRow(id, data) {
        this.tableData = data.filter(function (item) {
          return item.id !== id;
        });
      }
    }
  });
}

if (document.getElementById('datatable-persons')) {
  Vue.use(VueTables.ClientTable, false, false, 'bootstrap4');
  columns = ['edit', 'name', 'dates'];
  new Vue({
    el: '#datatable-persons',
    data: {
      columns: columns,
      tableData: JSON.parse(document.querySelector('#persons-data').innerHTML),
      options: {
        headings: {
          edit: 'Akce'
        },
        skin: defaultTablesOptions.skin,
        sortable: removeElFromArr('edit', columns),
        filterable: removeElFromArr('edit', columns),
        sortIcon: defaultTablesOptions.sortIcon,
        texts: defaultTablesOptions.texts,
        rowClassCallback: function rowClassCallback(row) {
          return 'row-' + row.id;
        }
      }
    },
    methods: {
      deletePerson: function deletePerson(id) {
        var self = this;
        removeItemAjax(id, 'delete_bl_person', function () {
          self.deleteRow(id, self.tableData);
        });
      },
      deleteRow: function deleteRow(id, data) {
        this.tableData = data.filter(function (item) {
          return item.id !== id;
        });
      }
    }
  });
}

if (document.getElementById('datatable-places')) {
  Vue.use(VueTables.ClientTable, false, false, 'bootstrap4');
  columns = ['edit', 'city', 'country'];
  new Vue({
    el: '#datatable-places',
    data: {
      columns: columns,
      tableData: JSON.parse(document.querySelector('#places-data').innerHTML),
      options: {
        headings: {
          edit: 'Akce'
        },
        skin: defaultTablesOptions.skin,
        sortable: removeElFromArr('edit', columns),
        filterable: removeElFromArr('edit', columns),
        sortIcon: defaultTablesOptions.sortIcon,
        texts: defaultTablesOptions.texts,
        rowClassCallback: function rowClassCallback(row) {
          return 'row-' + row.id;
        }
      }
    },
    methods: {
      deletePlace: function deletePlace(id) {
        var self = this;
        removeItemAjax(id, 'delete_bl_place', function () {
          self.deleteRow(id, self.tableData);
        });
      },
      deleteRow: function deleteRow(id, data) {
        this.tableData = data.filter(function (item) {
          return item.id !== id;
        });
      }
    }
  });
}

function removeElFromArr(el, array) {
  var filtered = array.filter(function (value) {
    return value != el;
  });
  return filtered;
}

function removeItemAjax(id, action, callback) {
  Swal.fire({
    title: 'Opravdu chcete smazat tuto položku?',
    type: 'warning',
    buttonsStyling: false,
    showCancelButton: true,
    confirmButtonText: 'Ano!',
    cancelButtonText: 'Zrušit',
    confirmButtonClass: 'btn btn-primary btn-lg mr-1',
    cancelButtonClass: 'btn btn-secondary btn-lg ml-1'
  }).then(function (result) {
    if (result.value) {
      axios.get(ajaxUrl + '?action=' + action + '&pods_id=' + id).then(function () {
        Swal.fire({
          title: 'Odstraněno.',
          type: 'success',
          buttonsStyling: false,
          confirmButtonText: 'OK',
          confirmButtonClass: 'btn btn-primary btn-lg'
        });
        callback();
      }).catch(function (error) {
        Swal.fire({
          title: 'Při odstraňování došlo k chybě.',
          text: error,
          type: 'error',
          buttonsStyling: false,
          confirmButtonText: 'OK',
          confirmButtonClass: 'btn btn-primary btn-lg'
        });
      });
    }
  });
}