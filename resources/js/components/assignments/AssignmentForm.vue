<template>
  <v-app>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <div>
          <span>{{ formTitle }}</span>
          <div class="text-caption grey--text">
            Step {{ currentStep }} of {{ 2 + childAssignmentsQueue.length }}
          </div>
          <!-- Creation and Update Info (Edit Mode Only) -->
          <div v-if="isEdit && formData.created_at_formatted" class="mt-2">
            <div class="text-caption">
              <span class="font-weight-medium">Created:</span>
              <span v-if="formData.created_by_name"
                >{{ formData.created_by_name }} on
              </span>
              <span>{{ formData.created_at_formatted }}</span>
            </div>
            <div v-if="formData.updated_at_formatted" class="text-caption">
              <span class="font-weight-medium">Last Updated:</span>
              <span>{{ formData.updated_at_formatted }}</span>
            </div>
          </div>
        </div>
        <div class="d-flex align-center">
          <v-btn text small @click="cancel" class="mr-2">Cancel</v-btn>
          <v-btn
            v-if="currentStep === 1"
            color="primary"
            small
            :disabled="loading"
            @click="goToNextStep"
            >Next</v-btn
          >
          <v-btn
            v-if="currentStep === 2 && !isEdit"
            text
            small
            @click="goToPreviousStep"
            class="mr-2"
            >Back</v-btn
          >
          <v-btn
            v-if="currentStep === 2"
            color="primary"
            small
            :disabled="loading"
            :loading="loading"
            @click="submit"
            >Save</v-btn
          >
          <v-btn v-if="currentStep >= 3" text small @click="goToPreviousStep" class="mr-2"
            >Back</v-btn
          >
          <v-btn
            v-if="
              currentStep >= 3 && currentChildIndex < childAssignmentsQueue.length - 1
            "
            color="primary"
            small
            :disabled="loading || childFormLoading"
            :loading="loading"
            @click="submit"
            >Save & Continue</v-btn
          >
          <v-btn
            v-if="
              currentStep >= 3 && currentChildIndex === childAssignmentsQueue.length - 1
            "
            color="primary"
            small
            :disabled="loading || childFormLoading"
            :loading="loading"
            @click="submit"
            >Save</v-btn
          >
        </div>
      </v-card-title>
      <v-card-text>
        <!-- Validation Error Alert -->
        <v-alert
          v-if="validationErrors.length > 0"
          type="error"
          dismissible
          @input="validationErrors = []"
          class="mb-4"
        >
          <div class="font-weight-bold mb-2">Please fix the following errors:</div>
          <ul class="mb-0">
            <li v-for="(error, index) in validationErrors" :key="index">{{ error }}</li>
          </ul>
        </v-alert>

        <!-- Progress Bar -->
        <v-progress-linear
          :value="(currentStep / (2 + childAssignmentsQueue.length)) * 100"
          color="primary"
          class="mb-4"
        ></v-progress-linear>

        <!-- Step 1: Department and Client Selection -->
        <v-form ref="step1Form" v-model="step1Valid" v-if="currentStep === 1">
          <v-row>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="selectedClient"
                :items="localClients"
                item-text="name"
                item-value="id"
                label="Client *"
                :rules="[(v) => !!v || 'Client is required']"
                @change="onClientChange"
                :search-input.sync="clientSearch"
                chips
                small-chips
                required
              >
                <template v-slot:append-item>
                  <v-list-item @click="showClientDialog = true">
                    <v-list-item-content>
                      <v-list-item-title>+ Create New Client</v-list-item-title>
                    </v-list-item-content>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="formData.department_id"
                :items="departments"
                item-text="name"
                item-value="id"
                label="Department *"
                :rules="[(v) => !!v || 'Department is required']"
                @change="onDepartmentChange"
                :search-input.sync="departmentSearch"
                chips
                small-chips
                required
              ></v-autocomplete>
            </v-col>
          </v-row>
        </v-form>

        <!-- Step 2: Department-specific form (only shown after clicking Next) -->
        <v-form ref="form" v-model="valid" v-if="currentStep === 2">
          <v-divider class="my-4"></v-divider>

          <!-- Selected Department and Client (Disabled) -->
          <v-row>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="selectedClient"
                :items="localClients"
                item-text="name"
                item-value="id"
                label="Client"
                disabled
                chips
                small-chips
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="formData.department_id"
                :items="departments"
                item-text="name"
                item-value="id"
                label="Department"
                disabled
                chips
                small-chips
              ></v-autocomplete>
            </v-col>
          </v-row>

          <!-- Assigned To (filtered by department) -->
          <v-row>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="formData.assigned_to_id"
                :items="filteredUsers"
                item-text="name"
                item-value="id"
                :label="
                  selectedDepartmentName
                    ? `${selectedDepartmentName} Assigned To *`
                    : 'Assignment Assigned To *'
                "
                :rules="[(v) => !!v || 'Assigned user is required']"
                :loading="loadingUsers"
                chips
                small-chips
                required
              ></v-autocomplete>
            </v-col>
          </v-row>

          <v-divider class="my-6" style="border-width: 2px; opacity: 0.5"></v-divider>
          <!-- Department-specific forms -->
          <MusicCreationForm
            v-if="formData.department_id === departmentIds.musicCreationId"
            v-model="formData"
            :is-child="isChild"
            :parent-data="parentData"
            :lookup-data="lookupData"
            :departments="departments"
            :assignment-data="currentAssignmentData"
            @update:modelValue="updateFormData"
          />

          <MusicMasteringForm
            v-else-if="formData.department_id === departmentIds.musicMasteringId"
            v-model="formData"
            :is-child="isChild"
            :parent-data="parentData"
            :lookup-data="lookupData"
            :available-songs="availableSongs"
            :selected-department-id="formData.department_id || null"
            :assignment-data="currentAssignmentData"
            @update:modelValue="updateFormData"
          />

          <!-- Link Child Assignments (Common for all departments) -->
          <v-divider
            v-if="childDepartmentsWithData.length > 0 && canCreateChildAssignments"
            style="border-width: 2px; opacity: 0.5"
          ></v-divider>

          <v-subheader
            v-if="childDepartmentsWithData.length > 0 && canCreateChildAssignments"
            >PLEASE SELECT ALL ASSIGNMENTS THAT NEED TO BE LINKED</v-subheader
          >
          <v-row v-if="childDepartmentsWithData.length > 0 && canCreateChildAssignments">
            <v-col cols="12">
              <v-card>
                <v-card-text>
                  <v-row class="mb-2">
                    <v-col :cols="isEdit ? 3 : 12" class="font-weight-bold">
                      Department
                    </v-col>
                    <v-col v-if="isEdit" cols="3" class="font-weight-bold">
                      Status
                    </v-col>
                    <v-col v-if="isEdit" cols="3" class="font-weight-bold">
                      Due Date
                    </v-col>
                    <v-col v-if="isEdit" cols="3" class="font-weight-bold">
                      Assigned To
                    </v-col>
                  </v-row>
                  <v-divider class="mb-2"></v-divider>
                  <v-row
                    v-for="dept in childDepartmentsWithData"
                    :key="dept.id"
                    class="mb-1 align-center"
                    style="min-height: 48px"
                  >
                    <v-col :cols="isEdit ? 3 : 12" class="d-flex align-center">
                      <v-checkbox
                        :value="dept.id"
                        v-model="formData.child_departments"
                        :label="dept.name"
                        hide-details
                        dense
                      ></v-checkbox>
                    </v-col>
                    <v-col v-if="isEdit" cols="3" class="d-flex align-center">
                      <v-chip
                        v-if="dept.hasChildAssignment && dept.status"
                        :color="getStatusColor(dept.status)"
                        small
                        text-color="white"
                      >
                        {{ dept.status }}
                      </v-chip>
                      <span v-else-if="dept.hasChildAssignment" class="text-body-2">
                        N/A
                      </span>
                      <span v-else class="text-body-2 grey--text">-</span>
                    </v-col>
                    <v-col v-if="isEdit" cols="3" class="d-flex align-center">
                      <div v-if="dept.dueDateDays">
                        <div
                          v-if="dept.dueDateDays.date"
                          class="font-weight-medium text-body-2"
                        >
                          {{ dept.dueDateDays.date }}
                        </div>
                        <v-chip
                          v-if="dept.dueDateDays.text"
                          :color="dept.dueDateDays.color"
                          small
                          text-color="white"
                          class="mt-1"
                        >
                          {{ dept.dueDateDays.text }}
                        </v-chip>
                      </div>
                      <span v-else class="text-body-2 grey--text">-</span>
                    </v-col>
                    <v-col v-if="isEdit" cols="3" class="d-flex align-center">
                      <span v-if="dept.hasChildAssignment" class="text-body-2">
                        {{ dept.assignedTo || "Unassigned" }}
                      </span>
                      <span v-else class="text-body-2 grey--text">-</span>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
          <!-- Common fields -->

          <v-divider style="border-width: 2px; opacity: 0.5"></v-divider>
          <!-- Notes Section -->
          <v-row>
            <v-col cols="12">
              <v-subheader>Notes</v-subheader>
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12" md="8">
              <v-text-field v-model="newNote.note" label="Note"></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-autocomplete
                v-model="newNote.note_for"
                :items="noteForOptions"
                item-text="label"
                item-value="value"
                label="Note For"
                chips
                small-chips
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="1" class="d-flex align-center">
              <v-btn
                icon
                color="primary"
                @click="addNote"
                :disabled="!newNote.note || !newNote.note_for"
              >
                <v-icon>mdi-plus</v-icon>
              </v-btn>
            </v-col>
          </v-row>

          <!-- Display Added Notes -->
          <v-row v-if="formData.notes && formData.notes.length > 0">
            <v-col cols="12">
              <v-list>
                <v-list-item
                  v-for="(note, index) in formData.notes"
                  :key="index"
                  class="px-0 mb-3"
                  @click.stop
                >
                  <v-list-item-content @click.stop>
                    <div v-if="editingNoteIndex !== index">
                      <v-list-item-title>
                        <strong>{{ getNoteForLabel(note.note_for) }}:</strong>
                        <span v-html="linkifyText(note.note)"></span>
                      </v-list-item-title>
                      <v-list-item-subtitle class="text-caption mt-1">
                        <div v-if="note.created_by">
                          Added by:
                          <span class="font-weight-medium">{{ note.created_by }}</span> on
                          <span class="grey--text text--darken-1">{{
                            note.created_at
                          }}</span>
                        </div>
                        <div v-if="note.updated_by" class="orange--text text--darken-2">
                          Updated by:
                          <span class="font-weight-medium">{{ note.updated_by }}</span> on
                          <span class="orange--text text--darken-1">{{
                            note.updated_at
                          }}</span>
                        </div>
                      </v-list-item-subtitle>
                    </div>
                    <div v-else>
                      <v-text-field
                        v-model="editingNote.note"
                        label="Edit Note"
                        dense
                        @click.stop
                      ></v-text-field>
                      <v-autocomplete
                        v-model="editingNote.note_for"
                        :items="noteForOptions"
                        item-text="label"
                        item-value="value"
                        label="Note For"
                        dense
                        chips
                        small-chips
                        @click.stop
                      ></v-autocomplete>
                    </div>
                  </v-list-item-content>
                  <v-list-item-action @click.stop>
                    <div v-if="editingNoteIndex !== index" class="d-flex flex-column">
                      <v-btn
                        v-if="note.canEdit"
                        icon
                        small
                        color="primary"
                        @click.stop="startEditNote(index)"
                        class="mb-1"
                      >
                        <v-icon small>mdi-pencil</v-icon>
                      </v-btn>
                      <v-btn
                        v-if="note.canDelete"
                        icon
                        small
                        color="error"
                        @click.stop="confirmDeleteNote(index)"
                      >
                        <v-icon small>mdi-delete</v-icon>
                      </v-btn>
                    </div>
                    <div v-else class="d-flex flex-column">
                      <v-btn
                        icon
                        small
                        color="success"
                        @click.stop="saveEditNote(index)"
                        class="mb-1"
                      >
                        <v-icon small>mdi-check</v-icon>
                      </v-btn>
                      <v-btn icon small @click.stop="cancelEditNote">
                        <v-icon small>mdi-close</v-icon>
                      </v-btn>
                    </div>
                  </v-list-item-action>
                </v-list-item>
              </v-list>
            </v-col>
          </v-row>
        </v-form>

        <!-- Step 3+: Child Assignment Forms -->
        <v-form ref="form" v-model="valid" v-if="currentStep >= 3">
          <v-alert type="info" text class="mb-4">
            Editing child assignment {{ currentChildIndex + 1 }} of
            {{ childAssignmentsQueue.length }}:
            <strong>{{
              childAssignmentsQueue[currentChildIndex]?.department?.name
            }}</strong>
          </v-alert>

          <v-divider class="my-4"></v-divider>

          <!-- Assigned To (filtered by department) -->
          <v-row>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="formData.assigned_to_id"
                :items="filteredUsers"
                item-text="name"
                item-value="id"
                :label="
                  selectedDepartmentName
                    ? `${selectedDepartmentName} Assigned To *`
                    : 'Assignment Assigned To *'
                "
                :rules="[(v) => !!v || 'Assigned user is required']"
                :loading="loadingUsers"
                chips
                small-chips
                required
              ></v-autocomplete>
            </v-col>
          </v-row>

          <!-- Department-specific forms for child assignments -->
          <MusicCreationForm
            v-if="formData.department_id === departmentIds.musicCreationId"
            v-model="formData"
            :is-child="true"
            :parent-data="parentAssignmentData"
            :lookup-data="lookupData"
            :departments="departments"
            :assignment-data="currentAssignmentData"
            @update:modelValue="updateFormData"
          />

          <MusicMasteringForm
            v-else-if="formData.department_id === departmentIds.musicMasteringId"
            v-model="formData"
            :is-child="true"
            :parent-data="parentAssignmentData"
            :lookup-data="lookupData"
            :available-songs="availableSongs"
            :selected-department-id="formData.department_id || null"
            :assignment-data="currentAssignmentData"
            @update:modelValue="updateFormData"
          />

          <!-- Common fields -->
          <v-divider class="my-4"></v-divider>

          <!-- Notes Section -->
          <v-row>
            <v-col cols="12">
              <v-subheader>Notes</v-subheader>
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12" md="8">
              <v-text-field v-model="newNote.note" label="Note"></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-autocomplete
                v-model="newNote.note_for"
                :items="noteForOptions"
                item-text="label"
                item-value="value"
                label="Note For"
                chips
                small-chips
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="1" class="d-flex align-center">
              <v-btn
                icon
                color="primary"
                @click="addNote"
                :disabled="!newNote.note || !newNote.note_for"
              >
                <v-icon>mdi-plus</v-icon>
              </v-btn>
            </v-col>
          </v-row>

          <!-- Display Added Notes -->
          <v-row v-if="formData.notes && formData.notes.length > 0">
            <v-col cols="12">
              <v-list>
                <v-list-item
                  v-for="(note, index) in formData.notes"
                  :key="index"
                  class="px-0 mb-3"
                  @click.stop
                >
                  <v-list-item-content @click.stop>
                    <div v-if="editingNoteIndex !== index">
                      <v-list-item-title>
                        <strong>{{ getNoteForLabel(note.note_for) }}:</strong>
                        <span v-html="linkifyText(note.note)"></span>
                      </v-list-item-title>
                      <v-list-item-subtitle class="text-caption mt-1">
                        <div v-if="note.created_by">
                          Created by {{ note.created_by }} on
                          {{ note.created_at }}
                        </div>
                        <div v-if="note.updated_by">
                          Updated by {{ note.updated_by }} on
                          {{ note.updated_at }}
                        </div>
                      </v-list-item-subtitle>
                    </div>
                    <div v-else>
                      <v-text-field
                        v-model="editingNote.note"
                        label="Edit Note"
                        dense
                        @click.stop
                      ></v-text-field>
                      <v-autocomplete
                        v-model="editingNote.note_for"
                        :items="noteForOptions"
                        item-text="label"
                        item-value="value"
                        label="Note For"
                        dense
                        chips
                        small-chips
                        @click.stop
                      ></v-autocomplete>
                    </div>
                  </v-list-item-content>
                  <v-list-item-action @click.stop>
                    <div v-if="editingNoteIndex !== index" class="d-flex flex-column">
                      <v-btn
                        v-if="note.canEdit"
                        icon
                        small
                        color="primary"
                        @click.stop="startEditNote(index)"
                        class="mb-1"
                      >
                        <v-icon small>mdi-pencil</v-icon>
                      </v-btn>
                      <v-btn
                        v-if="note.canDelete"
                        icon
                        small
                        color="error"
                        @click.stop="confirmDeleteNote(index)"
                      >
                        <v-icon small>mdi-delete</v-icon>
                      </v-btn>
                    </div>
                    <div v-else class="d-flex flex-column">
                      <v-btn
                        icon
                        small
                        color="success"
                        @click.stop="saveEditNote(index)"
                        class="mb-1"
                      >
                        <v-icon small>mdi-check</v-icon>
                      </v-btn>
                      <v-btn icon small @click.stop="cancelEditNote">
                        <v-icon small>mdi-close</v-icon>
                      </v-btn>
                    </div>
                  </v-list-item-action>
                </v-list-item>
              </v-list>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
    </v-card>

    <!-- Client Creation Dialog -->
    <v-dialog v-model="showClientDialog" max-width="500">
      <v-card>
        <v-card-title>Create New Client</v-card-title>
        <v-card-text>
          <v-form ref="clientForm" v-model="clientFormValid">
            <v-text-field
              v-model="newClient.name"
              label="Client Name *"
              :rules="[(v) => !!v || 'Client name is required']"
              required
            ></v-text-field>
            <v-text-field
              v-model="newClient.email"
              label="Email"
              type="email"
            ></v-text-field>
            <v-text-field v-model="newClient.phone" label="Phone"></v-text-field>
            <v-textarea v-model="newClient.notes" label="Notes" rows="3"></v-textarea>
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn text small @click="closeClientDialog" class="mr-2">Cancel</v-btn>
          <v-btn color="primary" small :disabled="!clientFormValid" @click="createClient"
            >Create</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Note Deletion Confirmation Dialog -->
    <v-dialog v-model="showDeleteNoteDialog" max-width="400">
      <v-card>
        <v-card-title>Confirm Deletion</v-card-title>
        <v-card-text>
          Are you sure you want to delete this note? This action cannot be undone.
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn text small @click="showDeleteNoteDialog = false">Cancel</v-btn>
          <v-btn color="error" small @click="deleteNote">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script>
