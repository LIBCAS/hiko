"use strict";

/* global SlimSelect Vue axios ajaxUrl */
if (document.getElementById('letter-form')) {
  new Vue({
    el: '#letter-form',
    data: {
      author: '',
      recipient: '',
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
      getTitle: function getTitle() {
        var author = getNameById(this.persons, this.author);
        var recipient = getNameById(this.persons, this.recipient);
        var origin = getNameById(this.places, this.origin);
        var destination = getNameById(this.places, this.destination);
        var date = this.day + '. ' + this.month + '. ' + this.year;
        var from = author + ' (' + origin + ')';
        var to = recipient + ' (' + destination + ')';
        this.title = date + ' ' + from + ' to ' + to;
        return;
      },
      regenerateSelectData: function regenerateSelectData(event) {
        var type = event.target.dataset.source;
        var vueInstance = this;

        if (type == 'persons') {
          event.target.classList.add('rotate');
          axios.get(ajaxUrl + '?action=list_bl_people_simple').then(function (response) {
            vueInstance.persons = response.data;
          }).catch(function (error) {
            console.log(error);
          }).then(function () {
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
  Array.prototype.forEach.call(document.querySelectorAll('.slim-select'), function (selected) {
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