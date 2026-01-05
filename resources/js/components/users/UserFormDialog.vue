<template>
  <v-dialog :value="dialog" max-width="800" persistent @input="handleClose">
    <v-card>
      <v-card-title>
        <span>{{ editMode ? "Edit User" : "Add New User" }}</span>
        <v-spacer></v-spacer>
        <v-btn icon small @click="$emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text>
        <!-- Error Alert -->
        <v-alert
          v-if="errorMessage || Object.keys(fieldErrors).length > 0"
          type="error"
          dense

          class="mb-4"
          dismissible
          @input="clearErrors"
        >
          <div v-if="errorMessage" class="mb-2">
            <strong>{{ errorMessage }}</strong>
          </div>
          <div v-if="Object.keys(fieldErrors).length > 0">
            <div v-for="(errors, field) in fieldErrors" :key="field" class="mt-1">
              <div v-for="(error, index) in errors" :key="index">• {{ error }}</div>
            </div>
          </div>
        </v-alert>

        <v-form ref="form">
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.name"
                label="Name *"
                :rules="rules.name"
                :error-messages="fieldErrors.name || []"
                required

                dense
                @input="clearFieldError('name')"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.email"
                label="Email *"
                type="email"
                :rules="rules.email"
                :error-messages="fieldErrors.email || []"
                required

                dense
                @input="clearFieldError('email')"
              ></v-text-field>
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.password"
                label="Password"
                :type="showPassword ? 'text' : 'password'"
                :rules="editMode ? rules.passwordOptional : rules.passwordRequired"
                :error-messages="fieldErrors.password || []"
                :hint="
                  editMode
                    ? 'Leave blank to keep current password'
                    : 'Password is required'
                "
                persistent-hint

                dense
                :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                @click:append="showPassword = !showPassword"
                @input="clearFieldError('password')"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="formData.role"
                :items="roles"
                item-text="name"
                item-value="name"
                label="Role *"
                :rules="rules.role"
                :error-messages="fieldErrors.role || []"
                :loading="loadingRoles"
                required

                dense
                chips
                small-chips
                @input="clearFieldError('role')"
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
                :error-messages="fieldErrors.departments || []"
                multiple
                :loading="loadingDepartments"

                dense
                chips
                small-chips
                clearable
                @input="clearFieldError('departments')"
              ></v-autocomplete>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions class="justify-end">
        <v-btn text @click="$emit('close')" class="mr-2">Cancel</v-btn>
        <v-btn color="primary" :loading="loading" @click="saveUser">
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
      loading: false,
      loadingRoles: false,
      loadingDepartments: false,
      showPassword: false,
      errorMessage: "",
      fieldErrors: {},
      formData: {
        name: "",
        email: "",
        password: "",
        role: null,
        departments: [],
      },
      roles: [],
      departments: [],
      rules: {
        name: [(v) => !!v || "Name is required"],
        email: [
          (v) => !!v || "Email is required",
          (v) => /.+@.+\..+/.test(v) || "Email must be valid",
        ],
        passwordRequired: [
          (v) => !!v || "Password is required",
          (v) => (v && v.length >= 8) || "Password must be at least 8 characters",
        ],
        passwordOptional: [
          (v) => !v || v.length >= 8 || "Password must be at least 8 characters",
        ],
        role: [(v) => !!v || "Role is required"],
      },
    };
  },
  watch: {
    dialog(newVal) {
      if (newVal) {
        this.clearErrors();
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
      this.clearErrors();
      this.$emit("close");
    },
    clearErrors() {
      this.errorMessage = "";
      this.fieldErrors = {};
    },
    clearFieldError(fieldName) {
      // Clear error for specific field when user starts typing
      if (this.fieldErrors[fieldName]) {
        // Create new object without the field to ensure reactivity
        const newFieldErrors = { ...this.fieldErrors };
        delete newFieldErrors[fieldName];
        this.fieldErrors = newFieldErrors;
      }
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
      this.clearErrors();
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
      // Clear previous errors
      this.clearErrors();

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

        // Handle backend validation errors
        if (error.response && error.response.data) {
          const errorData = error.response.data;

          // Set general error message
          if (errorData.message) {
            this.errorMessage = errorData.message;
          }

          // Set field-specific errors
          if (errorData.errors && typeof errorData.errors === "object") {
            this.fieldErrors = {};
            Object.keys(errorData.errors).forEach((field) => {
              if (Array.isArray(errorData.errors[field])) {
                this.fieldErrors[field] = errorData.errors[field];
              }
            });
          }

          // If no specific errors, show a generic message
          if (!this.errorMessage && Object.keys(this.fieldErrors).length === 0) {
            this.errorMessage =
              "Failed to save user. Please check the form and try again.";
          }
        } else {
          // Network or other errors
          this.errorMessage = "Failed to save user. Please try again.";
        }

        // Also show toast notification
        const toastMessage =
          this.errorMessage || "Validation errors occurred. Please check the form.";
        this.$toast?.error(toastMessage);
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
