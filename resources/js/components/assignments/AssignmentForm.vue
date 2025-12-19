<template>
  <v-app>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>{{ isEdit ? "Edit Assignment" : "Create Assignment" }}</span>
        <div>
          <v-btn text small @click="cancel" class="mr-2">Cancel</v-btn>
          <v-btn
            v-if="currentStep === 1"
            color="primary"
            small
            :disabled="!step1Valid"
            @click="goToNextStep"
            class="mr-2"
            >Next</v-btn
          >
          <v-btn
            v-if="currentStep === 2"
            text
            small
            @click="goToPreviousStep"
            class="mr-2"
            >Back</v-btn
          >
          <v-btn
            v-if="currentStep === 2"
            color="primary"
            small
            :disabled="!valid || loading"
            :loading="loading"
            @click="submit"
            >Save</v-btn
          >
        </div>
      </v-card-title>
      <v-card-text>
        <!-- Step 1: Department and Client Selection -->
        <v-form ref="step1Form" v-model="step1Valid" v-if="currentStep === 1">
          <v-row>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="formData.department_id"
                :items="departments"
                item-text="name"
                item-value="id"
                label="Department *"
                :rules="[(v) => !!v || 'Department is required']"
                @change="onDepartmentChange"
                :search-input.sync="departmentSearch"
                required
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="selectedClient"
                :items="localClients"
                item-text="name"
                item-value="id"
                label="Client *"
                :rules="[(v) => !!v || 'Client is required']"
                @change="onClientChange"
                :search-input.sync="clientSearch"
                required
              >
                <template v-slot:append-item>
                  <v-list-item @click="showClientDialog = true">
                    <v-list-item-content>
                      <v-list-item-title>+ Create New Client</v-list-item-title>
                    </v-list-item-content>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>
          </v-row>
        </v-form>

        <!-- Step 2: Department-specific form (only shown after clicking Next) -->
        <v-form ref="form" v-model="valid" v-if="currentStep === 2">
          <v-divider class="my-4"></v-divider>

          <!-- Assigned To (filtered by department) -->
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.assigned_to_id"
                :items="filteredUsers"
                item-text="name"
                item-value="id"
                label="Assignment Assigned To *"
                :rules="[(v) => !!v || 'Assigned user is required']"
                :loading="loadingUsers"
                required
              ></v-select>
            </v-col>
          </v-row>

          <!-- Department-specific forms -->
          <MusicCreationForm
            v-if="formData.department_id === musicCreationDeptId"
            v-model="formData"
            :is-child="isChild"
            :parent-data="parentData"
            :lookup-data="lookupData"
            @update:modelValue="updateFormData"
          />

          <MusicMasteringForm
            v-else-if="formData.department_id === musicMasteringDeptId"
            v-model="formData"
            :is-child="isChild"
            :parent-data="parentData"
            :lookup-data="lookupData"
            :available-songs="availableSongs"
            @update:modelValue="updateFormData"
          />

          <GraphicDesignForm
            v-else-if="formData.department_id === graphicDesignDeptId"
            v-model="formData"
            :is-child="isChild"
            :parent-data="parentData"
            :lookup-data="lookupData"
            :available-assignments="availableAssignments"
            @update:modelValue="updateFormData"
          />

          <VideoFilmingForm
            v-else-if="formData.department_id === videoFilmingDeptId"
            v-model="formData"
            :is-child="isChild"
            :parent-data="parentData"
            :lookup-data="lookupData"
            :available-assignments="availableAssignments"
            @update:modelValue="updateFormData"
          />

          <VideoEditingForm
            v-else-if="formData.department_id === videoEditingDeptId"
            v-model="formData"
            :is-child="isChild"
            :parent-data="parentData"
            :lookup-data="lookupData"
            :available-assignments="availableAssignments"
            @update:modelValue="updateFormData"
          />

          <DistributionForm
            v-else-if="isDistributionDept(formData.department_id)"
            v-model="formData"
            :is-child="isChild"
            :parent-data="parentData"
            :lookup-data="lookupData"
            :available-assignments="availableAssignments"
            :distribution-type="getDistributionType(formData.department_id)"
            @update:modelValue="updateFormData"
          />

          <!-- Common fields -->
          <v-divider class="my-4"></v-divider>
          <v-row>
            <v-col cols="12">
              <v-textarea
                v-model="formData.notes_for_team"
                label="Notes For Team"
                rows="3"
              ></v-textarea>
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12">
              <v-textarea
                v-model="formData.reference_links"
                label="Reference Links (one per line)"
                rows="3"
              ></v-textarea>
            </v-col>
          </v-row>
          <v-row v-if="isAdmin">
            <v-col cols="12">
              <v-textarea
                v-model="formData.notes_for_admin"
                label="Notes For Me (Admin Only)"
                rows="3"
              ></v-textarea>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
    </v-card>

    <!-- Client Creation Dialog -->
    <v-dialog v-model="showClientDialog" max-width="500">
      <v-card>
        <v-card-title>Create New Client</v-card-title>
        <v-card-text>
          <v-form ref="clientForm" v-model="clientFormValid">
            <v-text-field
              v-model="newClient.name"
              label="Client Name *"
              :rules="[(v) => !!v || 'Client name is required']"
              required
            ></v-text-field>
            <v-text-field
              v-model="newClient.email"
              label="Email"
              type="email"
            ></v-text-field>
            <v-text-field v-model="newClient.phone" label="Phone"></v-text-field>
            <v-textarea v-model="newClient.notes" label="Notes" rows="3"></v-textarea>
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn text small @click="closeClientDialog" class="mr-2">Cancel</v-btn>
          <v-btn color="primary" small :disabled="!clientFormValid" @click="createClient"
            >Create</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script>
