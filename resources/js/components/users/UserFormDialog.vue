<template>
  <v-dialog :value="dialog" max-width="600" persistent @input="handleClose">
    <v-card>
      <v-card-title>
        <span>{{ editMode ? "Edit User" : "Add New User" }}</span>
        <v-spacer></v-spacer>
        <v-btn icon small @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-form ref="form" v-model="valid">
          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="formData.name"
                label="Name *"
                :rules="[(v) => !!v || 'Name is required']"
                required
                outlined
                dense
              ></v-text-field>
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="formData.email"
                label="Email *"
                type="email"
                :rules="[
                  (v) => !!v || 'Email is required',
                  (v) => /.+@.+\..+/.test(v) || 'Email must be valid',
                ]"
                required
                outlined
                dense
              ></v-text-field>
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="formData.password"
                label="Password"
                :type="showPassword ? 'text' : 'password'"
                :rules="passwordRules"
                :hint="
                  editMode
                    ? 'Leave blank to keep current password'
                    : 'Password is required'
                "
                persistent-hint
                outlined
                dense
                :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                @click:append="showPassword = !showPassword"
              ></v-text-field>
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12">
              <v-autocomplete
                v-model="formData.role"
                :items="roles"
                item-text="name"
                item-value="name"
                label="Role *"
                :rules="[(v) => !!v || 'Role is required']"
                :loading="loadingRoles"
                required
                outlined
                dense
                chips
                small-chips
              ></v-autocomplete>
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12">
              <v-autocomplete
                v-model="formData.departments"
                :items="departments"
                item-text="name"
                item-value="id"
                label="Departments"
                multiple
                :loading="loadingDepartments"
                outlined
                dense
                chips
                small-chips
                clearable
              ></v-autocomplete>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions class="justify-end">
        <v-btn text @click="$emit('close')" class="mr-2">Cancel</v-btn>
        <v-btn
          color="primary"
          :disabled="!valid || loading"
          :loading="loading"
          @click="saveUser"
        >
          {{ editMode ? "Update" : "Create" }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
export default {
  name: "UserFormDialog",
  props: {
    dialog: {
      type: Boolean,
      default: false,
    },
    editMode: {
      type: Boolean,
      default: false,
    },
    userData: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      valid: false,
      loading: false,
      loadingRoles: false,
      loadingDepartments: false,
      showPassword: false,
      formData: {
        name: "",
        email: "",
        password: "",
        role: null,
        departments: [],
      },
      roles: [],
      departments: [],
    };
  },
  computed: {
    passwordRules() {
      if (this.editMode) {
        // Optional for edit mode
        return [(v) => !v || v.length >= 8 || "Password must be at least 8 characters"];
      } else {
        // Required for new users
        return [
          (v) => !!v || "Password is required",
          (v) => (v && v.length >= 8) || "Password must be at least 8 characters",
        ];
      }
    },
  },
  watch: {
    dialog(newVal) {
      if (newVal) {
        this.loadRoles();
        this.loadDepartments();
        if (this.editMode && this.userData) {
          this.populateForm();
        } else {
          this.resetForm();
        }
      }
    },
  },
  methods: {
    handleClose() {
      this.$emit("close");
    },
    populateForm() {
      this.formData = {
        name: this.userData.name || "",
        email: this.userData.email || "",
        password: "",
        role:
          this.userData.roles && this.userData.roles.length > 0
            ? this.userData.roles[0].name
            : null,
        departments: this.userData.departments
          ? this.userData.departments.map((dept) => dept.id)
          : [],
      };
    },
    resetForm() {
      this.formData = {
        name: "",
        email: "",
        password: "",
        role: null,
        departments: [],
      };
      if (this.$refs.form) {
        this.$refs.form.resetValidation();
      }
    },
    async loadRoles() {
      this.loadingRoles = true;
      try {
        const response = await axios.get("/users/roles");
        this.roles = response.data;
      } catch (error) {
        console.error("Error loading roles:", error);
        this.$toast?.error("Failed to load roles");
      } finally {
        this.loadingRoles = false;
      }
    },
    async loadDepartments() {
      this.loadingDepartments = true;
      try {
        const response = await axios.get("/lookup/departments");
        this.departments = response.data;
      } catch (error) {
        console.error("Error loading departments:", error);
        this.$toast?.error("Failed to load departments");
      } finally {
        this.loadingDepartments = false;
      }
    },
    async saveUser() {
      if (!this.$refs.form.validate()) {
        return;
      }

      this.loading = true;
      try {
        const payload = {
          name: this.formData.name,
          email: this.formData.email,
          role: this.formData.role,
          departments: this.formData.departments || [],
        };

        // Only include password if provided
        if (this.formData.password) {
          payload.password = this.formData.password;
        }

        let response;
        if (this.editMode) {
          response = await axios.put(`/users/${this.userData.id}`, payload);
        } else {
          response = await axios.post("/users", payload);
        }

        this.$toast?.success(
          this.editMode ? "User updated successfully" : "User created successfully"
        );
        this.$emit("saved");
      } catch (error) {
        console.error("Error saving user:", error);
        let errorMessage = "Failed to save user. Please try again.";
        if (error.response && error.response.data) {
          if (error.response.data.message) {
            errorMessage = error.response.data.message;
          } else if (error.response.data.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            errorMessage = errors.join("\n");
          }
        }
        this.$toast?.error(errorMessage);
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
