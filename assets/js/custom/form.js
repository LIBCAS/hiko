/* global Vue VeeValidate */

if (window.hasOwnProperty('VueMultiselect')) {
    Vue.component('multiselect', window.VueMultiselect.default)
}

if (typeof VeeValidate !== 'undefined') {
    Vue.use(VeeValidate)
}
