const state = {
    user: null,
    roles: [],
    permissions: [],
    departments: [],
    loading: false,
    error: null,
};

const mutations = {
    SET_USER(state, user) {
        state.user = user;
        state.roles = user?.roles || [];
        state.permissions = user?.permissions || [];
        state.departments = user?.departments || [];
    },
    SET_LOADING(state, loading) {
        state.loading = loading;
    },
    SET_ERROR(state, error) {
        state.error = error;
    },
};

const actions = {
    async fetchUser({ commit }) {
        commit('SET_LOADING', true);
        commit('SET_ERROR', null);

        try {
            const response = await window.axios.get('/auth/user');
            commit('SET_USER', response.data);
            return response.data;
        } catch (error) {
            console.error('Error fetching user:', error);
            commit('SET_ERROR', error.message || 'Failed to fetch user data');
            throw error;
        } finally {
            commit('SET_LOADING', false);
        }
    },
};

const getters = {
    user: (state) => state.user,
    roles: (state) => state.roles,
    permissions: (state) => state.permissions,
    departments: (state) => state.departments,
    loading: (state) => state.loading,
    error: (state) => state.error,

    // Check if user has a specific role
    hasRole: (state) => (role) => {
        return state.roles.includes(role);
    },

    // Check if user has any of the specified roles
    hasAnyRole: (state) => (roles) => {
        if (!Array.isArray(roles)) {
            roles = [roles];
        }
        return roles.some(role => state.roles.includes(role));
    },

    // Check if user has a specific permission
    hasPermission: (state) => (permission) => {
        console.log('permissionssss',state.permissions, permission);
        return state.permissions.includes(permission);
    },

    // Check if user is super-admin
    isSuperAdmin: (state) => {
        return state.roles.includes('super-admin');
    },

    // Check if user is admin or super-admin
    isAdmin: (state) => {
        return state.roles.includes('admin') || state.roles.includes('super-admin');
    },
};

export default {
    namespaced: true,
    state,
    mutations,
    actions,
    getters,
};

