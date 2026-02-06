<template>
  <div>
    <!-- WHAT SONG - Only shown in CREATE mode for standalone assignments -->
    <v-autocomplete
      v-if="isCreateMode && !isChild"
      v-model="selectedSongId"
      :items="songsList"
      item-text="name"
      item-value="id"
      label="What Song *"
      :rules="[(v) => !!v || 'Song selection is required']"
      :disabled="isViewOnly"
      chips
      small-chips
      required
      @change="onSongSelected"
    ></v-autocomplete>

    <!-- Song Information Section (shown when a song is selected) -->
    <v-card v-if="currentSong" class="mb-4" outlined>
      <v-card-title class="text-subtitle-1">Song Information</v-card-title>
      <v-card-text>
        <v-row>
          <!-- Left Column -->
          <v-col cols="12" md="6">
            <v-text-field
              :value="currentSong.name || ''"
              label="Song Name"
              readonly
            ></v-text-field>

            <v-text-field
              :value="currentSong.version || ''"
              label="Version"
              readonly
            ></v-text-field>

            <v-text-field
              :value="getAlbumName(currentSong)"
              label="Album"
              readonly
            ></v-text-field>

            <v-text-field
              :value="getArtistNames(currentSong)"
              label="Artist(s)"
              readonly
            ></v-text-field>

            <v-text-field
              :value="getMusicTypeName(currentSong)"
              label="Music Type"
              readonly
            ></v-text-field>
          </v-col>

          <!-- Right Column -->
          <v-col cols="12" md="6">
            <v-text-field
              :value="currentSong.bpm || ''"
              label="BPM"
              readonly
            ></v-text-field>

            <v-text-field
              :value="getMusicKeyName(currentSong)"
              label="Key"
              readonly
            ></v-text-field>

            <v-text-field
              :value="getGenreName(currentSong)"
              label="Genre"
              readonly
            ></v-text-field>

            <v-text-field
              :value="displayReleaseDate"
              label="Release Date"
              readonly
            ></v-text-field>

            <!-- Due Date (editable) -->
            <v-row no-gutters align="center">
              <v-col :cols="isViewOnly ? 12 : 9">
                <v-text-field
                  v-model="localData.completion_date"
                  label="Due Date *"
                  type="date"
                  :rules="[(v) => !!v || 'Due date is required']"
                  :disabled="isViewOnly"
                  required
                  hint="Auto-calculated based on song type and release date"
                  persistent-hint
                ></v-text-field>
              </v-col>
              <v-col cols="3" class="d-flex align-center pl-2" v-if="!isViewOnly">
                <v-btn small outlined color="primary" @click="calculateCompletionDate"
                  >UPDATE</v-btn
                >
              </v-col>
            </v-row>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>


    <v-divider class="my-6" style="border-width: 2px; opacity: 0.5"></v-divider>
    <!-- Deliverables Section -->
    <v-card outlined :class="{ 'error-border': showDeliverableError }">
      <v-card-title class="text-subtitle-1 pb-0">
        Please Select All Deliverables Needed *
      </v-card-title>
      <v-card-text>
        <!-- Validation Error Message -->
        <v-alert
          v-if="showDeliverableError"
          type="error"
          dense
          text
          class="mb-3"
        >
          At least one deliverable is required
        </v-alert>

        <!-- Create Mode: Simple checkbox selection -->
        <div v-if="isCreateMode">
          <v-row class="mb-2">
            <v-col cols="12" class="font-weight-bold text-caption">
              DELIVERABLE
            </v-col>
          </v-row>
          <v-divider class="mb-2"></v-divider>
          <v-row
            v-for="deliverable in deliverables"
            :key="deliverable.id"
            class="mb-1 align-center"
          >
            <v-col cols="12">
              <v-checkbox
                :value="deliverable.id"
                v-model="localData.deliverables"
                :label="deliverable.name"
                :disabled="isViewOnly"
                hide-details
                dense
                @change="onDeliverableChange"
              ></v-checkbox>
            </v-col>
          </v-row>
        </div>

        <!-- Edit/View Mode: 4-column table with status dropdowns -->
        <div v-else>
          <!-- Table Header -->
          <v-row class="mb-2 grey lighten-4 py-2">
            <v-col cols="3" class="font-weight-bold text-caption">
              DELIVERABLE
            </v-col>
            <v-col cols="3" class="font-weight-bold text-caption">
              COMPLETED
            </v-col>
            <v-col cols="3" class="font-weight-bold text-caption">
              WAVE UPLOADED
            </v-col>
            <v-col cols="3" class="font-weight-bold text-caption">
              MP3 UPLOADED
            </v-col>
          </v-row>
          <v-divider class="mb-2"></v-divider>

          <!-- Deliverable Rows -->
          <v-row
            v-for="deliverable in visibleDeliverables"
            :key="deliverable.id"
            class="mb-1 align-center"
            style="min-height: 48px"
          >
            <!-- Column 1: Checkbox + Name -->
            <v-col cols="3" class="d-flex align-center">
              <v-checkbox
                :value="deliverable.id"
                v-model="localData.deliverables"
                :label="deliverable.name"
                :disabled="!canManageDeliverables || isViewOnly"
                hide-details
                dense
                @change="onDeliverableToggle(deliverable)"
              ></v-checkbox>
            </v-col>

            <!-- Column 2: COMPLETED dropdown -->
            <v-col cols="3">
              <v-autocomplete
                v-if="isDeliverableSelected(deliverable.id)"
                v-model="deliverableStatuses[deliverable.id].completion_status_id"
                :items="completionStatusOptions"
                item-text="name"
                item-value="id"
                :disabled="!canUpdateStatus"
                dense
                hide-details
                @change="onStatusChange(deliverable.id, 'completion_status_id')"
              ></v-autocomplete>
              <span v-else class="text-caption grey--text">-</span>
            </v-col>

            <!-- Column 3: WAVE UPLOADED dropdown -->
            <v-col cols="3">
              <v-autocomplete
                v-if="isDeliverableSelected(deliverable.id)"
                v-model="deliverableStatuses[deliverable.id].wave_upload_status_id"
                :items="waveUploadStatusOptions"
                item-text="name"
                item-value="id"
                :disabled="!canUpdateStatus || !isCompletionDone(deliverable.id)"
                dense
                hide-details
                @change="onStatusChange(deliverable.id, 'wave_upload_status_id')"
              ></v-autocomplete>
              <span v-else class="text-caption grey--text">-</span>
            </v-col>

            <!-- Column 4: MP3 UPLOADED dropdown -->
            <v-col cols="3">
              <v-autocomplete
                v-if="isDeliverableSelected(deliverable.id)"
                v-model="deliverableStatuses[deliverable.id].mp3_upload_status_id"
                :items="mp3UploadStatusOptions"
                item-text="name"
                item-value="id"
                :disabled="!canUpdateStatus || !isCompletionDone(deliverable.id)"
                dense
                hide-details
                @change="onStatusChange(deliverable.id, 'mp3_upload_status_id')"
              ></v-autocomplete>
              <span v-else class="text-caption grey--text">-</span>
            </v-col>
          </v-row>

          <!-- Empty state for non-admin users -->
          <v-row v-if="visibleDeliverables.length === 0">
            <v-col cols="12" class="text-center grey--text">
              No deliverables selected
            </v-col>
          </v-row>
        </div>
      </v-card-text>
    </v-card>
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
    isViewOnly: {
      type: Boolean,
      default: false,
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
      // Status options from API
      statusOptions: {
        completion: [],
        wave_upload: [],
        mp3_upload: [],
      },
      // Track status for each deliverable
      deliverableStatuses: {},
      // Track pending status updates
      pendingStatusUpdates: {},
      // Validation state
      showDeliverableError: false,
    };
  },
  computed: {
    currentUser() {
      return this.$store.getters["auth/user"]?.user;
    },
    isAdmin() {
      return this.$store.getters["auth/isAdmin"];
    },
    isSuperAdmin() {
      return this.$store.getters["auth/isSuperAdmin"];
    },
    // Check if we're in create mode (no assignment ID yet)
    isCreateMode() {
      return !this.assignmentData?.id;
    },
    // Can manage deliverables (select/deselect): Admin, super-admin, or creator
    canManageDeliverables() {
      if (this.isAdmin || this.isSuperAdmin){
         return true;
      }
      if (!this.assignmentData) {
        return true;
      }
      console.log('canmanage',this.assignmentData.created_by.id, this.currentUser.id)
      return this.assignmentData.created_by.id === this.currentUser.id;
    },
    // Can update status dropdowns: Assigned user, admin, or super-admin
    canUpdateStatus() {
        console.log('canupdate',this.assignmentData.assigned_to_id, this.currentUser.id)
      if (this.isAdmin || this.isSuperAdmin) {
        return true;
      }
      if (!this.assignmentData) {
        return false;
      }
      if (this.assignmentData.assigned_to_id === this.currentUser.id) {
        console.log('canupdate true')
        return true;
      }
      return false;
    },
    // Visible deliverables based on permissions
    visibleDeliverables() {
      if (this.canManageDeliverables) {
        return this.deliverables; // Show all
      }
      // Non-admin assigned users only see checked items
      return this.deliverables.filter((d) =>
        this.localData.deliverables.includes(d.id)
      );
    },
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
    currentSong() {
      if (this.isChild && this.parentData && this.parentData.song) {
        return this.parentData.song;
      }
      if (this.selectedSong) {
        return this.selectedSong;
      }
      if (this.localData.song) {
        return this.localData.song;
      }
      return null;
    },
    displayReleaseDate() {
      if (!this.currentSong || !this.currentSong.release_date) return "";
      const date = this.currentSong.release_date;
      // Format date to yyyy-MM-dd for display
      return date.split("T")[0];
    },
    completionStatusOptions() {
      return this.statusOptions.completion || [];
    },
    waveUploadStatusOptions() {
      return this.statusOptions.wave_upload || [];
    },
    mp3UploadStatusOptions() {
      return this.statusOptions.mp3_upload || [];
    },
    // Get the "done" status ID for completion
    doneStatusId() {
      const doneStatus = this.completionStatusOptions.find(
        (s) => s.code === "done"
      );
      return doneStatus ? doneStatus.id : null;
    },
    // Get default pending status IDs
    pendingCompletionStatusId() {
      const pending = this.completionStatusOptions.find(
        (s) => s.code === "pending"
      );
      return pending ? pending.id : null;
    },
    pendingWaveUploadStatusId() {
      const pending = this.waveUploadStatusOptions.find(
        (s) => s.code === "pending"
      );
      return pending ? pending.id : null;
    },
    pendingMp3UploadStatusId() {
      const pending = this.mp3UploadStatusOptions.find(
        (s) => s.code === "pending"
      );
      return pending ? pending.id : null;
    },
  },
  mounted() {
    this.getDeliverables();
    this.getDeliverableStatuses();
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
    // Validation method - can be called by parent form
    validate() {
      const hasDeliverables = this.localData.deliverables && this.localData.deliverables.length > 0;
      this.showDeliverableError = !hasDeliverables;
      return hasDeliverables;
    },
    // Handle deliverable checkbox change
    onDeliverableChange() {
      // Clear error when user selects a deliverable
      if (this.localData.deliverables && this.localData.deliverables.length > 0) {
        this.showDeliverableError = false;
      }
      this.updateModel();
    },
    // Helper methods to get display names
    getAlbumName(song) {
      if (song.album && song.album.name) return song.album.name;
      if (song.album_id && this.lookupData.albums) {
        const album = this.lookupData.albums.find((a) => a.id === song.album_id);
        return album ? album.name : "";
      }
      return "";
    },
    getArtistNames(song) {
      if (song.artists && Array.isArray(song.artists)) {
        return song.artists.map((a) => a.name).join(", ");
      }
      return "";
    },
    getMusicTypeName(song) {
      if (song.music_type && song.music_type.name) return song.music_type.name;
      if (song.music_type_id && this.lookupData.music_types) {
        const type = this.lookupData.music_types.find(
          (t) => t.id === song.music_type_id
        );
        return type ? type.name : "";
      }
      return "";
    },
    getMusicKeyName(song) {
      if (song.music_key && (song.music_key.display_name || song.music_key.name))
        return song.music_key.display_name || song.music_key.name;
      if (song.music_key_id && this.lookupData.music_keys) {
        const key = this.lookupData.music_keys.find(
          (k) => k.id === song.music_key_id
        );
        return key ? (key.display_name || key.name) : "";
      }
      return "";
    },
    getGenreName(song) {
      if (song.music_genre && song.music_genre.name) return song.music_genre.name;
      if (song.music_genre_id && this.lookupData.music_genres) {
        const genre = this.lookupData.music_genres.find(
          (g) => g.id === song.music_genre_id
        );
        return genre ? genre.name : "";
      }
      return "";
    },
    // Check if a deliverable is selected
    isDeliverableSelected(deliverableId) {
      return this.localData.deliverables.includes(deliverableId);
    },
    // Check if completion status is "Done" for a deliverable
    isCompletionDone(deliverableId) {
      const status = this.deliverableStatuses[deliverableId];
      return status && status.completion_status_id === this.doneStatusId;
    },
    // Initialize status tracking for a deliverable
    initDeliverableStatus(deliverableId, existingData = null) {
      if (!this.deliverableStatuses[deliverableId]) {
        this.$set(this.deliverableStatuses, deliverableId, {
          completion_status_id:
            existingData?.completion_status_id || this.pendingCompletionStatusId,
          wave_upload_status_id:
            existingData?.wave_upload_status_id || this.pendingWaveUploadStatusId,
          mp3_upload_status_id:
            existingData?.mp3_upload_status_id || this.pendingMp3UploadStatusId,
        });
      }
    },
    // Handle deliverable toggle (checkbox)
    onDeliverableToggle(deliverable) {
      const isSelected = this.localData.deliverables.includes(deliverable.id);

      if (isSelected) {
        // Initialize status for newly selected deliverable
        this.initDeliverableStatus(deliverable.id);
      } else {
        // Clear status when deselected
        this.$delete(this.deliverableStatuses, deliverable.id);
      }

      this.updateModel();
    },
    // Handle status dropdown change
    onStatusChange(deliverableId, statusField) {
      // If completion status changed and is not "done", reset upload statuses to pending
      if (statusField === "completion_status_id") {
        const status = this.deliverableStatuses[deliverableId];
        if (status.completion_status_id !== this.doneStatusId) {
          status.wave_upload_status_id = this.pendingWaveUploadStatusId;
          status.mp3_upload_status_id = this.pendingMp3UploadStatusId;
        }
      }

      // If in edit mode, save status immediately
      if (!this.isCreateMode && this.assignmentData?.id) {
        this.saveDeliverableStatus(deliverableId);
      }
    },
    // Save deliverable status to API
    async saveDeliverableStatus(deliverableId) {
      const assignmentId = this.assignmentData?.id;
      if (!assignmentId) return;

      const status = this.deliverableStatuses[deliverableId];
      if (!status) return;

      try {
        const response = await axios.patch(
          `/assignments/${assignmentId}/deliverables/${deliverableId}/status`,
          {
            completion_status_id: status.completion_status_id,
            wave_upload_status_id: status.wave_upload_status_id,
            mp3_upload_status_id: status.mp3_upload_status_id,
          }
        );

        console.log("Status updated:", response.data);

        // Emit event to parent if assignment status changed
        if (response.data.assignment_status) {
          this.$emit("assignment-status-changed", response.data.assignment_status);
        }
      } catch (error) {
        console.error("Error updating deliverable status:", error);
        // Could show a toast notification here
      }
    },
    populateFromAssignmentData() {
      // Primary source: assignmentData (for edit mode)
      // Fallback: modelValue (for create mode or if assignmentData not available)
      const dataSource = this.assignmentData || this.modelValue;

      console.log("MusicMasteringForm dataSource", dataSource);

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

          // Also check if song object is directly available
          if (dataSource.song) {
            this.selectedSong = dataSource.song;
          }
        }

        // Populate completion_date
        if (dataSource.completion_date) {
          // Format for date input (YYYY-MM-DD)
          const date = new Date(dataSource.completion_date);
          if (!isNaN(date.getTime())) {
            this.localData.completion_date = date.toISOString().split("T")[0];
          }
        }

        // Populate deliverables with their statuses
        if (dataSource.deliverables && Array.isArray(dataSource.deliverables)) {
          this.localData.deliverables = dataSource.deliverables.map((d) =>
            typeof d === "object" ? d.id : d
          );

          // Populate status tracking from existing data
          dataSource.deliverables.forEach((d) => {
            if (typeof d === "object" && d.pivot) {
              this.initDeliverableStatus(d.id, {
                completion_status_id: d.pivot.completion_status_id,
                wave_upload_status_id: d.pivot.wave_upload_status_id,
                mp3_upload_status_id: d.pivot.mp3_upload_status_id,
              });
            } else {
              const deliverableId = typeof d === "object" ? d.id : d;
              this.initDeliverableStatus(deliverableId);
            }
          });
        } else if (
          dataSource.deliverable_ids &&
          Array.isArray(dataSource.deliverable_ids)
        ) {
          this.localData.deliverables = dataSource.deliverable_ids;
          dataSource.deliverable_ids.forEach((id) => {
            this.initDeliverableStatus(id);
          });
        }

        // Calculate completion date if song exists
        if (
          this.localData.song_id &&
          (this.localData.song || this.selectedSong)
        ) {
          this.$nextTick(() => {
            if (!this.localData.completion_date) {
              this.calculateCompletionDate();
            }
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
      const song = this.currentSong;
      if (!song || !song.release_date || !song.music_type_id) return;

      // Calculate completion date based on music type and release date
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
        .get(
          `/music-types/${song.music_type_id}/${musicMasteringDeptId}/completion-days`
        )
        .then((response) => {
          const daysBeforeRelease = response.data.days_before_release || 7;
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
      // Include deliverable statuses in the emitted data
      const dataToEmit = {
        ...this.localData,
        deliverable_statuses: this.deliverableStatuses,
      };
      this.$emit("update:modelValue", dataToEmit);
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
          this.deliverables = response.data;
        })
        .catch((error) => {
          console.error("Error getting deliverables:", error);
        });
    },
    getDeliverableStatuses() {
      axios
        .get(`/lookup/deliverable-statuses`)
        .then((response) => {
          console.log("deliverable statuses", response.data);
          this.statusOptions = response.data;

          // Re-initialize statuses with correct defaults now that we have the options
          this.localData.deliverables.forEach((id) => {
            if (!this.deliverableStatuses[id]) {
              this.initDeliverableStatus(id);
            }
          });
        })
        .catch((error) => {
          console.error("Error getting deliverable statuses:", error);
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
        if (
          this.localData.song_id &&
          this.availableSongs.length > 0 &&
          !this.isChild
        ) {
          this.selectedSong = this.availableSongs.find(
            (s) => s.id === this.localData.song_id
          );
          if (this.selectedSong && !this.localData.completion_date) {
            this.calculateCompletionDate();
          }
        }
      },
    },
    selectedDepartmentId: {
      handler(newVal) {
        // Reload deliverables when department changes
        if (newVal) {
          this.getDeliverables();
        }
      },
      immediate: true,
    },
  },
};
</script>

<style scoped>
.error-border {
  border-color: #ff5252 !important;
}
</style>