import MusicCreationForm from "./MusicCreationForm.vue";
import MusicMasteringForm from "./MusicMasteringForm.vue";
import GraphicDesignForm from "./GraphicDesignForm.vue";
import VideoFilmingForm from "./VideoFilmingForm.vue";
import VideoEditingForm from "./VideoEditingForm.vue";
import DistributionForm from "./DistributionForm.vue";

export default {
  name: "AssignmentForm",
  components: {
    MusicCreationForm,
    MusicMasteringForm,
    GraphicDesignForm,
    VideoFilmingForm,
    VideoEditingForm,
    DistributionForm,
  },
  props: {
    modelValue: {
      type: Object,
      default: () => ({}),
    },
    isEdit: {
      type: Boolean,
      default: false,
    },
    assignmentData: {
      type: Object,
      default: () => null,
    },
    isChild: {
      type: Boolean,
      default: false,
    },
    parentData: {
      type: Object,
      default: () => null,
    },
    availableAssignments: {
      type: Array,
      default: () => [],
    },
  },
  computed: {
    // Get user permissions from Vuex store
    isAdmin() {
      return this.$store.getters["auth/isAdmin"];
    },
    isSuperAdmin() {
      return this.$store.getters["auth/isSuperAdmin"];
    },
    currentUser() {
      return this.$store.getters["auth/user"];
    },
    isUserRole() {
      return this.$store.getters["auth/hasRole"]("user");
    },
    isMusicCreationDepartment() {
      return this.formData.department_id === this.departmentIds.musicCreationId;
    },
    canCreateChildAssignments() {
      // Users cannot create child assignments for MUSIC CREATION assignments
      // Only super-admin and admin can create child assignments for MUSIC CREATION
      if (this.isUserRole && this.isMusicCreationDepartment) {
        return false;
      }
      return true;
    },
    selectedDepartmentName() {
      if (!this.formData.department_id || !this.departments.length) {
        return "";
      }
      const department = this.departments.find(
        (d) => d.id === this.formData.department_id
      );
      return department ? department.name : "";
    },
    formTitle() {
      if (this.isEdit) {
        if (this.selectedDepartmentName) {
          return `Edit ${this.selectedDepartmentName} Assignment`;
        }
        return "Edit Assignment";
      }
      if (this.selectedDepartmentName) {
        return `Create ${this.selectedDepartmentName} Assignment`;
      }
      return "Create Assignment";
    },
    currentAssignmentData() {
      // Return prop if available, otherwise return loaded data
      return this.assignmentData || this.loadedAssignmentData;
    },
    // Merge available child departments with child assignments data for edit mode
    childDepartmentsWithData() {
      if (
        !this.availableChildDepartments ||
        this.availableChildDepartments.length === 0
      ) {
        return [];
      }

      const assignmentData = this.currentAssignmentData;
      const childAssignments = assignmentData?.childAssignments || [];

      // Create a map of department_id to child assignment for quick lookup
      const childAssignmentMap = {};
      childAssignments.forEach((child) => {
        if (child.department_id) {
          childAssignmentMap[child.department_id] = child;
        }
      });

      // Merge available departments with child assignment data
      return this.availableChildDepartments.map((dept) => {
        const childAssignment = childAssignmentMap[dept.id];
        let status = null;
        let assignedTo = null;
        let dueDateDays = null;

        if (childAssignment) {
          // Get status - could be from status relationship or assignment_status field
          if (childAssignment.status && childAssignment.status.name) {
            status = childAssignment.status.name;
          } else if (childAssignment.assignment_status) {
            status = childAssignment.assignment_status;
          }

          // Get assigned user name
          if (childAssignment.assignedTo && childAssignment.assignedTo.name) {
            assignedTo = childAssignment.assignedTo.name;
          } else if (childAssignment.assigned_to && childAssignment.assigned_to.name) {
            assignedTo = childAssignment.assigned_to.name;
          } else {
            assignedTo = "Unassigned";
          }

          // Use backend-calculated completion_date and completion_date_days
          if (childAssignment.completion_date || childAssignment.completion_date_days) {
            const daysText = childAssignment.completion_date_days;
            let color = "success"; // Default color

            // Determine color based on text content (same logic as AssignmentList.vue)
            if (daysText && daysText.includes("overdue")) {
              color = "error";
            } else if (
              daysText &&
              (daysText.includes("today") || daysText.includes("Today"))
            ) {
              color = "warning";
            } else if (daysText) {
              // Extract number from text like "3 days to go" or "1 day to go"
              const match = daysText.match(/(\d+)\s+day/);
              if (match) {
                const days = parseInt(match[1]);
                if (days <= 3) {
                  color = "info";
                } else {
                  color = "success";
                }
              }
            }

            dueDateDays = {
              date: childAssignment.completion_date || null,
              text: daysText || null,
              color: color,
            };
          }
        }

        return {
          ...dept,
          hasChildAssignment: !!childAssignment,
          childAssignment: childAssignment || null,
          status: status,
          assignedTo: assignedTo,
          dueDateDays: dueDateDays,
        };
      });
    },
  },
  data() {
    return {
      currentStep: this.isEdit ? 2 : 1,
      valid: false,
      step1Valid: false,
      loading: false,
      loadingUsers: false,
      loadingInitialData: false,
      formData: {
        ...this.modelValue,
        child_departments: this.modelValue.child_departments || [],
      },
      selectedClient: null,
      filteredUsers: [],
      departments: [],
      clients: [],
      localClients: [],
      lookupData: {},
      showClientDialog: false,
      clientFormValid: false,
      departmentSearch: null,
      clientSearch: null,
      newClient: {
        name: "",
        email: "",
        phone: "",
        notes: "",
      },
      newNote: {
        note: "",
        note_for: null,
      },
      editingNoteIndex: null,
      editingNote: {
        note: "",
        note_for: null,
      },
      showDeleteNoteDialog: false,
      noteToDelete: null,
      noteForOptions: [
        { label: "Me", value: "me" },
        { label: "Team", value: "team" },
      ],
      availableChildDepartments: [],
      loadingChildDepartments: false,
      childAssignmentsQueue: [],
      currentChildIndex: 0,
      parentAssignmentId: null,
      parentAssignmentData: null,
      childFormLoading: false,
      availableSongs: [],
      departmentIds: {},
      loadedAssignmentData: null, // For storing assignment data loaded from API
      validationErrors: [], // For displaying validation errors in alert
    };
  },
  async mounted() {
    this.loadingInitialData = true;
    try {
      await this.getInitialData();

      // If editing, use assignmentData prop if provided, otherwise load from API
      if (this.isEdit) {
        const assignmentData = this.assignmentData || this.loadedAssignmentData;
        if (assignmentData) {
          // Use assignmentData from prop or loaded data
          this.formData = { ...this.formData, ...assignmentData };

          // Set child_departments if childAssignments exists - ensure it's always an array
          if (
            assignmentData.childAssignments &&
            Array.isArray(assignmentData.childAssignments)
          ) {
            this.formData.child_departments = assignmentData.childAssignments
              .map((child) => child.department_id)
              .filter((id) => id != null); // Filter out any null/undefined values
          } else {
            this.formData.child_departments = [];
          }

          console.log("formData", this.formData);

          // Update parent assignment data if it exists
          if (assignmentData.parent_assignment) {
            this.parentAssignmentData = assignmentData.parent_assignment;
          }

          // Ensure song_artists is properly set
          if (assignmentData.song_artists) {
            this.formData.song_artists = assignmentData.song_artists;
          } else if (assignmentData.song && assignmentData.song.artists) {
            this.formData.song_artists = assignmentData.song.artists.map(
              (artist) => artist.id
            );
          } else {
            this.formData.song_artists = [];
          }
        } else if (this.formData.id) {
          // Load from API if assignmentData prop not provided
          await this.loadAssignmentData(this.formData.id);
        }
      }

      this.initializeClient();
      this.initializeNotes();
      this.loadUsersForDepartment();
      this.loadAvailableSongs();

      // If editing, load child departments for the selected department
      if (this.isEdit && this.formData.department_id) {
        this.loadChildDepartments();
      }
    } catch (error) {
      console.error("Error loading initial data:", error);
      alert("Failed to load initial data. Please refresh the page.");
    } finally {
      this.loadingInitialData = false;
    }
  },
  methods: {
    async getInitialData() {
      try {
        // Fetch all initial data in a single API call
        const response = await axios.get("/lookup/get-initial-data");

        this.departments = response.data.departments;
        this.clients = response.data.clients;
        this.localClients = [...this.clients];
        this.lookupData = response.data.lookup_data;
        this.departmentIds = response.data.department_ids;
      } catch (error) {
        console.error("Error fetching initial data:", error);
        throw error;
      }
    },
    initializeClient() {
      // Set selected client if editing and client_id exists
      if (this.formData.client_id) {
        const client = this.localClients.find((c) => c.id === this.formData.client_id);
        if (client) {
          this.selectedClient = client.id;
        }
      }
    },
    onDepartmentChange() {
      // Reset department-specific fields when department changes
      this.$emit("department-changed", this.formData.department_id);
      // Load users for the selected department
      this.loadUsersForDepartment();
      // Load child departments for this parent department
      this.loadChildDepartments();
    },
    onClientChange(clientId) {
      // Handle client selection
      if (clientId) {
        this.formData.client_id = clientId;
        this.selectedClient = clientId;
      }
    },
    loadUsersForDepartment() {
      if (!this.formData.department_id) {
        this.filteredUsers = [];
        return;
      }

      this.loadingUsers = true;
      axios
        .get(`/users/${this.formData.department_id}`)
        .then((response) => {
          this.filteredUsers = response.data;
          this.loadingUsers = false;
        })
        .catch((error) => {
          console.error("Error loading users:", error);
          this.filteredUsers = [];
          this.loadingUsers = false;
        });
    },
    loadAvailableSongs() {
      axios
        .get("/songs")
        .then((response) => {
          this.availableSongs = response.data;
          console.log("Available songs loaded:", this.availableSongs.length);
        })
        .catch((error) => {
          console.error("Error loading songs:", error);
          this.availableSongs = [];
        });
    },
    loadChildDepartments() {
      if (!this.formData.department_id) {
        this.availableChildDepartments = [];
        return;
      }

      this.loadingChildDepartments = true;
      axios
        .get("/lookup/child-departments", {
          params: {
            department_id: this.formData.department_id,
          },
        })
        .then((response) => {
          this.availableChildDepartments = response.data;
          this.loadingChildDepartments = false;
        })
        .catch((error) => {
          console.error("Error loading child departments:", error);
          this.availableChildDepartments = [];
          this.loadingChildDepartments = false;
        });
    },
    createClient() {
      if (!this.$refs.clientForm.validate()) {
        return;
      }
      axios
        .post("/clients", this.newClient)
        .then((response) => {
          // Add new client to local clients list
          this.localClients.push(response.data);
          // Set as selected client
          this.selectedClient = response.data.id;
          this.formData.client_id = response.data.id;
          // Close dialog and reset form
          this.closeClientDialog();
        })
        .catch((error) => {
          console.error("Error creating client:", error);
          let errorMessage = "Error creating client. Please try again.";
          if (error.response && error.response.data && error.response.data.message) {
            errorMessage = error.response.data.message;
          } else if (
            error.response &&
            error.response.data &&
            error.response.data.errors
          ) {
            const errors = Object.values(error.response.data.errors).flat();
            errorMessage = errors.join("\n");
          }
          alert(errorMessage);
        });
    },
    closeClientDialog() {
      this.showClientDialog = false;
      this.newClient = {
        name: "",
        email: "",
        phone: "",
        notes: "",
      };
      if (this.$refs.clientForm) {
        this.$refs.clientForm.resetValidation();
      }
    },
    updateFormData(newData) {
      this.formData = { ...this.formData, ...newData };
      this.$emit("update:modelValue", this.formData);
    },
    updateModel() {
      this.$emit("update:modelValue", this.formData);
    },
    async loadAssignmentData(assignmentId) {
      console.log("loading assignment data", assignmentId);
      try {
        const response = await axios.get(`/assignments/${assignmentId}/edit`, {
          headers: {
            Accept: "application/json",
          },
        });

        const assignmentData = response.data.assignment;

        // Populate form data with assignment data
        // Merge to preserve any existing formData properties
        this.formData = { ...this.formData, ...assignmentData };

        // Set child_departments if childAssignments exists - ensure it's always an array
        if (
          assignmentData.childAssignments &&
          Array.isArray(assignmentData.childAssignments)
        ) {
          this.formData.child_departments = assignmentData.childAssignments
            .map((child) => child.department_id)
            .filter((id) => id != null); // Filter out any null/undefined values
        } else {
          this.formData.child_departments = [];
        }

        // Update parent assignment data if it exists
        if (assignmentData.parent_assignment) {
          this.parentAssignmentData = assignmentData.parent_assignment;
        }

        // Ensure song_artists is properly set (API should provide this, but handle both cases)
        if (assignmentData.song_artists) {
          this.formData.song_artists = assignmentData.song_artists;
        } else if (assignmentData.song && assignmentData.song.artists) {
          // Fallback: extract artist IDs from song.artists relationship
          this.formData.song_artists = assignmentData.song.artists.map(
            (artist) => artist.id
          );
        } else {
          this.formData.song_artists = [];
        }

        this.loadedAssignmentData = assignmentData;
        this.$emit("update:modelValue", this.formData);
      } catch (error) {
        console.error("Error loading assignment data:", error);
        throw error;
      }
    },
    loadChildAssignmentData(childId) {
      this.childFormLoading = true;
      axios
        .get(`/assignments/${childId}/edit`)
        .then((response) => {
          const childData = response.data.assignment;
          // Populate form data with child assignment data
          this.formData = { ...childData };

          // Update parent assignment data from child's parent relationship
          if (childData.parent_assignment) {
            this.parentAssignmentData = childData.parent_assignment;
          }

          this.childFormLoading = false;
        })
        .catch((error) => {
          console.error("Error loading child assignment:", error);
          this.childFormLoading = false;
          alert("Error loading child assignment data. Please try again.");
        });
    },
    submit() {
      console.log("submit", this.formData);

      // Validate the form - this will highlight invalid fields
      const isValid = this.$refs.form && this.$refs.form.validate();
      console.log("Form validation result:", isValid);

      if (isValid) {
        this.loading = true;

        // Prepare form data with notes array
        const submitData = { ...this.formData };

        // Ensure notes array exists and is properly formatted
        if (!submitData.notes || !Array.isArray(submitData.notes)) {
          submitData.notes = [];
        }

        // Filter out empty notes
        submitData.notes = submitData.notes.filter(
          (note) => note.note && note.note.trim()
        );

        // Ensure child_departments is always an array
        if (
          !submitData.child_departments ||
          !Array.isArray(submitData.child_departments)
        ) {
          submitData.child_departments = [];
        }

        // Check if we're in a child step (currentStep >= 3)
        if (this.currentStep >= 3) {
          // Update child assignment
          const childId = this.childAssignmentsQueue[this.currentChildIndex].id;
          axios
            .put(`/assignments/${childId}`, submitData)
            .then((response) => {
              this.loading = false;
              // Check if there are more children to process
              if (this.currentChildIndex < this.childAssignmentsQueue.length - 1) {
                // Move to next child
                this.currentChildIndex++;
                this.currentStep++;
                this.loadChildAssignmentData(
                  this.childAssignmentsQueue[this.currentChildIndex].id
                );
              } else {
                // All children processed, redirect to assignments list
                window.location.href = "/assignments";
              }
            })
            .catch((error) => {
              this.loading = false;
              console.error("Error updating child assignment:", error);

              let errorMessage = "Error updating child assignment. Please try again.";
              if (error.response && error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
              } else if (
                error.response &&
                error.response.data &&
                error.response.data.errors
              ) {
                const errors = Object.values(error.response.data.errors).flat();
                errorMessage = errors.join("\n");
              }

              alert(errorMessage);
            });
        } else {
          // Create or update parent assignment (Step 2)
          const url = this.isEdit ? `/assignments/${this.formData.id}` : "/assignments";
          const method = this.isEdit ? "put" : "post";

          axios[method](url, submitData)
            .then((response) => {
              this.loading = false;

              // Check if child assignments were created
              if (
                response.data.child_assignments &&
                response.data.child_assignments.length > 0
              ) {
                // Store child assignments queue
                this.childAssignmentsQueue = response.data.child_assignments;
                this.parentAssignmentId = response.data.id;
                this.parentAssignmentData = response.data;
                this.currentChildIndex = 0;
                this.currentStep = 3;

                // Load first child assignment
                this.loadChildAssignmentData(this.childAssignmentsQueue[0].id);
              } else {
                // No child assignments, redirect to assignments list
                window.location.href =
                  "/assignments?department_id=" + this.formData.department_id;
              }
            })
            .catch((error) => {
              this.loading = false;
              console.error("Error saving assignment:", error);

              let errorMessage = "Error saving assignment. Please try again.";
              if (error.response && error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
              } else if (
                error.response &&
                error.response.data &&
                error.response.data.errors
              ) {
                const errors = Object.values(error.response.data.errors).flat();
                errorMessage = errors.join("\n");
              }

              alert(errorMessage);
            });
        }
      } else {
        console.log("validation failed");
        this.showValidationErrors();
      }
    },
    getStatusColor(status) {
      if (!status) return "grey";

      const statusLower = status.toLowerCase();
      switch (statusLower) {
        case "pending":
          return "orange";
        case "in-progress":
          return "blue";
        case "completed":
          return "green";
        case "on-hold":
          return "red";
        default:
          return "grey";
      }
    },
    showValidationErrors() {
      // Collect all validation error messages from the form
      this.$nextTick(() => {
        const errorMessages = [];
        const errorElements = this.$el.querySelectorAll(".v-messages.error--text");

        console.log("Error elements found:", errorElements.length);

        errorElements.forEach((el) => {
          const messageText = el.innerText.trim();
          if (messageText) {
            console.log("Error message:", messageText);
            errorMessages.push(messageText);
          }
        });

        // Also check for input fields with errors
        const errorInputs = this.$el.querySelectorAll(".v-input.error--text");
        console.log("Error inputs found:", errorInputs.length);

        errorInputs.forEach((input) => {
          const label = input.querySelector(".v-label");
          const messages = input.querySelector(".v-messages__message");
          if (label && messages) {
            const fieldName = label.innerText.replace("*", "").trim();
            const errorMsg = messages.innerText.trim();
            console.log(`Field: ${fieldName}, Error: ${errorMsg}`);
            if (!errorMessages.includes(`${fieldName}: ${errorMsg}`)) {
              errorMessages.push(`${fieldName}: ${errorMsg}`);
            }
          }
        });

        if (errorMessages.length > 0) {
          // Display errors in Vuetify alert
          this.validationErrors = errorMessages;
        } else {
          // No specific errors found, show generic message
          this.validationErrors = [
            "Please check all required fields and ensure they are filled correctly.",
          ];
        }

        // Scroll to top to show the error alert
        this.$nextTick(() => {
          const cardContent = this.$el.querySelector(".v-card__text");
          if (cardContent) {
            cardContent.scrollTop = 0;
          }

          // Also scroll to first error field
          const firstError = this.$el.querySelector(".error--text");
          if (firstError) {
            firstError.scrollIntoView({ behavior: "smooth", block: "center" });
          }
        });
      });
    },
    cancel() {
      window.location.href = "/assignments";
    },
    goToNextStep() {
      // Validate step 1 form - this will highlight invalid fields
      if (this.$refs.step1Form && this.$refs.step1Form.validate()) {
        this.currentStep = 2;
        // Load users for the selected department when moving to step 2
        this.loadUsersForDepartment();
      } else {
        // Validation failed - show errors
        this.showValidationErrors();
      }
    },
    goToPreviousStep() {
      if (this.currentStep >= 3) {
        // Going back from a child step
        if (this.currentChildIndex > 0) {
          // Go to previous child assignment
          this.currentChildIndex--;
          this.currentStep--;
          this.loadChildAssignmentData(
            this.childAssignmentsQueue[this.currentChildIndex].id
          );
        } else {
          // Go back to Step 2 (parent form)
          // Note: We don't reload parent data as it's already been saved
          alert(
            "Cannot go back to parent form after child assignments have been created. Use Cancel to exit."
          );
        }
      } else {
        // Going back from Step 2 to Step 1
        // Don't allow going back to Step 1 if editing
        if (this.isEdit) {
          // When editing, Step 2 is the first step, so cancel instead
          this.cancel();
        } else {
          this.currentStep = 1;
        }
      }
    },
    initializeNotes() {
      // Initialize notes array if not present
      if (!this.formData.notes) {
        this.formData.notes = [];
      }
    },
    addNote() {
      if (this.newNote.note && this.newNote.note.trim() && this.newNote.note_for) {
        if (!this.formData.notes) {
          this.formData.notes = [];
        }

        // Add note without metadata - backend will set created_by and timestamps
        // Set canEdit and canDelete to true for newly created notes so they can be edited/deleted
        this.formData.notes.push({
          note: this.newNote.note.trim(),
          note_for: this.newNote.note_for,
          canEdit: true,
          canDelete: true,
        });
        // Reset new note fields
        this.newNote = {
          note: "",
          note_for: null,
        };
        this.updateModel();
      }
    },

    startEditNote(index) {
      this.editingNoteIndex = index;
      this.editingNote = {
        note: this.formData.notes[index].note,
        note_for: this.formData.notes[index].note_for,
      };
    },

    saveEditNote(index) {
      if (
        this.editingNote.note &&
        this.editingNote.note.trim() &&
        this.editingNote.note_for
      ) {
        // Update note preserving ID so backend knows to update, not create
        // Backend will set updated_by and updated_at
        this.formData.notes[index] = {
          ...this.formData.notes[index],
          note: this.editingNote.note.trim(),
          note_for: this.editingNote.note_for,
        };
        this.cancelEditNote();
        this.updateModel();
      }
    },

    cancelEditNote() {
      this.editingNoteIndex = null;
      this.editingNote = {
        note: "",
        note_for: null,
      };
    },

    confirmDeleteNote(index) {
      this.noteToDelete = index;
      this.showDeleteNoteDialog = true;
    },

    deleteNote() {
      if (
        this.noteToDelete !== null &&
        this.formData.notes &&
        this.formData.notes.length > this.noteToDelete
      ) {
        this.formData.notes.splice(this.noteToDelete, 1);
        this.showDeleteNoteDialog = false;
        this.noteToDelete = null;
        this.$nextTick(() => {
          this.updateModel();
        });
      }
    },

    removeNote(index) {
      // Keep this for backward compatibility but redirect to confirmDeleteNote
      this.confirmDeleteNote(index);
    },

    linkifyText(text) {
      if (!text) return "";
      // Regular expression to detect URLs
      const urlRegex = /(https?:\/\/[^\s]+)/g;
      return text.replace(urlRegex, (url) => {
        return `<a href="${url}" target="_blank" rel="noopener noreferrer" style="color: #1976d2; text-decoration: underline;">${url}</a>`;
      });
    },

    getNoteForLabel(value) {
      const option = this.noteForOptions.find((opt) => opt.value === value);
      return option ? option.label : value;
    },
  },
  watch: {
    modelValue: {
      handler(newVal) {
        this.formData = { ...newVal };
        this.initializeClient();
        this.initializeNotes();
      },
      deep: true,
    },
    "formData.department_id": {
      handler() {
        this.loadUsersForDepartment();
      },
    },
  },
};
</script>
