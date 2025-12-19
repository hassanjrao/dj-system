<template>
  <div>
    <!-- Standalone vs Child Toggle -->
    <v-radio-group v-model="isStandalone" row @change="onStandaloneChange">
      <v-radio label="Standalone Assignment" :value="true"></v-radio>
      <v-radio label="Child Assignment" :value="false"></v-radio>
    </v-radio-group>

    <!-- Song Selection (if standalone) -->
    <v-select
      v-if="isStandalone"
      v-model="localData.linked_song_assignment_id"
      :items="availableSongs"
      item-text="song_name"
      item-value="id"
      label="What Song *"
      :rules="[(v) => !!v || 'Song selection is required']"
      required
      @change="onSongSelected"
    ></v-select>

    <!-- Song Name (auto-populated) -->
    <v-text-field
      v-model="localData.song_name"
      label="Song Name"
      readonly
      :value="linkedSong ? linkedSong.song_name : ''"
    ></v-text-field>

    <!-- Release Date (auto-populated) -->
    <v-text-field
      v-model="localData.release_date"
      label="Release Date"
      readonly
      :value="linkedSong ? linkedSong.release_date : ''"
    ></v-text-field>

    <!-- Completion Date (auto-calculated, editable) -->
    <v-text-field
      v-model="localData.completion_date"
      label="Completion Date *"
      type="date"
      :rules="[(v) => !!v || 'Completion date is required']"
      required
      hint="Auto-calculated based on song type and release date"
      persistent-hint
    ></v-text-field>
    <v-btn small outlined color="primary" @click="calculateCompletionDate"
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
    ></v-checkbox>

    <!-- Notes for Mastering -->
    <v-textarea
      v-model="localData.notes_for_mastering"
      label="Notes For Mastering"
      rows="3"
    ></v-textarea>

    <!-- Reference Links for Mastering -->
    <v-textarea
      v-model="localData.reference_links_mastering"
      label="Reference Links For Mastering (one per line)"
      rows="3"
    ></v-textarea>
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
    return {
      localData: {
        ...this.modelValue,
        deliverable_ids: this.modelValue.deliverable_ids || [],
      },
      isStandalone: !this.isChild && !this.parentData,
      linkedSong: null,
      availableDeliverables: [],
    };
  },
  mounted() {
    this.loadDeliverables();
    if (this.isChild && this.parentData) {
      this.populateFromParent();
    }
  },
  methods: {
    populateFromParent() {
      // Auto-populate from parent Music Creation assignment
      if (this.parentData.song_name) {
        this.localData.song_name = this.parentData.song_name;
      }
      if (this.parentData.release_date) {
        this.localData.release_date = this.parentData.release_date;
      }
      if (this.parentData.music_type_id) {
        this.localData.music_type_id = this.parentData.music_type_id;
      }

      // Auto-calculate completion date
      this.calculateCompletionDate();

      // Pre-select deliverables based on song type
      this.preSelectDeliverables();
    },
    onStandaloneChange() {
      if (!this.isStandalone) {
        // If switching to child, populate from parent
        this.populateFromParent();
      } else {
        // Clear parent-related data
        this.localData.song_name = null;
        this.localData.release_date = null;
      }
      this.updateModel();
    },
    onSongSelected() {
      // Find the selected song
      this.linkedSong = this.availableSongs.find(
        (s) => s.id === this.localData.linked_song_assignment_id
      );
      if (this.linkedSong) {
        this.localData.song_name = this.linkedSong.song_name;
        this.localData.release_date = this.linkedSong.release_date;
        this.localData.music_type_id = this.linkedSong.music_type_id;
        this.calculateCompletionDate();
        this.preSelectDeliverables();
      }
      this.updateModel();
    },
    calculateCompletionDate() {
      if (!this.localData.release_date || !this.localData.music_type_id) return;

      // Get days before release for this music type
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
    preSelectDeliverables() {
      if (!this.localData.music_type_id) return;

      // Get default deliverables for music mastering based on music type
      axios
        .get(`/api/deliverables/pre-select`, {
          params: {
            department: "music_mastering",
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
          params: { department: "music_mastering" },
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
