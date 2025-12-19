<template>
  <div>
    <!-- Music Type -->
    <v-select
      v-model="localData.music_type_id"
      :items="lookupData.music_types || []"
      item-text="name"
      item-value="id"
      label="Music Type *"
      :rules="[(v) => !!v || 'Music type is required']"
      required
    ></v-select>

    <!-- Song Name -->
    <v-text-field
      v-model="localData.song_name"
      label="Song Name *"
      :rules="[(v) => !!v || 'Song name is required']"
      required
    ></v-text-field>

    <!-- Version Name -->
    <v-text-field v-model="localData.version_name" label="Version Name"></v-text-field>

    <!-- Album Selector -->
    <v-select
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
    </v-select>

    <!-- Artists -->
    <v-combobox
      v-model="localData.artists"
      :items="artistSuggestions"
      label="Artists *"
      multiple
      chips
      :rules="[(v) => (v && v.length > 0) || 'At least one artist is required']"
      @input="onArtistsInput"
      required
    ></v-combobox>

    <!-- BPM -->
    <v-text-field
      v-model.number="localData.bpm"
      label="BPM"
      type="number"
      :rules="[(v) => !v || (v >= 0 && v <= 999) || 'BPM must be between 0 and 999']"
      maxlength="3"
    ></v-text-field>

    <!-- Music Key -->
    <v-select
      v-model="localData.music_key_id"
      :items="lookupData.music_keys || []"
      item-text="name"
      item-value="id"
      label="Music Key"
      clearable
    ></v-select>

    <!-- Genre -->
    <v-select
      v-model="localData.music_genre_id"
      :items="lookupData.music_genres || []"
      item-text="name"
      item-value="id"
      label="Genre"
      clearable
    ></v-select>

    <!-- Completion Date -->
    <v-date-picker
      v-model="localData.completion_date"
      label="Completion Date"
    ></v-date-picker>
    <v-text-field
      v-model="localData.completion_date"
      label="Completion Date"
      type="date"
    ></v-text-field>

    <!-- Release Date -->
    <v-text-field
      v-model="localData.release_date"
      label="Release Date *"
      type="date"
      :rules="[(v) => !!v || 'Release date is required']"
      required
    ></v-text-field>

    <!-- Status -->
    <v-select
      v-model="localData.music_creation_status_id"
      :items="lookupData.music_creation_statuses || []"
      item-text="name"
      item-value="id"
      label="Status"
      clearable
    ></v-select>

    <!-- Link Child Assignments -->
    <v-divider class="my-4"></v-divider>
    <v-subheader>Link Child Assignments</v-subheader>
    <v-select
      v-model="localData.child_assignment_types"
      :items="availableChildDepartments"
      item-text="name"
      item-value="id"
      label="Select departments for child assignments"
      multiple
      chips
    ></v-select>

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
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="showAlbumDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="createAlbum">Create</v-btn>
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
          this.artistSuggestions = response.data;
        })
        .catch((error) => {
          console.error("Error loading artists:", error);
        });
    },
    createAlbum() {
      if (!this.newAlbumName) return;

      axios
        .post("/api/albums", { name: this.newAlbumName })
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
