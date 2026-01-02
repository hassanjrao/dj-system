<template>
  <div>
    <!-- Standalone vs Child Toggle -->
    <v-radio-group v-model="isStandalone" row @change="onStandaloneChange">
      <v-radio label="Standalone Assignment" :value="true"></v-radio>
      <v-radio label="Child Assignment" :value="false"></v-radio>
    </v-radio-group>

    <!-- Assignment Name (if standalone) -->
    <v-text-field
      v-if="isStandalone"
      v-model="localData.assignment_name"
      label="Assignment Name *"
      :rules="[(v) => !!v || 'Assignment name is required']"
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
        chips
        small-chips
        required
        @change="onParentAssignmentSelected"
      ></v-autocomplete>
    </template>

    <!-- Release Timing -->
    <v-autocomplete
      v-model="localData.release_timing"
      :items="releaseTimings"
      item-text="name"
      item-value="value"
      label="Release Timing *"
      :rules="[(v) => !!v || 'Release timing is required']"
      chips
      small-chips
      required
      @change="onReleaseTimingChange"
    ></v-autocomplete>

    <!-- Edit Type -->
    <v-autocomplete
      v-model="localData.edit_type_id"
      :items="lookupData.edit_types || []"
      item-text="name"
      item-value="id"
      label="Edit Type *"
      :rules="[(v) => !!v || 'Edit type is required']"
      chips
      small-chips
      required
    ></v-autocomplete>

    <!-- Footage To Use -->
    <v-autocomplete
      v-model="localData.footage_type_id"
      :items="lookupData.footage_types || []"
      item-text="name"
      item-value="id"
      label="Footage To Use *"
      :rules="[(v) => !!v || 'Footage type is required']"
      chips
      small-chips
      required
    ></v-autocomplete>

    <!-- Completion Date -->
    <v-text-field
      v-model="localData.completion_date"
      label="Completion Date *"
      type="date"
      :rules="[(v) => !!v || 'Completion date is required']"
      required
      :hint="releaseTimingHint"
      persistent-hint
    ></v-text-field>
    <v-btn
      v-if="localData.release_timing === 'pre-release'"
      small
      outlined
      color="primary"
      @click="calculateCompletionDate"
      >Auto-Calculate</v-btn
    >

    <!-- Deliverables -->
    <v-divider class="my-4"></v-divider>
    <v-subheader>Select All Deliverables Needed</v-subheader>
    <v-checkbox
      v-for="deliverable in availableDeliverables"
      :key="deliverable.id"
      v-model="localData.deliverable_ids"
      :value="deliverable.id"
      :label="deliverable.name"
    >
      <template v-slot:append>
        <v-checkbox
          v-if="deliverable.needs_distribution"
          v-model="deliverable.needs_distribution_checked"
          label="Needs Distribution"
          hide-details
          dense
        ></v-checkbox>
      </template>
    </v-checkbox>

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
      multiple
      chips
      small-chips
    ></v-autocomplete>
  </div>
</template>

<script>
export default {
  name: "VideoEditingForm",
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
    assignmentData: {
      type: Object,
      default: () => null,
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
      releaseTimings: [
        { name: "Pre-Release", value: "pre-release" },
        { name: "Post-Release", value: "post-release" },
        { name: "Other", value: "other" },
      ],
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
    releaseTimingHint() {
      if (this.localData.release_timing === "pre-release") {
        return "Auto-calculated based on Song Type and Release Date if linked to Music Creation";
      }
      return "Select a completion date";
    },
    availableChildDepartments() {
      return [
        { id: "distribution_video", name: "Distribution - Video" },
        { id: "marketing", name: "Marketing" },
      ];
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

        // Populate release_timing
        if (dataSource.release_timing) {
          this.localData.release_timing = dataSource.release_timing;
        }

        // Populate edit_type_id
        if (dataSource.edit_type_id) {
          this.localData.edit_type_id = dataSource.edit_type_id;
        }

        // Populate footage_type_id
        if (dataSource.footage_type_id) {
          this.localData.footage_type_id = dataSource.footage_type_id;
        }

        // Populate completion_date
        if (dataSource.completion_date) {
          // Format for date input (YYYY-MM-DD)
          const date = new Date(dataSource.completion_date);
          if (!isNaN(date.getTime())) {
            this.localData.completion_date = date.toISOString().split('T')[0];
          }
        }

        // Populate deliverable_ids
        if (dataSource.deliverables && Array.isArray(dataSource.deliverables)) {
          this.localData.deliverable_ids = dataSource.deliverables.map(d => 
            typeof d === 'object' ? d.id : d
          );
        } else if (dataSource.deliverable_ids && Array.isArray(dataSource.deliverable_ids)) {
          this.localData.deliverable_ids = dataSource.deliverable_ids;
        }
      }
    },
    populateFromParent() {
      if (this.parentData.song && this.parentData.song.release_date) {
        this.localData.release_date = this.parentData.song.release_date;
      } else if (this.parentData.release_date) {
        this.localData.release_date = this.parentData.release_date;
      }
      if (this.localData.release_timing === "pre-release") {
        this.calculateCompletionDate();
      }
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
        if (this.localData.release_timing === "pre-release") {
          this.calculateCompletionDate();
        }
        this.preSelectDeliverables();
      }
      this.updateModel();
    },
    onReleaseTimingChange() {
      if (
        this.localData.release_timing === "pre-release" &&
        this.localData.release_date
      ) {
        this.calculateCompletionDate();
      }
      this.updateModel();
    },
    calculateCompletionDate() {
      if (!this.localData.release_date) return;

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
        .get(`/music-types/${musicTypeId}/completion-days`)
        .then((response) => {
          const daysBeforeRelease = response.data.days_before_release;
          if (daysBeforeRelease) {
            const releaseDate = new Date(this.localData.release_date);
            releaseDate.setDate(releaseDate.getDate() - daysBeforeRelease);
            this.localData.completion_date = releaseDate.toISOString().split("T")[0];
            this.updateModel();
          }
        })
        .catch((error) => {
          console.error("Error calculating completion date:", error);
        });
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
            department_id: this.findDeptId("Video Editing"),
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
          params: { department: "video_editing" },
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
