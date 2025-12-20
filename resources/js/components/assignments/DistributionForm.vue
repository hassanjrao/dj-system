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

    <!-- Deliverables -->
    <v-divider class="my-4"></v-divider>
    <v-subheader>Select All Deliverables Needed</v-subheader>
    <v-checkbox
      v-for="deliverable in availableDeliverables"
      :key="deliverable.id"
      v-model="localData.deliverable_ids"
      :value="deliverable.id"
      :label="deliverable.name"
    ></v-checkbox>

    <!-- Notes for Distribution -->
    <v-textarea
      v-model="localData.notes_for_distribution"
      label="Notes For Distribution"
      rows="3"
    ></v-textarea>

    <!-- Reference Links for Distribution -->
    <v-textarea
      v-model="localData.reference_links_distribution"
      label="Reference Links For Distribution (one per line)"
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
          display_name: `${a.song_name || a.assignment_name} (${
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
    if (this.isChild && this.parentData) {
      this.populateFromParent();
    }
  },
  methods: {
    populateFromParent() {
      if (this.parentData.music_type_id) {
        this.localData.music_type_id = this.parentData.music_type_id;
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
        this.localData.music_type_id = parent.music_type_id;
        this.preSelectDeliverables();
      }
      this.updateModel();
    },
    preSelectDeliverables() {
      if (!this.localData.music_type_id) return;

      axios
        .get(`/api/deliverables/pre-select`, {
          params: {
            department: this.departmentSlug,
            music_type_id: this.localData.music_type_id,
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
    modelValue: {
      handler(newVal) {
        this.localData = { ...newVal, deliverable_ids: newVal.deliverable_ids || [] };
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
