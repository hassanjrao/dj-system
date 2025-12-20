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

    <!-- Notes for Videographer -->
    <v-textarea
      v-model="localData.notes_for_videographer"
      label="Notes For Videographer"
      rows="3"
    ></v-textarea>

    <!-- Reference Links for Videographer -->
    <v-textarea
      v-model="localData.reference_links_videographer"
      label="Reference Links For Videographer (one per line)"
      rows="3"
    ></v-textarea>

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
  name: "VideoFilmingForm",
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
  },
  data() {
    return {
      localData: { ...this.modelValue },
      isStandalone: !this.isChild && !this.parentData,
      selectedParentDepartment: null,
      parentDepartments: [],
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
          display_name: `${a.song_name || a.assignment_name} (${
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
        { id: "video_editing", name: "Video Editing" },
        { id: "distribution_video", name: "Distribution - Video" },
      ];
    },
  },
  mounted() {
    this.loadParentDepartments();
    if (this.isChild && this.parentData) {
      this.populateFromParent();
    }
  },
  methods: {
    populateFromParent() {
      if (this.parentData.release_date) {
        this.localData.release_date = this.parentData.release_date;
      }
      if (this.parentData.music_type_id) {
        this.localData.music_type_id = this.parentData.music_type_id;
      }
      if (this.localData.release_timing === "pre-release") {
        this.calculateCompletionDate();
      }
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
        this.localData.release_date = parent.release_date;
        this.localData.music_type_id = parent.music_type_id;
        if (this.localData.release_timing === "pre-release") {
          this.calculateCompletionDate();
        }
      }
      this.updateModel();
    },
    onReleaseTimingChange() {
      if (
        this.localData.release_timing === "pre-release" &&
        this.localData.music_type_id &&
        this.localData.release_date
      ) {
        this.calculateCompletionDate();
      }
      this.updateModel();
    },
    calculateCompletionDate() {
      if (!this.localData.release_date || !this.localData.music_type_id) return;

      axios
        .get(`/api/music-types/${this.localData.music_type_id}/completion-days`)
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
    modelValue: {
      handler(newVal) {
        this.localData = { ...newVal };
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
