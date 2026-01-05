require('./bootstrap');
import Vue from 'vue';
import vuetify from './plugins/vuetify';
import store from './store';


// Set up axios CSRF token
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}


// Register Vue components globally
Vue.component('assignment-form', require('./components/assignments/AssignmentForm.vue').default);
Vue.component('music-creation-form', require('./components/assignments/MusicCreationForm.vue').default);
Vue.component('music-mastering-form', require('./components/assignments/MusicMasteringForm.vue').default);
Vue.component('graphic-design-form', require('./components/assignments/GraphicDesignForm.vue').default);
Vue.component('video-filming-form', require('./components/assignments/VideoFilmingForm.vue').default);
Vue.component('video-editing-form', require('./components/assignments/VideoEditingForm.vue').default);
Vue.component('distribution-form', require('./components/assignments/DistributionForm.vue').default);
Vue.component('assignment-list', require('./components/assignments/AssignmentList.vue').default);
Vue.component('user-list', require('./components/users/UserList.vue').default);
Vue.component('user-form-dialog', require('./components/users/UserFormDialog.vue').default);
Vue.component('profile-update', require('./components/profile/ProfileUpdate.vue').default);


// Fetch user data before initializing app
store.dispatch('auth/fetchUser').then(() => {
    const app = new Vue({
        el: '#vue-app',
        vuetify,
        store,
    });
}).catch((error) => {
    console.error('Failed to initialize app:', error);
    // Still create the app even if fetching user fails
    const app = new Vue({
        el: '#vue-app',
        vuetify,
        store,
    });
});



