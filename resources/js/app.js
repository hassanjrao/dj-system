require('./bootstrap');

window.Vue = require('vue').default;
window.Vuetify = require('vuetify').default;

Vue.use(Vuetify);

// Set up axios CSRF token
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Vuetify instance
window.vuetifyInstance = new Vuetify({
    theme: {
        themes: {
            light: {
                primary: '#1976D2',
                secondary: '#424242',
                accent: '#82B1FF',
                error: '#FF5252',
                info: '#2196F3',
                success: '#4CAF50',
                warning: '#FB8C00',
            },
        },
    },
});