<template>
  <div>
    <!-- Standalone vs Child Toggle -->
    <v-radio-group
      v-model="isStandalone"
      row
      @change="onStandaloneChange"
      :disabled="isViewOnly"
    >
      <v-radio label="Standalone Assignment" :value="true"></v-radio>
      <v-radio label="Child Assignment" :value="false"></v-radio>
    </v-radio-group>

    <!-- Assignment Name (if standalone) -->
    <v-text-field
      v-if="isStandalone"
      v-model="localData.assignment_name"
      label="Assignment Name *"
      :rules="[(v) => !!v || 'Assignment name is required']"
      :disabled="isViewOnly"
      required
    ></v-text-field>

    <!-- Parent Assignment Selection (if child) -->
    <template v-if="!isStandalone">
      <v-autocomplete
        v-model="selectedParentDepartment"
        :items="parentDepartments"
        item-text="name"
        item-value="id"
        label="Select Parent Department"
        :disabled="isViewOnly"
        chips
        small-chips
        @change="onParentDepartmentChange"
      ></v-autocomplete>

      <v-autocomplete
        v-if="selectedParentDepartment"
        v-model="localData.parent_assignment_id"
        :items="filteredParentAssignments"
        item-text="display_name"
        item-value="id"
        label="Select Parent Assignment *"
        :rules="[(v) => !!v || 'Parent assignment is required']"
        :disabled="isViewOnly"
        chips
        small-chips
        required
        @change="onParentAssignmentSelected"
      ></v-autocomplete>
    </template>

    <!-- Deliverables -->
    <v-divider class="my-4"></v-divider>
    <v-subheader>Select All Deliverables Needed</v-subheader>
    <v-checkbox
      v-for="deliverable in availableDeliverables"
      :key="deliverable.id"
      v-model="localData.deliverable_ids"
      :value="deliverable.id"
      :label="deliverable.name"
      :disabled="isViewOnly"
    ></v-checkbox>

    <!-- Link Child Assignments (if this is a parent) -->
    <v-divider v-if="!isChild" class="my-4"></v-divider>
    <v-subheader v-if="!isChild">Link Child Assignments (Optional)</v-subheader>
    <v-autocomplete
      v-if="!isChild"
      v-model="localData.child_assignment_types"
      :items="availableChildDepartments"
      item-text="name"
      item-value="id"
      label="Select departments for child assignments"
      :disabled="isViewOnly"
      multiple
      chips
      small-chips
    ></v-autocomplete>
  </div>
</template>