import MusicCreationForm from "./MusicCreationForm.vue";
import MusicMasteringForm from "./MusicMasteringForm.vue";
import GraphicDesignForm from "./GraphicDesignForm.vue";
import VideoFilmingForm from "./VideoFilmingForm.vue";
import VideoEditingForm from "./VideoEditingForm.vue";
import DistributionForm from "./DistributionForm.vue";

export default {
  name: "AssignmentForm",
  components: {
    MusicCreationForm,
    MusicMasteringForm,
    GraphicDesignForm,
    VideoFilmingForm,
    VideoEditingForm,
    DistributionForm,
  },
  props: {
    modelValue: {
      type: Object,
      default: () => ({}),
    },
    departments: {
      type: Array,
      default: () => [],
    },
    clients: {
      type: Array,
      default: () => [],
    },
    availableUsers: {
      type: Array,
      default: () => [],
    },
    lookupData: {
      type: Object,
      default: () => ({}),
    },
    isEdit: {
      type: Boolean,
      default: false,
    },
    isChild: {
      type: Boolean,
      default: false,
    },
    parentData: {
      type: Object,
      default: () => null,
    },
    availableSongs: {
      type: Array,
      default: () => [],
    },
    availableAssignments: {
      type: Array,
      default: () => [],
    },
    isAdmin: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      currentStep: 1,
      valid: false,
      step1Valid: false,
      loading: false,
      loadingUsers: false,
      formData: { ...this.modelValue },
      selectedClient: null,
      filteredUsers: [],
      localClients: [...this.clients],
      showClientDialog: false,
      clientFormValid: false,
      departmentSearch: null,
      clientSearch: null,
      newClient: {
        name: "",
        email: "",
        phone: "",
        notes: "",
      },
      musicCreationDeptId: null,
      musicMasteringDeptId: null,
      graphicDesignDeptId: null,
      videoFilmingDeptId: null,
      videoEditingDeptId: null,
      distributionDeptIds: [],
    };
  },
  mounted() {
    console.log("mountedddd", this.departments);
    this.initializeDepartmentIds();
    this.initializeClient();
    this.loadUsersForDepartment();
  },
  methods: {
    initializeDepartmentIds() {
      // Find department IDs by slug or name
      this.musicCreationDeptId = this.findDeptId("Music Creation");
      this.musicMasteringDeptId = this.findDeptId("Music Mastering");
      this.graphicDesignDeptId = this.findDeptId("Graphic Design");
      this.videoFilmingDeptId = this.findDeptId("Video Filming");
      this.videoEditingDeptId = this.findDeptId("Video Editing");
      this.distributionDeptIds = [
        this.findDeptId("Distribution - Video"),
        this.findDeptId("Distribution - Graphic"),
        this.findDeptId("Distribution - Music"),
      ].filter((id) => id !== null);
    },
    initializeClient() {
      // Set selected client if editing and client_id exists
      if (this.formData.client_id) {
        const client = this.localClients.find((c) => c.id === this.formData.client_id);
        if (client) {
          this.selectedClient = client.id;
        }
      }
    },
    findDeptId(name) {
      const dept = this.departments.find((d) => d.name === name);
      return dept ? dept.id : null;
    },
    isDistributionDept(deptId) {
      return this.distributionDeptIds.includes(deptId);
    },
    getDistributionType(deptId) {
      if (deptId === this.findDeptId("Distribution - Video")) return "video";
      if (deptId === this.findDeptId("Distribution - Graphic")) return "graphic";
      if (deptId === this.findDeptId("Distribution - Music")) return "music";
      return null;
    },
    onDepartmentChange() {
      // Reset department-specific fields when department changes
      this.$emit("department-changed", this.formData.department_id);
      // Load users for the selected department
      this.loadUsersForDepartment();
    },
    onClientChange(clientId) {
      // Handle client selection
      if (clientId) {
        this.formData.client_id = clientId;
        this.selectedClient = clientId;
      }
    },
    loadUsersForDepartment() {
      if (!this.formData.department_id) {
        this.filteredUsers = [];
        return;
      }

      this.loadingUsers = true;
      axios
        .get(`/api/users/available-for-assignment`, {
          params: { department_id: this.formData.department_id },
        })
        .then((response) => {
          this.filteredUsers = response.data;
          this.loadingUsers = false;
        })
        .catch((error) => {
          console.error("Error loading users:", error);
          this.filteredUsers = [];
          this.loadingUsers = false;
        });
    },
    createClient() {
      if (!this.$refs.clientForm.validate()) {
        return;
      }
      axios
        .post("/clients", this.newClient)
        .then((response) => {
          // Add new client to local clients list
          this.localClients.push(response.data);
          // Set as selected client
          this.selectedClient = response.data.id;
          this.formData.client_id = response.data.id;
          // Close dialog and reset form
          this.closeClientDialog();
        })
        .catch((error) => {
          console.error("Error creating client:", error);
          let errorMessage = "Error creating client. Please try again.";
          if (error.response && error.response.data && error.response.data.message) {
            errorMessage = error.response.data.message;
          } else if (
            error.response &&
            error.response.data &&
            error.response.data.errors
          ) {
            const errors = Object.values(error.response.data.errors).flat();
            errorMessage = errors.join("\n");
          }
          alert(errorMessage);
        });
    },
    closeClientDialog() {
      this.showClientDialog = false;
      this.newClient = {
        name: "",
        email: "",
        phone: "",
        notes: "",
      };
      if (this.$refs.clientForm) {
        this.$refs.clientForm.resetValidation();
      }
    },
    updateFormData(newData) {
      this.formData = { ...this.formData, ...newData };
      this.$emit("update:modelValue", this.formData);
    },
    submit() {
      if (this.$refs.form.validate()) {
        this.loading = true;

        const url = this.isEdit
          ? `/api/assignments/${this.formData.id}`
          : "/api/assignments";
        const method = this.isEdit ? "put" : "post";

        axios[method](url, this.formData)
          .then((response) => {
            this.loading = false;
            window.location.href = "/assignments";
          })
          .catch((error) => {
            this.loading = false;
            console.error("Error saving assignment:", error);

            let errorMessage = "Error saving assignment. Please try again.";
            if (error.response && error.response.data && error.response.data.message) {
              errorMessage = error.response.data.message;
            } else if (
              error.response &&
              error.response.data &&
              error.response.data.errors
            ) {
              const errors = Object.values(error.response.data.errors).flat();
              errorMessage = errors.join("\n");
            }

            alert(errorMessage);
          });
      }
    },
    cancel() {
      window.location.href = "/assignments";
    },
    goToNextStep() {
      if (this.$refs.step1Form && this.$refs.step1Form.validate()) {
        this.currentStep = 2;
        // Load users for the selected department when moving to step 2
        this.loadUsersForDepartment();
      }
    },
    goToPreviousStep() {
      this.currentStep = 1;
    },
  },
  watch: {
    modelValue: {
      handler(newVal) {
        this.formData = { ...newVal };
        this.initializeClient();
      },
      deep: true,
    },
    clients: {
      handler(newClients) {
        this.localClients = [...newClients];
        this.initializeClient();
      },
      deep: true,
    },
    "formData.department_id": {
      handler() {
        this.loadUsersForDepartment();
      },
    },
  },
};
</script>
