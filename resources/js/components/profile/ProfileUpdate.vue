<template>
  <v-app>
    <v-row>
      <v-col cols="12" md="12">
        <v-card>
          <v-card-title>
            <span>Update Profile</span>
          </v-card-title>
          <br />
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

            <!-- Success Alert -->
            <v-alert
              v-if="successMessage"
              type="success"
              dense
              class="mb-4"
              dismissible
              @input="successMessage = ''"
            >
              {{ successMessage }}
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

              <v-divider class="my-4"></v-divider>

              <v-row>
                <v-col cols="12">
                  <h3 class="text-subtitle-1 mb-2">Change Password</h3>
                  <p class="text-caption text--secondary mb-3">
                    Leave blank if you don't want to change your password
                  </p>
                </v-col>
              </v-row>

              <v-row>
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model="formData.current_password"
                    label="Current Password"
                    :type="showCurrentPassword ? 'text' : 'password'"
                    :rules="passwordChangeRules"
                    :error-messages="fieldErrors.current_password || []"
                    dense
                    :append-icon="showCurrentPassword ? 'mdi-eye' : 'mdi-eye-off'"
                    @click:append="showCurrentPassword = !showCurrentPassword"
                    @input="clearFieldError('current_password')"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model="formData.password"
                    label="New Password"
                    :type="showPassword ? 'text' : 'password'"
                    :rules="rules.passwordOptional"
                    :error-messages="fieldErrors.password || []"
                    hint="Password must be at least 8 characters"
                    persistent-hint
                    dense
                    :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                    @click:append="showPassword = !showPassword"
                    @input="clearFieldError('password')"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model="formData.password_confirmation"
                    label="Confirm New Password"
                    :type="showPasswordConfirmation ? 'text' : 'password'"
                    :rules="confirmPasswordRules"
                    :error-messages="fieldErrors.password_confirmation || []"
                    dense
                    :append-icon="showPasswordConfirmation ? 'mdi-eye' : 'mdi-eye-off'"
                    @click:append="showPasswordConfirmation = !showPasswordConfirmation"
                    @input="clearFieldError('password_confirmation')"
                  ></v-text-field>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>

          <v-card-actions class="justify-end px-6 pb-4">
            <v-btn color="primary" :loading="loading" @click="updateProfile">
              Update Profile
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-app>
</template>

<script>
export default {
  name: "ProfileUpdate",
  props: {
    user: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      loading: false,
      showCurrentPassword: false,
      showPassword: false,
      showPasswordConfirmation: false,
      errorMessage: "",
      successMessage: "",
      fieldErrors: {},
      formData: {
        name: "",
        email: "",
        current_password: "",
        password: "",
        password_confirmation: "",
      },
      rules: {
        name: [(v) => !!v || "Name is required"],
        email: [
          (v) => !!v || "Email is required",
          (v) => /.+@.+\..+/.test(v) || "Email must be valid",
        ],
        passwordOptional: [
          (v) => !v || v.length >= 8 || "Password must be at least 8 characters",
        ],
      },
    };
  },
  computed: {
    passwordChangeRules() {
      // If user wants to change password, current password is required
      if (this.formData.password || this.formData.password_confirmation) {
        return [(v) => !!v || "Current password is required to change password"];
      }
      return [];
    },
    confirmPasswordRules() {
      // If new password is provided, confirmation is required and must match
      if (this.formData.password) {
        return [
          (v) => !!v || "Please confirm your new password",
          (v) => v === this.formData.password || "Passwords do not match",
        ];
      }
      return [];
    },
  },
  mounted() {
    this.populateForm();
  },
  methods: {
    populateForm() {
      this.formData.name = this.user.name || "";
      this.formData.email = this.user.email || "";
    },
    clearErrors() {
      this.errorMessage = "";
      this.fieldErrors = {};
    },
    clearFieldError(fieldName) {
      if (this.fieldErrors[fieldName]) {
        const newFieldErrors = { ...this.fieldErrors };
        delete newFieldErrors[fieldName];
        this.fieldErrors = newFieldErrors;
      }
    },
    async updateProfile() {
      // Clear previous messages
      this.clearErrors();
      this.successMessage = "";

      if (!this.$refs.form.validate()) {
        return;
      }

      this.loading = true;
      try {
        const payload = {
          name: this.formData.name,
          email: this.formData.email,
        };

        // Only include password fields if user wants to change password
        if (this.formData.password) {
          payload.current_password = this.formData.current_password;
          payload.password = this.formData.password;
          payload.password_confirmation = this.formData.password_confirmation;
        }

        const response = await axios.put("/profile", payload);

        this.successMessage = "Profile updated successfully!";

        // Clear password fields after successful update
        this.formData.current_password = "";
        this.formData.password = "";
        this.formData.password_confirmation = "";

        // Reset form validation
        if (this.$refs.form) {
          this.$refs.form.resetValidation();
        }

        // Scroll to top to show success message
        window.scrollTo({ top: 0, behavior: "smooth" });
      } catch (error) {
        console.error("Error updating profile:", error);

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
              "Failed to update profile. Please check the form and try again.";
          }
        } else {
          // Network or other errors
          this.errorMessage = "Failed to update profile. Please try again.";
        }

        // Scroll to top to show error message
        window.scrollTo({ top: 0, behavior: "smooth" });
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