<script>
export default {
  name: "DistributionForm",
  props: {
    modelValue: {
      type: Object,
      default: () => ({}),
    },
    lookupData: {
      type: Object,
      default: () => ({}),
    },
    isChild: {
      type: Boolean,
      default: false,
    },
    parentData: {
      type: Object,
      default: () => null,
    },
    availableAssignments: {
      type: Array,
      default: () => [],
    },
    distributionType: {
      type: String,
      default: "video", // video, graphic, music
    },
    assignmentData: {
      type: Object,
      default: () => null,
    },
    isViewOnly: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      localData: {
        ...this.modelValue,
        deliverable_ids: this.modelValue.deliverable_ids || [],
      },
      isStandalone: !this.isChild && !this.parentData,
      selectedParentDepartment: null,
      parentDepartments: [],
      availableDeliverables: [],
    };
  },
  computed: {
    filteredParentAssignments() {
      if (!this.selectedParentDepartment) return [];
      return this.availableAssignments
        .filter((a) => a.department_id === this.selectedParentDepartment)
        .map((a) => ({
          ...a,
          display_name: `${(a.song && a.song.name) || a.assignment_name} (${
            a.department?.name || ""
          })`,
        }));
    },
    availableChildDepartments() {
      return [{ id: "marketing", name: "Marketing" }];
    },
    departmentSlug() {
      return `distribution_${this.distributionType}`;
    },
  },
  mounted() {
    this.loadDeliverables();
    this.loadParentDepartments();
    // Populate from assignmentData (edit mode) or modelValue (create mode)
    this.populateFromAssignmentData();
    if (this.isChild && this.parentData) {
      this.populateFromParent();
    }
  },
  methods: {
    populateFromAssignmentData() {
      // Primary source: assignmentData (for edit mode)
      // Fallback: modelValue (for create mode or if assignmentData not available)
      const dataSource = this.assignmentData || this.modelValue;

      if (dataSource) {
        // Populate assignment_name
        if (dataSource.assignment_name) {
          this.localData.assignment_name = dataSource.assignment_name;
          // Determine if standalone based on whether parent_assignment_id exists
          this.isStandalone = !dataSource.parent_assignment_id;
        }

        // Populate parent_assignment_id
        if (dataSource.parent_assignment_id) {
          this.localData.parent_assignment_id = dataSource.parent_assignment_id;
          this.isStandalone = false;
          // Find parent department
          const parent = this.availableAssignments.find(
            (a) => a.id === dataSource.parent_assignment_id
          );
          if (parent) {
            this.selectedParentDepartment = parent.department_id;
          }
        }

        // Populate deliverable_ids
        if (dataSource.deliverables && Array.isArray(dataSource.deliverables)) {
          this.localData.deliverable_ids = dataSource.deliverables.map((d) =>
            typeof d === "object" ? d.id : d
          );
        } else if (
          dataSource.deliverable_ids &&
          Array.isArray(dataSource.deliverable_ids)
        ) {
          this.localData.deliverable_ids = dataSource.deliverable_ids;
        }
      }
    },
    populateFromParent() {
      this.preSelectDeliverables();
    },
    onStandaloneChange() {
      if (!this.isStandalone && this.parentData) {
        this.populateFromParent();
      }
      this.updateModel();
    },
    onParentDepartmentChange() {
      this.localData.parent_assignment_id = null;
      this.updateModel();
    },
    onParentAssignmentSelected() {
      const parent = this.availableAssignments.find(
        (a) => a.id === this.localData.parent_assignment_id
      );
      if (parent) {
        if (parent.song && parent.song.release_date) {
          this.localData.release_date = parent.song.release_date;
        } else if (parent.release_date) {
          this.localData.release_date = parent.release_date;
        }
        this.preSelectDeliverables();
      }
      this.updateModel();
    },
    preSelectDeliverables() {
      // Get music_type_id from parent's song
      let musicTypeId = null;
      if (this.isChild && this.parentData && this.parentData.song) {
        musicTypeId = this.parentData.song.music_type_id;
      } else if (this.localData.parent_assignment_id) {
        const parent = this.availableAssignments.find(
          (a) => a.id === this.localData.parent_assignment_id
        );
        if (parent && parent.song) {
          musicTypeId = parent.song.music_type_id;
        }
      }

      if (!musicTypeId) return;

      axios
        .get(`/deliverables/pre-select`, {
          params: {
            department_id: this.findDeptId(this.departmentName),
            music_type_id: musicTypeId,
          },
        })
        .then((response) => {
          this.localData.deliverable_ids = response.data.map((d) => d.id);
          this.updateModel();
        })
        .catch((error) => {
          console.error("Error pre-selecting deliverables:", error);
        });
    },
    loadDeliverables() {
      axios
        .get("/api/deliverables", {
          params: { department: this.departmentSlug },
        })
        .then((response) => {
          this.availableDeliverables = response.data;
        })
        .catch((error) => {
          console.error("Error loading deliverables:", error);
        });
    },
    loadParentDepartments() {
      axios
        .get("/api/departments", {
          params: { can_be_parent: true },
        })
        .then((response) => {
          this.parentDepartments = response.data;
        })
        .catch((error) => {
          console.error("Error loading parent departments:", error);
        });
    },
    updateModel() {
      this.$emit("update:modelValue", this.localData);
    },
  },
  watch: {
    assignmentData: {
      handler(newVal) {
        // When assignmentData changes (e.g., loaded asynchronously), populate form data
        if (newVal) {
          this.populateFromAssignmentData();
        }
      },
      deep: true,
      immediate: true,
    },
    modelValue: {
      handler(newVal) {
        this.localData = { ...newVal, deliverable_ids: newVal.deliverable_ids || [] };
        // If modelValue changes and assignmentData not available, populate from modelValue
        if (newVal && !this.assignmentData) {
          this.populateFromAssignmentData();
        }
      },
      deep: true,
    },
    localData: {
      handler() {
        this.updateModel();
      },
      deep: true,
    },
  },
};
</script>
