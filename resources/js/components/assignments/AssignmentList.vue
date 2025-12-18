<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            Assignments
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="$emit('create')">Create Assignment</v-btn>
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="assignments"
              :loading="loading"
              class="elevation-1"
            >
              <template v-slot:item.status="{ item }">
                <v-chip :color="getStatusColor(item.status)" small>
                  {{ item.status }}
                </v-chip>
              </template>
              <template v-slot:item.days_remaining="{ item }">
                <span :class="getDaysRemainingClass(item.days_remaining)">
                  {{ item.days_remaining !== null ? item.days_remaining + ' days' : 'N/A' }}
                </span>
              </template>
              <template v-slot:item.actions="{ item }">
                <v-btn small color="primary" @click="$emit('view', item.id)">View</v-btn>
                <v-btn small color="secondary" @click="$emit('edit', item.id)">Edit</v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
export default {
  name: 'AssignmentList',
  props: {
    assignments: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      headers: [
        { text: 'ID', value: 'id' },
        { text: 'Department', value: 'department.name' },
        { text: 'Song Name', value: 'song_name' },
        { text: 'Assigned To', value: 'assigned_to.name' },
        { text: 'Completion Date', value: 'completion_date' },
        { text: 'Days Remaining', value: 'days_remaining' },
        { text: 'Status', value: 'status' },
        { text: 'Actions', value: 'actions', sortable: false }
      ]
    }
  },
  methods: {
    getStatusColor(status) {
      const colors = {
        'pending': 'grey',
        'in-progress': 'blue',
        'completed': 'green',
        'on-hold': 'orange'
      };
      return colors[status] || 'grey';
    },
    getDaysRemainingClass(days) {
      if (days === null) return '';
      if (days < 0) return 'red--text';
      if (days <= 3) return 'orange--text';
      return 'green--text';
    }
  }
}
</script>

