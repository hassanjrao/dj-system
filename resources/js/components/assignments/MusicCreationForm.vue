<template>
  <div>
    <v-row>
      <!-- Left Column -->
      <v-col cols="12" md="6">
        <!-- Assigned To is handled in parent AssignmentForm -->

        <!-- Song Name -->
        <v-text-field
          v-model="songData.name"
          label="Song Name *"
          :rules="[(v) => !!v || 'Song name is required']"
          :disabled="isViewOnly"
          required
        ></v-text-field>

        <!-- Version Name -->
        <v-text-field
          v-model="songData.version"
          label="Version Name"
          :disabled="isViewOnly"
        ></v-text-field>

        <!-- Album Selector -->
        <v-autocomplete
          v-model="songData.album_id"
          :items="albums"
          item-text="name"
          item-value="id"
          label="Album"
          :disabled="isViewOnly"
          clearable
          chips
          small-chips
        >
          <template v-slot:append-item v-if="!isViewOnly">
            <v-list-item @click="showAlbumDialog = true">
              <v-list-item-content>
                <v-list-item-title>+ Create New Album</v-list-item-title>
              </v-list-item-content>
            </v-list-item>
          </template>
        </v-autocomplete>

        <!-- Artists -->
        <v-autocomplete
          v-model="songData.artists"
          :items="artistSuggestions"
          item-text="name"
          item-value="id"
          label="Artist(s) *"
          :disabled="isViewOnly"
          multiple
          chips
          small-chips
          :rules="[(v) => (v && v.length > 0) || 'At least one artist is required']"
          required
        >
          <template v-slot:append-item v-if="!isViewOnly">
            <v-list-item @click="showArtistDialog = true">
              <v-list-item-content>
                <v-list-item-title>+ Create New Artist</v-list-item-title>
              </v-list-item-content>
            </v-list-item>
          </template>
        </v-autocomplete>

        <!-- Music Type -->
        <v-autocomplete
          v-model="songData.music_type_id"
          :items="lookupData.music_types || []"
          item-text="name"
          item-value="id"
          label="Music Type *"
          :rules="[(v) => !!v || 'Music type is required']"
          :disabled="isViewOnly"
          chips
          small-chips
          required
        ></v-autocomplete>
      </v-col>

      <!-- Right Column -->
      <v-col cols="12" md="6">
        <!-- BPM -->
        <v-text-field
          v-model.number="songData.bpm"
          label="BPM"
          type="number"
          :disabled="isViewOnly"
          :rules="[(v) => !v || (v >= 0 && v <= 999) || 'BPM must be between 0 and 999']"
          maxlength="3"
        ></v-text-field>

        <!-- Music Key -->
        <v-autocomplete
          v-model="songData.music_key_id"
          :items="lookupData.music_keys || []"
          item-text="name"
          item-value="id"
          label="Key"
          :disabled="isViewOnly"
          clearable
          chips
          small-chips
        ></v-autocomplete>

        <!-- Genre -->
        <v-autocomplete
          v-model="songData.music_genre_id"
          :items="lookupData.music_genres || []"
          item-text="name"
          item-value="id"
          label="Genre"
          :disabled="isViewOnly"
          clearable
          chips
          small-chips
        ></v-autocomplete>

        <!-- Release Date -->
        <v-text-field
          v-if="canSetReleaseDate"
          v-model="songData.release_date"
          label="Release Date & Time (PST) *"
          type="datetime-local"
          :rules="[(v) => !!v || 'Release date is required']"
          :disabled="isViewOnly"
          required
        ></v-text-field>

        <!-- Completion Date -->
        <v-row>
          <v-col cols="9">
            <v-text-field
              v-model="songData.completion_date"
              label="Completion Date"
              type="date"
              :disabled="isViewOnly"
            ></v-text-field>
          </v-col>
          <v-col cols="3" class="d-flex align-center" v-if="!isViewOnly">
            <v-btn small outlined color="primary" @click="calculateCompletionDate"
              >UPDATE</v-btn
            >
          </v-col>
        </v-row>

        <!-- Status -->
        <v-autocomplete
          v-model="localData.music_creation_status_id"
          :items="lookupData.music_creation_statuses || []"
          item-text="name"
          item-value="id"
          label="Creation Status"
          :disabled="isViewOnly"
          clearable
          chips
          small-chips
        ></v-autocomplete>
      </v-col>
    </v-row>

    <!-- Album Creation Dialog -->
    <v-dialog v-model="showAlbumDialog" max-width="500" v-if="!isViewOnly">
      <v-card>
        <v-card-title>Create New Album</v-card-title>
        <v-card-text>
          <v-form ref="albumForm" v-model="albumFormValid">
            <v-text-field
              v-model="newAlbumName"
              label="Album Name *"
              :rules="[(v) => !!v || 'Album name is required']"
              required
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn text small @click="closeAlbumDialog" class="mr-2">Cancel</v-btn>
          <v-btn color="primary" small :disabled="!albumFormValid" @click="createAlbum"
            >Create</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Artist Creation Dialog -->
    <v-dialog v-model="showArtistDialog" max-width="500" v-if="!isViewOnly">
      <v-card>
        <v-card-title>Create New Artist</v-card-title>
        <v-card-text>
          <v-form ref="artistForm" v-model="artistFormValid">
            <v-text-field
              v-model="newArtistName"
              label="Artist Name *"
              :rules="[(v) => !!v || 'Artist name is required']"
              required
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn text small @click="closeArtistDialog" class="mr-2">Cancel</v-btn>
          <v-btn color="primary" small :disabled="!artistFormValid" @click="createArtist"
            >Create</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
