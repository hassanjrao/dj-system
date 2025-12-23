<template>
  <div>
    <!-- WHAT SONG - Only show if NOT a child assignment -->
    <v-autocomplete
      v-if="!isChild"
      v-model="selectedSongId"
      :items="availableSongs"
      item-text="name"
      item-value="id"
      label="What Song *"
      :rules="[(v) => !!v || 'Song selection is required']"
      chips
      small-chips
      required
      @change="onSongSelected"
    ></v-autocomplete>

    <!-- Song Name (auto-populated, readonly) -->
    <v-text-field
      v-model="displaySongName"
      label="Song Name"
      readonly
      :value="displaySongName || ''"
    ></v-text-field>

    <!-- Release Date (auto-populated, readonly) -->
    <v-text-field
      v-model="displayReleaseDate"
      label="Release Date"
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
    <v-divider class="my-4"></v-divider>
    <v-subheader>PLEASE SELECT ALL DELIVERABLES NEEDED</v-subheader>
    <v-checkbox
      v-for="deliverable in availableDeliverables"
      :key="deliverable.id"
      v-model="localData.deliverables"
      :value="deliverable.id"
      :label="deliverable.name"
    ></v-checkbox>
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
  },
  data() {
    // Convert deliverables from relationship objects to array of IDs if needed
    let deliverables = [];
    if (this.modelValue.deliverables) {
      if (Array.isArray(this.modelValue.deliverables)) {
        deliverables = this.modelValue.deliverables.map((d) =>
          typeof d === "object" && d.id ? d.id : d
        );
      } else {
        deliverables = [];
      }
    }

    return {
      localData: {
        ...this.modelValue,
        deliverables: deliverables,
      },
      selectedSongId: this.modelValue.song_id || null,
      availableDeliverables: [],
      selectedSong: null,
    };
  },
  computed: {
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
      if (this.isChild && this.parentData && this.parentData.song) {
        return this.parentData.song.release_date;
      }
      if (this.selectedSong) {
        return this.selectedSong.release_date;
      }
      if (this.localData.song && this.localData.song.release_date) {
        return this.localData.song.release_date;
      }
      return "";
    },
  },
  mounted() {
    this.loadDeliverables();
    if (this.isChild && this.parentData) {
      this.populateFromParent();
    } else if (this.localData.song_id && this.availableSongs.length > 0) {
      // If editing and song_id exists, find the song
      this.selectedSong = this.availableSongs.find(
        (s) => s.id === this.localData.song_id
      );
      if (this.selectedSong) {
        this.calculateCompletionDate();
      }
    }
  },
  methods: {
    populateFromParent() {
      // Auto-populate song_id from parent Music Creation assignment
      if (this.parentData.song_id) {
        this.localData.song_id = this.parentData.song_id;
      }

      // Auto-calculate completion date
      this.calculateCompletionDate();

      // Pre-select deliverables based on song type (can be manually changed)
      this.preSelectDeliverables();

      this.updateModel();
    },
    onSongSelected() {
      // Find the selected song and set song_id
      this.selectedSong = this.availableSongs.find((s) => s.id === this.selectedSongId);
      if (this.selectedSong) {
        this.localData.song_id = this.selectedSong.id;
        this.calculateCompletionDate();
        this.preSelectDeliverables();
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
      const musicMasteringDeptId = this.findDeptId("Music Mastering");
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
    preSelectDeliverables() {
      let song = null;
      if (this.isChild && this.parentData && this.parentData.song) {
        song = this.parentData.song;
      } else if (this.selectedSong) {
        song = this.selectedSong;
      } else if (this.localData.song) {
        song = this.localData.song;
      }

      if (!song || !song.music_type_id) return;

      // Get default deliverables for music mastering based on music type
      // This will pre-select but user can still manually change
      const musicMasteringDeptId = this.findDeptId("Music Mastering");
      if (!musicMasteringDeptId) return;

      axios
        .get(`/deliverables/pre-select`, {
          params: {
            department_id: musicMasteringDeptId,
            music_type_id: song.music_type_id,
          },
        })
        .then((response) => {
          // Only set if deliverables not already selected (preserve user selections)
          if (!this.localData.deliverables || this.localData.deliverables.length === 0) {
            this.localData.deliverables = response.data.map((d) => d.id);
            this.updateModel();
          }
        })
        .catch((error) => {
          console.error("Error pre-selecting deliverables:", error);
        });
    },
    findDeptId(name) {
      // Try to find department ID from lookupData
      if (this.lookupData && this.lookupData.departments) {
        const dept = this.lookupData.departments.find((d) => d.name === name);
        return dept ? dept.id : null;
      }
      // Try to find from formData if available
      if (this.localData && this.localData.department_id) {
        // If we're in Music Mastering form, the department_id should be Music Mastering
        return this.localData.department_id;
      }
      return null;
    },
    loadDeliverables() {
      const musicMasteringDeptId = this.findDeptId("Music Mastering");
      if (!musicMasteringDeptId) {
        // Try to get from formData
        if (this.localData.department_id) {
          this.loadDeliverablesByDeptId(this.localData.department_id);
        }
        return;
      }

      this.loadDeliverablesByDeptId(musicMasteringDeptId);
    },
    loadDeliverablesByDeptId(departmentId) {
      axios
        .get("/lookup/deliverables", {
          params: { department_id: departmentId },
        })
        .then((response) => {
          this.availableDeliverables = response.data;
        })
        .catch((error) => {
          console.error("Error loading deliverables:", error);
        });
    },
    updateModel() {
      this.$emit("update:modelValue", this.localData);
    },
  },
  watch: {
    modelValue: {
      handler(newVal) {
        // Convert deliverables from relationship objects to array of IDs if needed
        let deliverables = [];
        if (newVal.deliverables) {
          if (Array.isArray(newVal.deliverables)) {
            deliverables = newVal.deliverables.map((d) =>
              typeof d === "object" && d.id ? d.id : d
            );
          }
        }
        this.localData = { ...newVal, deliverables: deliverables };
        if (newVal.song_id) {
          this.selectedSongId = newVal.song_id;
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
