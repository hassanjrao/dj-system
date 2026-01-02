<template>
  <v-app>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <span>Users</span>
            <v-tooltip bottom>
              <template v-slot:activator="{ on, attrs }">
                <v-btn icon small v-bind="attrs" v-on="on" @click="getUsers" class="ml-2">
                  <v-icon>mdi-refresh</v-icon>
                </v-btn>
              </template>
              <span>Refresh Data</span>
            </v-tooltip>
            <v-spacer></v-spacer>
            <v-tooltip bottom>
              <template v-slot:activator="{ on, attrs }">
                <v-btn
                  color="success"
                  v-bind="attrs"
                  v-on="on"
                  small
                  @click="openAddDialog"
                >
                  <v-icon>mdi-plus</v-icon>
                  Add User
                </v-btn>
              </template>
              <span>Add New User</span>
            </v-tooltip>
          </v-card-title>

          <!-- Search Field -->
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="searchQuery"
                  label="Search"
                  dense
                  clearable
                  prepend-inner-icon="mdi-magnify"
                  @input="debouncedSearch"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-card-text>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="filteredUsers"
              :loading="loading"
              class="elevation-1"
              :items-per-page="25"
            >
              <template v-slot:item.index="{ item }">
                {{ item.index }}
              </template>
              <template v-slot:item.name="{ item }">
                {{ item.name }}
              </template>
              <template v-slot:item.email="{ item }">
                {{ item.email }}
              </template>
              <template v-slot:item.role="{ item }">
                <v-chip
                  v-for="role in item.roles"
                  :key="role.name"
                  small
                  class="mr-1"
                  color="primary"
                >
                  {{ role.name }}
                </v-chip>
                <span
                  v-if="!item.roles || item.roles.length === 0"
                  class="text--secondary"
                  >No role</span
                >
              </template>
              <template v-slot:item.departments="{ item }">
                <div v-if="item.departments && item.departments.length > 0">
                  <v-chip
                    v-for="dept in item.departments"
                    :key="dept.id"
                    small
                    class="mr-1 mb-1"
                    color="secondary"
                  >
                    {{ dept.name }}
                  </v-chip>
                </div>
                <span v-else class="text--secondary">No departments</span>
              </template>
              <template v-slot:item.actions="{ item }">
                <v-tooltip bottom>
                  <template v-slot:activator="{ on, attrs }">
                    <v-btn
                      icon
                      small
                      color="primary"
                      v-bind="attrs"
                      v-on="on"
                      @click.stop="openEditDialog(item)"
                    >
                      <v-icon small>mdi-pencil</v-icon>
                    </v-btn>
                  </template>
                  <span>Edit User</span>
                </v-tooltip>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- User Form Dialog -->
    <user-form-dialog
      :dialog="dialog"
      :edit-mode="editMode"
      :user-data="selectedUser"
      @close="closeDialog"
      @saved="handleUserSaved"
    />
  </v-app>
</template>

<script>
export default {
  name: "UserList",
  data() {
    return {
      loading: false,
      users: [],
      filteredUsers: [],
      searchQuery: "",
      searchTimeout: null,
      dialog: false,
      editMode: false,
      selectedUser: null,
      headers: [
        { text: "#", value: "index", sortable: true, width: "60" },
        { text: "Name", value: "name", sortable: true },
        { text: "Email", value: "email", sortable: true },
        { text: "Role", value: "role", sortable: false },
        { text: "Departments", value: "departments", sortable: false },
        {
          text: "Actions",
          value: "actions",
          sortable: false,
          align: "center",
          width: "100",
        },
      ],
    };
  },
  mounted() {
    this.getUsers();
  },
  watch: {
    users() {
      this.filterUsers();
    },
  },
  methods: {
    async getUsers() {
      this.loading = true;
      try {
        const response = await axios.get("/users/list");
        this.users = response.data.data;
        this.filterUsers();
      } catch (error) {
        console.error("Error loading users:", error);
        if (error.response && error.response.status === 403) {
          alert("You do not have permission to access this page.");
        } else {
          this.$toast?.error("Failed to load users");
        }
      } finally {
        this.loading = false;
      }
    },
    filterUsers() {
      if (!this.searchQuery || this.searchQuery.trim() === "") {
        this.filteredUsers = this.users;
        return;
      }

      const query = this.searchQuery.toLowerCase().trim();
      this.filteredUsers = this.users.filter((user) => {
        const nameMatch = user.name?.toLowerCase().includes(query);
        const emailMatch = user.email?.toLowerCase().includes(query);
        const roleMatch = user.roles?.some((role) =>
          role.name?.toLowerCase().includes(query)
        );
        const departmentMatch = user.departments?.some((dept) =>
          dept.name?.toLowerCase().includes(query)
        );

        return nameMatch || emailMatch || roleMatch || departmentMatch;
      });
    },
    debouncedSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.filterUsers();
      }, 300);
    },
    openAddDialog() {
      this.editMode = false;
      this.selectedUser = null;
      this.dialog = true;
    },
    openEditDialog(user) {
      this.editMode = true;
      this.selectedUser = { ...user };
      this.dialog = true;
    },
    closeDialog() {
      this.dialog = false;
      this.selectedUser = null;
    },
    handleUserSaved() {
      this.closeDialog();
      this.getUsers();
    },
  },
};
</script>
