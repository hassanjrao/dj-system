<template>
  <div>
    <!-- WHAT SONG - Disabled for child assignments, enabled for standalone -->
    <v-autocomplete
      v-model="selectedSongId"
      :items="songsList"
      item-text="name"
      item-value="id"
      label="What Song *"
      :rules="[(v) => !!v || 'Song selection is required']"
      :disabled="isChild"
      chips
      small-chips
      required
      @change="onSongSelected"
    ></v-autocomplete>

    <!-- Release Date (auto-populated, readonly) -->
    <v-text-field
      v-model="displayReleaseDate"
      label="Release Date"
      type="date"
      readonly
      :value="displayReleaseDate || ''"
    ></v-text-field>

    <!-- Completion Date (auto-calculated, editable) -->
    <v-row>
      <v-col cols="9">
        <v-text-field
          v-model="localData.completion_date"
          label="Completion Date *"
          type="date"
          :rules="[(v) => !!v || 'Completion date is required']"
          required
          hint="Auto-calculated based on song type and release date"
          persistent-hint
        ></v-text-field>
      </v-col>
      <v-col cols="3" class="d-flex align-center">
        <v-btn small outlined color="primary" @click="calculateCompletionDate"
          >UPDATE</v-btn
        >
      </v-col>
    </v-row>

    <!-- Deliverables -->
    <v-autocomplete
      v-model="localData.deliverables"
      :items="deliverables"
      item-text="name"
      item-value="id"
      label="Please Select All Deliverables Needed *"
      :rules="[(v) => (v && v.length > 0) || 'At least one deliverable is required']"
      multiple
      chips
      small-chips
      required
      @change="updateModel"
    ></v-autocomplete>
  </div>
</template>

