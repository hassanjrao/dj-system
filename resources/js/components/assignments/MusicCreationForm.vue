<template>
  <div>
    <v-row>
      <!-- Left Column -->
      <v-col cols="12" md="6">
        <!-- Assigned To is handled in parent AssignmentForm -->

        <!-- Music Type -->
        <v-autocomplete
          v-model="localData.music_type_id"
          :items="lookupData.music_types || []"
          item-text="name"
          item-value="id"
          label="Music Type *"
          :rules="[(v) => !!v || 'Music type is required']"
          required
        ></v-autocomplete>

        <!-- Song Name -->
        <v-text-field
          v-model="localData.song_name"
          label="Song Name *"
          :rules="[(v) => !!v || 'Song name is required']"
          required
        ></v-text-field>

        <!-- Version Name -->
        <v-text-field
          v-model="localData.version_name"
          label="Version Name"
        ></v-text-field>

        <!-- Album Selector -->
        <v-autocomplete
          v-model="localData.album_id"
          :items="albums"
          item-text="name"
          item-value="id"
          label="Album"
          clearable
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
          v-model="localData.artists"
          :items="artistSuggestions"
          label="Artist(s) *"
          multiple
          chips
          :rules="[(v) => (v && v.length > 0) || 'At least one artist is required']"
          @input="onArtistsInput"
          required
        ></v-autocomplete>
      </v-col>

      <!-- Right Column -->
      <v-col cols="12" md="6">
        <!-- BPM -->
        <v-text-field
          v-model.number="localData.bpm"
          label="BPM"
          type="number"
          :rules="[(v) => !v || (v >= 0 && v <= 999) || 'BPM must be between 0 and 999']"
          maxlength="3"
        ></v-text-field>

        <!-- Music Key -->
        <v-autocomplete
          v-model="localData.music_key_id"
          :items="lookupData.music_keys || []"
          item-text="name"
          item-value="id"
          label="Key"
          clearable
        ></v-autocomplete>

        <!-- Genre -->
        <v-autocomplete
          v-model="localData.music_genre_id"
          :items="lookupData.music_genres || []"
          item-text="name"
          item-value="id"
          label="Genre"
          clearable
        ></v-autocomplete>

        <!-- Release Date -->
        <v-text-field
          v-model="localData.release_date"
          label="Release Date & Time (PST) *"
          type="datetime-local"
          :rules="[(v) => !!v || 'Release date is required']"
          required
        ></v-text-field>

        <!-- Completion Date -->
        <v-row>
          <v-col cols="9">
            <v-text-field
              v-model="localData.completion_date"
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
        ></v-autocomplete>
      </v-col>
    </v-row>

    <!-- Link Child Assignments -->
    <v-divider class="my-4"></v-divider>
    <v-subheader>PLEASE SELECT ALL ASSIGNMENTS THAT NEED TO BE LINKED</v-subheader>
    <v-row>
      <v-col cols="12">
        <v-autocomplete
          v-model="localData.child_assignment_types"
          :items="availableChildDepartments"
          item-text="name"
          item-value="id"
          label="Select departments for child assignments"
          multiple
          chips
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
      artistSuggestions: [],
      showAlbumDialog: false,
      newAlbumName: "",
      albums: [],
    };
  },
  computed: {
    availableChildDepartments() {
      // Departments that can be child assignments of Music Creation
      return [
        { id: "music_mastering", name: "Music Mastering" },
        { id: "graphic_design", name: "Graphic Design" },
        { id: "video_filming", name: "Video Filming" },
        { id: "video_editing", name: "Video Editing" },
        { id: "distribution_video", name: "Distribution - Video" },
        { id: "distribution_graphic", name: "Distribution - Graphic" },
        { id: "distribution_music", name: "Distribution - Music" },
        { id: "marketing", name: "Marketing" },
      ];
    },
  },
  mounted() {
    this.loadAlbums();
    this.loadArtistSuggestions();
    if (this.parentData) {
      this.populateFromParent();
    }
  },
  methods: {
    populateFromParent() {
      // Auto-populate from parent if this is a child assignment
      if (this.parentData.song_name) {
        this.localData.song_name = this.parentData.song_name;
      }
      if (this.parentData.music_type_id) {
        this.localData.music_type_id = this.parentData.music_type_id;
      }
      if (this.parentData.release_date) {
        this.localData.release_date = this.parentData.release_date;
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
        .get("/api/albums")
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
        .get("/api/artists")
        .then((response) => {
          // Extract artist names from the response
          this.artistSuggestions = response.data.map((artist) => artist.name || artist);
        })
        .catch((error) => {
          console.error("Error loading artists:", error);
        });
    },
    createAlbum() {
      if (!this.newAlbumName) return;

      axios
        .post("albums", { name: this.newAlbumName })
        .then((response) => {
          this.albums.push(response.data);
          this.localData.album_id = response.data.id;
          this.showAlbumDialog = false;
          this.newAlbumName = "";
          this.updateModel();
        })
        .catch((error) => {
          console.error("Error creating album:", error);
        });
    },
    calculateCompletionDate() {
      // Emit event to parent to calculate completion date
      this.$emit("calculate-completion-date");
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
