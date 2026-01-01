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
    console.log("MusicMasteringForm mounted", {
      isChild: this.isChild,
      parentData: this.parentData,
      localData: this.localData,
      hasSong: !!this.localData.song,
    });

    console.log("modelValue", this.modelValue);

    // For child assignments
    if (this.isChild) {
      if (this.parentData && this.parentData.song) {
        this.populateFromParent();
      } else if (this.localData.song_id) {
        // Child assignment already has song_id loaded from backend
        this.selectedSongId = this.localData.song_id;
        if (this.localData.song) {
          this.calculateCompletionDate();
        }
      }
    } else {
      // For standalone assignments
      if (this.localData.song_id && this.availableSongs.length > 0) {
        // If editing and song_id exists, find the song
        this.selectedSong = this.availableSongs.find(
          (s) => s.id === this.localData.song_id
        );
        if (this.selectedSong) {
          this.calculateCompletionDate();
        }
      }
    }
  },
  methods: {
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
      console.log("updateModel", this.localData);
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
};
</script>
