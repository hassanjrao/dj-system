<template>
  <v-app>
    <v-form ref="form" v-model="valid">
      <v-card>
        <v-card-title>
          {{ isEdit ? "Edit Assignment" : "Create Assignment" }}
        </v-card-title>
        <v-card-text>
          <!-- Department Selection -->
          <v-select
            v-model="formData.department_id"
            :items="departments"
            item-text="name"
            item-value="id"
            label="Department *"
            :rules="[(v) => !!v || 'Department is required']"
            @change="onDepartmentChange"
            required
          ></v-select>

          <!-- Client Selection -->
          <v-select
            v-model="formData.client_id"
            :items="clients"
            item-text="name"
            item-value="id"
            label="Client *"
            :rules="[(v) => !!v || 'Client is required']"
            required
          ></v-select>

          <!-- Assigned To -->
          <v-select
            v-model="formData.assigned_to_id"
            :items="availableUsers"
            item-text="name"
            item-value="id"
            label="Assignment Assigned To *"
            :rules="[(v) => !!v || 'Assigned user is required']"
            required
          ></v-select>

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
          <v-textarea
            v-model="formData.notes_for_team"
            label="Notes For Team"
            rows="3"
          ></v-textarea>

          <v-textarea
            v-model="formData.reference_links"
            label="Reference Links (one per line)"
            rows="3"
          ></v-textarea>

          <v-textarea
            v-if="isAdmin"
            v-model="formData.notes_for_admin"
            label="Notes For Me (Admin Only)"
            rows="3"
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="cancel">Cancel</v-btn>
          <v-btn
            color="primary"
            :disabled="!valid || loading"
            :loading="loading"
            @click="submit"
            >Save</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-form>
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
      valid: false,
      loading: false,
      formData: { ...this.modelValue },
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
  },
  watch: {
    modelValue: {
      handler(newVal) {
        this.formData = { ...newVal };
      },
      deep: true,
    },
  },
};
</script>
