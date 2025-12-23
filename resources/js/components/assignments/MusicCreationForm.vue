<template>
  <div>
    <v-row>
      <!-- Left Column -->
      <v-col cols="12" md="6">
        <!-- Assigned To is handled in parent AssignmentForm -->

        <!-- Music Type -->
        <v-autocomplete
          v-model="songData.music_type_id"
          :items="lookupData.music_types || []"
          item-text="name"
          item-value="id"
          label="Music Type *"
          :rules="[(v) => !!v || 'Music type is required']"
          chips
          small-chips
          required
        ></v-autocomplete>

        <!-- Song Name -->
        <v-text-field
          v-model="songData.name"
          label="Song Name *"
          :rules="[(v) => !!v || 'Song name is required']"
          required
        ></v-text-field>

        <!-- Version Name -->
        <v-text-field v-model="songData.version" label="Version Name"></v-text-field>

        <!-- Album Selector -->
        <v-autocomplete
          v-model="songData.album_id"
          :items="albums"
          item-text="name"
          item-value="id"
          label="Album"
          clearable
          chips
          small-chips
        >
          <template v-slot:append-item>
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
          multiple
          chips
          small-chips
          :rules="[(v) => (v && v.length > 0) || 'At least one artist is required']"
          required
        >
          <template v-slot:append-item>
            <v-list-item @click="showArtistDialog = true">
              <v-list-item-content>
                <v-list-item-title>+ Create New Artist</v-list-item-title>
              </v-list-item-content>
            </v-list-item>
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Right Column -->
      <v-col cols="12" md="6">
        <!-- BPM -->
        <v-text-field
          v-model.number="songData.bpm"
          label="BPM"
          type="number"
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
          clearable
          chips
          small-chips
        ></v-autocomplete>

        <!-- Release Date -->
        <v-text-field
          v-model="songData.release_date"
          label="Release Date & Time (PST) *"
          type="datetime-local"
          :rules="[(v) => !!v || 'Release date is required']"
          required
        ></v-text-field>

        <!-- Completion Date -->
        <v-row>
          <v-col cols="9">
            <v-text-field
              v-model="songData.completion_date"
              label="Completion Date"
              type="date"
            ></v-text-field>
          </v-col>
          <v-col cols="3" class="d-flex align-center">
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
          clearable
          chips
          small-chips
        ></v-autocomplete>
      </v-col>
    </v-row>

    <!-- Link Child Assignments -->
    <v-divider class="my-4"></v-divider>
    <v-subheader>PLEASE SELECT ALL ASSIGNMENTS THAT NEED TO BE LINKED</v-subheader>
    <v-row>
      <v-col cols="12">
        <v-autocomplete
          v-model="localData.child_departments"
          :items="availableChildDepartments"
          item-text="name"
          item-value="id"
          label="Select departments for child assignments"
          multiple
          chips
          small-chips
          :loading="loadingChildDepartments"
        ></v-autocomplete>
      </v-col>
    </v-row>

    <!-- Album Creation Dialog -->
    <v-dialog v-model="showAlbumDialog" max-width="500">
      <v-card>
        <v-card-title>Create New Album</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="newAlbumName"
            label="Album Name *"
            :rules="[(v) => !!v || 'Album name is required']"
            required
          ></v-text-field>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn text small @click="showAlbumDialog = false" class="mr-2">Cancel</v-btn>
          <v-btn color="primary" small @click="createAlbum">Create</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Artist Creation Dialog -->
    <v-dialog v-model="showArtistDialog" max-width="500">
      <v-card>
        <v-card-title>Create New Artist</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="newArtistName"
            label="Artist Name *"
            :rules="[(v) => !!v || 'Artist name is required']"
            required
          ></v-text-field>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn text small @click="closeArtistDialog" class="mr-2">Cancel</v-btn>
          <v-btn color="primary" small @click="createArtist">Create</v-btn>
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
      newAlbumName: "",
      albums: [],
      showArtistDialog: false,
      newArtistName: "",
      availableChildDepartments: [],
      loadingChildDepartments: false,
    };
  },
  mounted() {
    this.loadAlbums();
    this.loadArtistSuggestions();
    this.loadChildDepartments();
    if (this.parentData) {
      this.populateFromParent();
    }
    if (this.modelValue.song) {
      // If editing and song exists, populate song data
      this.songData = {
        name: this.modelValue.song.name || "",
        version: this.modelValue.song.version || "",
        album_id: this.modelValue.song.album_id || null,
        music_type_id: this.modelValue.song.music_type_id || null,
        music_genre_id: this.modelValue.song.music_genre_id || null,
        bpm: this.modelValue.song.bpm || null,
        music_key_id: this.modelValue.song.music_key_id || null,
        release_date: this.modelValue.song.release_date || "",
        completion_date: this.modelValue.song.completion_date || "",
        artists: this.modelValue.song.artists
          ? this.modelValue.song.artists.map((a) => a.id)
          : [],
      };
    }
  },
  methods: {
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
      if (!this.newArtistName) return;

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
          this.showArtistDialog = false;
          this.newArtistName = "";
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
    },
    createAlbum() {
      if (!this.newAlbumName) return;

      axios
        .post("/albums", { name: this.newAlbumName })
        .then((response) => {
          this.albums.push(response.data);
          this.songData.album_id = response.data.id;
          this.showAlbumDialog = false;
          this.newAlbumName = "";
          this.updateModel();
        })
        .catch((error) => {
          console.error("Error creating album:", error);
        });
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
      this.$emit("update:modelValue", payload);
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
    songData: {
      handler() {
        this.updateModel();
      },
      deep: true,
    },
  },
};
</script>
