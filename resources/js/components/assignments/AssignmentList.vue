<template>
  <v-app>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <span>Assignments</span>
            <v-tooltip bottom>
              <template v-slot:activator="{ on, attrs }">
                <v-btn
                  icon
                  small
                  v-bind="attrs"
                  v-on="on"
                  @click="getAssignments"
                  class="ml-2"
                >
                  <v-icon>mdi-refresh</v-icon>
                </v-btn>
              </template>
              <span>Refresh Data</span>
            </v-tooltip>
            <v-spacer></v-spacer>
            <v-tooltip bottom>
              <template v-slot:activator="{ on, attrs }">
                <v-btn
                v-if="$store.getters['auth/hasPermission']('create-assignments')"
                  color="success"
                  v-bind="attrs"
                  v-on="on"
                  small
                  @click="createAssignment"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>
              </template>
              <span>Create New Assignment</span>
            </v-tooltip>
          </v-card-title>

          <!-- Filters -->
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-autocomplete
                  v-model="selectedClient"
                  :items="clients"
                  item-text="name"
                  item-value="id"
                  label="Filter by Client"
                  dense
                  clearable
                  multiple
                  chips
                  small-chips
                  @change="getAssignments"
                ></v-autocomplete>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="searchQuery"
                  label="Search"
                  dense
                  clearable
                  @input="debouncedSearch"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-card-text>

          <!-- Tabs -->
          <v-tabs v-model="activeTab" @change="getAssignments">
            <v-tab value="all" key="all"> My Assignments </v-tab>
            <v-tab value="active" key="active">
              Active
              <v-chip v-if="activeCount > 0" small class="ml-2" :color="'primary'">
                {{ activeCount }}
              </v-chip>
              <v-chip v-if="overdueCount > 0" small color="error" class="ml-1">
                {{ overdueCount }} overdue
              </v-chip>
            </v-tab>
            <v-tab value="completed" key="completed">
              Completed
              <v-chip v-if="completedCount > 0" small class="ml-2" color="success">
                {{ completedCount }}
              </v-chip>
            </v-tab>
          </v-tabs>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="assignments"
              :loading="loading"
              class="elevation-1"
              @click:row="goToEdit"
              :items-per-page="25"
            >
              <template v-slot:item.assignment_id="{ item }">
                <div>
                  <div class="font-weight-medium">
                    {{ item.assignment_id || `#${item.id}` }}
                  </div>
                  <div class="text-caption text--secondary">
                    {{ item.department ? item.department.name : "N/A" }}
                  </div>
                </div>
              </template>
              <template v-slot:item.assignment_name="{ item }">
                <div>
                  <div class="font-weight-medium">
                    {{ item.assignment_display_name || "N/A" }}
                  </div>
                  <div class="text-caption text--secondary">
                    {{ item.created_by ? item.created_by.name : "N/A" }}
                  </div>
                </div>
              </template>
              <template v-slot:item.client.name="{ item }">
                {{ item.client ? item.client.name : "N/A" }}
              </template>
              <template v-slot:item.deliverables="{ item }">
                <div v-if="item.deliverables && item.deliverables.length > 0">
                  <v-chip
                    v-for="deliverable in item.deliverables"
                    :key="deliverable.id"
                    small
                    class="mr-1 mb-1"
                    color="primary"
                  >
                    {{ deliverable.name }}
                  </v-chip>
                </div>
                <span v-else class="text--secondary">No deliverables</span>
              </template>
              <template v-slot:item.assignment_status="{ item }">
                <v-chip :color="getStatusColor(item.assignment_status)" small>
                  {{ formatStatus(item.assignment_status) }}
                </v-chip>
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
                      @click.stop="goToEdit(item)"
                    >
                      <v-icon small>mdi-pencil</v-icon>
                    </v-btn>
                  </template>
                  <span>Edit Assignment</span>
                </v-tooltip>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-app>
</template>

<script>
export default {
  name: "AssignmentList",
  props: {
    departmentId: {
      type: [Number, String],
      required: false,
      default: null,
    },
  },
  data() {
    return {
      loading: false,
      assignments: [],
      selectedClient: [],
      searchQuery: "",
      clients: [],
      activeCount: 0,
      overdueCount: 0,
      completedCount: 0,
      canCreate: false,
      headers: [
        { text: "Assignment ID", value: "assignment_id", sortable: false },
        { text: "Due Date", value: "completion_date", sortable: true },
        { text: "Assignment", value: "assignment_name", sortable: false },
        { text: "Client", value: "client.name", sortable: false },
        { text: "Deliverables", value: "deliverables", sortable: false },
        { text: "Status", value: "assignment_status", sortable: false },
        {
          text: "Actions",
          value: "actions",
          sortable: false,
          align: "center",
          width: "100",
        },
      ],
      searchTimeout: null,
      tabs: [
        { index: 0, key: "all", label: "My Assignments" },
        { index: 1, key: "active", label: "Active" },
        { index: 2, key: "completed", label: "Completed" },
      ],
      activeTab: 1, // Default to Active tab (index 1)
    };
  },
  mounted() {
    console.log("departmentId", this.departmentId);
    // Load clients for filter
    this.loadClients();
    // Load data (works for both "All" and specific department)
    this.getAssignments();
  },
  methods: {
    async getAssignments() {
      this.loading = true;
      try {
        const params = {
          status: this.tabs[this.activeTab].key,
        };

        // Only include department_id if it's provided (not null/undefined)
        if (this.departmentId && this.departmentId !== "null") {
          params.department_id = this.departmentId;
        }

        if (this.selectedClient && this.selectedClient.length > 0) {
          params.client_id = this.selectedClient;
        }

        if (this.searchQuery) {
          params.search = this.searchQuery;
        }

        const response = await axios.get("/assignments/get-assignments", { params });

        this.assignments = response.data.data;
        this.activeCount = response.data.active_count || 0;
        this.overdueCount = response.data.overdue_count || 0;
        this.completedCount = response.data.completed_count || 0;
      } catch (error) {
        console.error("Error loading assignments:", error);
        this.$toast?.error("Failed to load assignments");
      } finally {
        this.loading = false;
      }
    },
    async loadClients() {
      try {
        const response = await axios.get("/clients");
        this.clients = response.data;
      } catch (error) {
        console.error("Error loading clients:", error);
      }
    },
    debouncedSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.getAssignments();
      }, 500);
    },
    goToEdit(item) {
      window.location.href = `/assignments/${item.id}/edit`;
    },
    createAssignment() {
      const url = `/assignments/create`;
      window.location.href = url;
    },
    formatDate(date) {
      if (!date) return "N/A";
      const d = new Date(date);
      const options = { year: "numeric", month: "short", day: "numeric" };
      return d.toLocaleDateString("en-US", options);
    },
    formatStatus(status) {
      const statusMap = {
        pending: "Pending",
        "in-progress": "In Progress",
        completed: "Completed",
        "on-hold": "On Hold",
      };
      return statusMap[status] || status;
    },
    getStatusColor(status) {
      const colors = {
        pending: "grey",
        "in-progress": "blue",
        completed: "green",
        "on-hold": "orange",
      };
      return colors[status] || "grey";
    },
    getDaysRemainingClass(days) {
      if (days === null) return "";
      if (days < 0) return "red--text font-weight-bold";
      if (days <= 3) return "orange--text";
      return "green--text";
    },
  },
};
</script>