<script>
export default {
  name: "MusicMasteringForm",
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
    availableSongs: {
      type: Array,
      default: () => [],
    },
    selectedDepartmentId: {
      type: Number,
      default: null,
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
        deliverables: [],
      },
      selectedSongId: this.modelValue.song_id || null,
      selectedSong: null,
      deliverables: [],
    };
  },
  computed: {
    songsList() {
      // For child assignments, show the parent's song or the loaded song
      if (this.isChild) {
        if (this.parentData && this.parentData.song) {
          return [this.parentData.song];
        } else if (this.localData.song) {
          return [this.localData.song];
        }
        return [];
      }
      // For standalone assignments, show all available songs
      return this.availableSongs;
    },
    displaySongName() {
      if (this.isChild && this.parentData && this.parentData.song) {
        return this.parentData.song.name;
      }
      if (this.selectedSong) {
        return this.selectedSong.name;
      }
      if (this.localData.song && this.localData.song.name) {
        return this.localData.song.name;
      }
      return "";
    },
    displayReleaseDate() {
      let date = null;
      if (this.isChild && this.parentData && this.parentData.song) {
        date = this.parentData.song.release_date;
      } else if (this.selectedSong) {
        date = this.selectedSong.release_date;
      } else if (this.localData.song && this.localData.song.release_date) {
        date = this.localData.song.release_date;
      }

      // Format date to yyyy-MM-dd for HTML date input
      if (date) {
        return date.split("T")[0]; // Remove time portion
      }
      return "";
    },
  },
  mounted() {
    this.getDeliverables();
    // Populate from assignmentData (edit mode) or modelValue (create mode)
    this.populateFromAssignmentData();

    // For child assignments
    if (this.isChild) {
      if (this.parentData && this.parentData.song) {
        this.populateFromParent();
      }
    }
  },
  methods: {
    populateFromAssignmentData() {
      // Primary source: assignmentData (for edit mode)
      // Fallback: modelValue (for create mode or if assignmentData not available)
      const dataSource = this.assignmentData || this.modelValue;

      console.log("dataSource", dataSource);

      if (dataSource) {
        // Populate song_id
        if (dataSource.song_id) {
          this.localData.song_id = dataSource.song_id;
          this.selectedSongId = dataSource.song_id;

          // Find the song in available songs if not a child
          if (!this.isChild && this.availableSongs.length > 0) {
            this.selectedSong = this.availableSongs.find(
              (s) => s.id === dataSource.song_id
            );
          }
        }

        // Populate completion_date
        if (dataSource.completion_date) {
          // Format for date input (YYYY-MM-DD)
          const date = new Date(dataSource.completion_date);
          if (!isNaN(date.getTime())) {
            this.localData.completion_date = date.toISOString().split('T')[0];
          }
        }

        // Populate deliverables
        if (dataSource.deliverables && Array.isArray(dataSource.deliverables)) {
          this.localData.deliverables = dataSource.deliverables.map(d =>
            typeof d === 'object' ? d.id : d
          );
        } else if (dataSource.deliverable_ids && Array.isArray(dataSource.deliverable_ids)) {
          this.localData.deliverables = dataSource.deliverable_ids;
        }

        // Calculate completion date if song exists
        if (this.localData.song_id && (this.localData.song || this.selectedSong)) {
          this.$nextTick(() => {
            this.calculateCompletionDate();
          });
        }
      }
    },
    populateFromParent() {
      // Auto-populate song_id from parent Music Creation assignment
      if (this.parentData.song_id) {
        this.localData.song_id = this.parentData.song_id;
        this.selectedSongId = this.parentData.song_id;
      }
      // Auto-calculate completion date
      this.calculateCompletionDate();
      this.updateModel();
    },
    onSongSelected() {
      // Find the selected song and set song_id
      this.selectedSong = this.songsList.find((s) => s.id === this.selectedSongId);
      if (this.selectedSong) {
        this.localData.song_id = this.selectedSong.id;
        this.calculateCompletionDate();
      }
      this.updateModel();
    },
    calculateCompletionDate() {
      let song = null;
      if (this.isChild && this.parentData && this.parentData.song) {
        song = this.parentData.song;
      } else if (this.selectedSong) {
        song = this.selectedSong;
      } else if (this.localData.song) {
        song = this.localData.song;
      }

      if (!song || !song.release_date || !song.music_type_id) return;

      // Calculate completion date based on music type and release date
      // Get music mastering department ID
      const musicMasteringDeptId = this.selectedDepartmentId;
      if (!musicMasteringDeptId) {
        // Fallback: calculate with default 7 days
        const releaseDate = new Date(song.release_date);
        releaseDate.setDate(releaseDate.getDate() - 7);
        this.localData.completion_date = releaseDate.toISOString().split("T")[0];
        this.updateModel();
        return;
      }

      axios
        .get(`/music-types/${song.music_type_id}/completion-days`, {
          params: {
            department_id: musicMasteringDeptId,
          },
        })
        .then((response) => {
          const daysBeforeRelease = response.data.days_before_release || 7; // Default to 7 days
          const releaseDate = new Date(song.release_date);
          releaseDate.setDate(releaseDate.getDate() - daysBeforeRelease);
          this.localData.completion_date = releaseDate.toISOString().split("T")[0];
          this.updateModel();
        })
        .catch((error) => {
          console.error("Error calculating completion date:", error);
          // Fallback: calculate with default 7 days
          const releaseDate = new Date(song.release_date);
          releaseDate.setDate(releaseDate.getDate() - 7);
          this.localData.completion_date = releaseDate.toISOString().split("T")[0];
          this.updateModel();
        });
    },
    updateModel() {
      this.$emit("update:modelValue", this.localData);
    },
    getDeliverables() {
      axios
        .get(`/lookup/deliverables`, {
          params: {
            department_id: this.selectedDepartmentId,
          },
        })
        .then((response) => {
          console.log("deliverables", response.data);
          this.deliverables  = response.data;
        })
        .catch((error) => {
          console.error("Error getting deliverables:", error);
        });
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
        this.localData = { ...newVal, deliverables: newVal.deliverables || [] };
        // If modelValue changes and assignmentData not available, populate from modelValue
        if (newVal && !this.assignmentData) {
          this.populateFromAssignmentData();
        }
      },
      deep: true,
    },
    availableSongs: {
      handler() {
        // When songs become available, try to find the selected song
        if (this.localData.song_id && this.availableSongs.length > 0 && !this.isChild) {
          this.selectedSong = this.availableSongs.find(
            (s) => s.id === this.localData.song_id
          );
          if (this.selectedSong) {
            this.calculateCompletionDate();
          }
        }
      },
    },
  },
};
</script>