export default {
  name: "MusicCreationForm",
  props: {
    modelValue: {
      type: Object,
      default: () => ({}),
    },
    lookupData: {
      type: Object,
      default: () => ({}),
    },
    departments: {
      type: Array,
      default: () => [],
    },
    isChild: {
      type: Boolean,
      default: false,
    },
    parentData: {
      type: Object,
      default: () => null,
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
      localData: { ...this.modelValue },
      songData: {
        name: "",
        version: "",
        album_id: null,
        music_type_id: null,
        music_genre_id: null,
        bpm: null,
        music_key_id: null,
        release_date: "",
        completion_date: "",
        artists: [],
      },
      artistSuggestions: [],
      showAlbumDialog: false,
      albumFormValid: false,
      newAlbumName: "",
      albums: [],
      showArtistDialog: false,
      artistFormValid: false,
      newArtistName: "",
      availableChildDepartments: [],
      loadingChildDepartments: false,
    };
  },
  computed: {
    canSetReleaseDate() {
      // Users cannot set release date for MUSIC CREATION assignments
      // Only super-admin and admin can set release date
      let can = !this.$store.getters["auth/hasRole"]("user");

      console.log("can", can);
      return can;
    },
  },
  mounted() {
    this.loadAlbums();
    this.loadArtistSuggestions();
    this.loadChildDepartments();
    if (this.parentData) {
      this.populateFromParent();
    }
    // Populate song data from assignmentData (edit mode) or modelValue (create mode)
    this.populateFromAssignmentData();
  },
  methods: {
    populateFromAssignmentData() {
      // Primary source: assignmentData (for edit mode)
      // Fallback: modelValue.song (for create mode or if assignmentData not available)
      const songSource = this.assignmentData?.song || this.modelValue?.song;

      if (songSource) {
        // Format release_date for datetime-local input (YYYY-MM-DDTHH:mm)
        let releaseDate = "";
        if (songSource.release_date) {
          const date = new Date(songSource.release_date);
          if (!isNaN(date.getTime())) {
            // Format as YYYY-MM-DDTHH:mm for datetime-local
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");
            const hours = String(date.getHours()).padStart(2, "0");
            const minutes = String(date.getMinutes()).padStart(2, "0");
            releaseDate = `${year}-${month}-${day}T${hours}:${minutes}`;
          }
        }

        // Format completion_date for date input (YYYY-MM-DD)
        let completionDate = "";
        if (songSource.completion_date) {
          const date = new Date(songSource.completion_date);
          if (!isNaN(date.getTime())) {
            completionDate = date.toISOString().split("T")[0];
          }
        }

        // Get artist IDs - check multiple sources
        let artists = [];
        if (this.assignmentData?.song_artists) {
          artists = this.assignmentData.song_artists;
        } else if (songSource.artists && Array.isArray(songSource.artists)) {
          artists = songSource.artists.map((a) => (typeof a === "object" ? a.id : a));
        } else if (this.modelValue?.song_artists) {
          artists = this.modelValue.song_artists;
        }

        this.songData = {
          name: songSource.name || "",
          version: songSource.version || "",
          album_id: songSource.album_id || null,
          music_type_id: songSource.music_type_id || null,
          music_genre_id: songSource.music_genre_id || null,
          bpm: songSource.bpm || null,
          music_key_id: songSource.music_key_id || null,
          release_date: releaseDate,
          completion_date: completionDate,
          artists: artists,
        };

        // Also update localData with music_creation_status_id if available
        if (this.assignmentData?.music_creation_status_id) {
          this.localData.music_creation_status_id = this.assignmentData.music_creation_status_id;
        } else if (this.modelValue?.music_creation_status_id) {
          this.localData.music_creation_status_id = this.modelValue.music_creation_status_id;
        }
      }
    },
    populateFromParent() {
      // Auto-populate song data from parent if this is a child assignment
      if (this.parentData.song) {
        this.songData = {
          name: this.parentData.song.name || "",
          version: this.parentData.song.version || "",
          album_id: this.parentData.song.album_id || null,
          music_type_id: this.parentData.song.music_type_id || null,
          music_genre_id: this.parentData.song.music_genre_id || null,
          bpm: this.parentData.song.bpm || null,
          music_key_id: this.parentData.song.music_key_id || null,
          release_date: this.parentData.song.release_date || "",
          completion_date: this.parentData.song.completion_date || "",
          artists: this.parentData.song.artists
            ? this.parentData.song.artists.map((a) => a.id)
            : [],
        };
      }
    },
    onArtistsInput(artists) {
      // Deduplicate artists
      const uniqueArtists = [
        ...new Set(artists.map((a) => (typeof a === "string" ? a.trim() : a))),
      ];
      this.localData.artists = uniqueArtists.filter((a) => a);
      this.updateModel();
    },
    loadAlbums() {
      // Load albums from API
      axios
        .get("/albums")
        .then((response) => {
          this.albums = response.data;
        })
        .catch((error) => {
          console.error("Error loading albums:", error);
        });
    },
    loadArtistSuggestions() {
      // Load existing artists for autocomplete
      axios
        .get("/artists")
        .then((response) => {
          // Keep full artist objects with id and name
          this.artistSuggestions = response.data;
        })
        .catch((error) => {
          console.error("Error loading artists:", error);
        });
    },
    loadChildDepartments() {
      // Load child departments from backend based on parent department
      // Since this is Music Creation form, we know the parent is Music Creation
      this.loadingChildDepartments = true;
      axios
        .get("/lookup/child-departments", {
          params: {
            department_slug: "music-creation",
          },
        })
        .then((response) => {
          this.availableChildDepartments = response.data.map((dept) => ({
            id: dept.id,
            name: dept.name,
          }));
          this.loadingChildDepartments = false;
        })
        .catch((error) => {
          console.error("Error loading child departments:", error);
          this.loadingChildDepartments = false;
        });
    },
    createArtist() {
      if (!this.$refs.artistForm.validate()) return;

      axios
        .post("/artists", { name: this.newArtistName })
        .then((response) => {
          // Add new artist to suggestions (full object with id and name)
          this.artistSuggestions.push(response.data);
          // Add to selected artists (using ID)
          if (!this.songData.artists) {
            this.songData.artists = [];
          }
          this.songData.artists.push(response.data.id);
          this.closeArtistDialog();
          this.updateModel();
        })
        .catch((error) => {
          console.error("Error creating artist:", error);
          if (error.response && error.response.data && error.response.data.message) {
            alert(error.response.data.message);
          }
        });
    },
    closeArtistDialog() {
      this.showArtistDialog = false;
      this.newArtistName = "";
      if (this.$refs.artistForm) {
        this.$refs.artistForm.resetValidation();
      }
    },
    createAlbum() {
      if (!this.$refs.albumForm.validate()) return;

      axios
        .post("/albums", { name: this.newAlbumName })
        .then((response) => {
          this.albums.push(response.data);
          this.songData.album_id = response.data.id;
          this.closeAlbumDialog();
          this.updateModel();
        })
        .catch((error) => {
          console.error("Error creating album:", error);
        });
    },
    closeAlbumDialog() {
      this.showAlbumDialog = false;
      this.newAlbumName = "";
      if (this.$refs.albumForm) {
        this.$refs.albumForm.resetValidation();
      }
    },
    calculateCompletionDate() {
      // Calculate completion date based on song's music type and release date
      if (!this.songData.release_date || !this.songData.music_type_id) {
        return;
      }

      // Get department ID for Music Creation
      const musicCreationDept = this.departments.find((d) => d.slug === "music-creation");
      if (!musicCreationDept) return;

      axios
        .get(`/music-types/${this.songData.music_type_id}/completion-days`, {
          params: {
            department_id: musicCreationDept.id,
          },
        })
        .then((response) => {
          const daysBeforeRelease = response.data.days_before_release || 7;
          const releaseDate = new Date(this.songData.release_date);
          releaseDate.setDate(releaseDate.getDate() - daysBeforeRelease);
          this.songData.completion_date = releaseDate.toISOString().split("T")[0];
          this.updateModel();
        })
        .catch((error) => {
          console.error("Error calculating completion date:", error);
          // Fallback: calculate with default 7 days
          const releaseDate = new Date(this.songData.release_date);
          releaseDate.setDate(releaseDate.getDate() - 7);
          this.songData.completion_date = releaseDate.toISOString().split("T")[0];
          this.updateModel();
        });
    },
    updateModel() {
      // Always include song data in the payload (always creating new song)
      const payload = {
        ...this.localData,
        song_name: this.songData.name,
        song_version: this.songData.version,
        song_album_id: this.songData.album_id,
        song_music_type_id: this.songData.music_type_id,
        song_music_genre_id: this.songData.music_genre_id,
        song_bpm: this.songData.bpm,
        song_music_key_id: this.songData.music_key_id,
        song_release_date: this.songData.release_date,
        song_completion_date: this.songData.completion_date,
        song_artists: this.songData.artists || [],
      };

      console.log("payload", payload);
      this.$emit("update:modelValue", payload);
    },
  },
  watch: {
    assignmentData: {
      handler(newVal) {
        // When assignmentData changes (e.g., loaded asynchronously), populate song data
        if (newVal && newVal.song) {
          this.populateFromAssignmentData();
        }
      },
      deep: true,
      immediate: true,
    },
    modelValue: {
      handler(newVal) {
        this.localData = { ...newVal };
        // If modelValue.song changes and assignmentData not available, populate from modelValue
        if (newVal.song && !this.assignmentData) {
          this.populateFromAssignmentData();
        }
      },
      deep: true,
    },
    "modelValue.song": {
      handler(newSong) {
        // Watch specifically for song changes in modelValue
        if (newSong && !this.assignmentData) {
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
    songData: {
      handler() {
        this.updateModel();
      },
      deep: true,
    },
  },
};
</script>
