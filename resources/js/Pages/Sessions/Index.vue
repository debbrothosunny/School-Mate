<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';

import Swal from 'sweetalert2';


import { ref, computed, watch } from 'vue';

const props = defineProps({
  sessions: Object,
  filters: Object,
  flash: Object
});

const flash = computed(() => usePage().props.flash || {});

const searchQuery  = ref(props.filters.search || '');
const statusFilter = ref(
  props.filters.status !== undefined ? props.filters.status : ''
);

const showDeleteModal = ref(false);
const sessionToDelete = ref(null);

const form = useForm({});

// trigger search/status on change
watch([searchQuery, statusFilter], ([q, s]) => {
  router.get(route('sessions.index'), { search: q, status: s }, {
    preserveState: true,
    replace: true
  });
});

// clear both filters
function clearFilters() {
  if (!searchQuery.value && statusFilter.value === '') return;
  searchQuery.value = '';
  statusFilter.value = '';
}

// delete flow
function confirmDelete(session) {
  sessionToDelete.value = session;
  showDeleteModal.value = true;
}
function closeDeleteModal() {
  showDeleteModal.value = false;
  sessionToDelete.value = null;
}
function deleteSession() {
  if (!sessionToDelete.value) return;
  form.delete(route('sessions.destroy', sessionToDelete.value.id), {
    preserveScroll: true,
    onSuccess: closeDeleteModal,
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Deletion Failed!',
        text: 'Please try again.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
      });
      closeDeleteModal();
    }
  });
}

// flash-to-toast
watch(() => flash.value, f => {
  if (f.message) {
    Swal.fire({
      icon: f.type === 'success' ? 'success' : 'error',
      title: f.type === 'success' ? 'Success!' : 'Error!',
      text: f.message,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });
  }
}, { deep: true });

const getStatusText = s => s === 0 ? 'Active' : 'Inactive';
const getStatusBadge = s => s === 0 ? 'badge bg-success' : 'badge bg-danger';
</script>

<template>
  <Head title="Session Management" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="h4 mb-0">Session Management</h2>
    </template>

    <div class="container my-4">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- header + add -->
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">All Sessions</h5>
            <Link
              :href="route('sessions.create')"
              class="btn btn-primary btn-sm d-flex align-items-center"
            >
              <i class="fas fa-plus me-1"></i> Add Session
            </Link>
          </div>

          <!-- filters + clear -->
          <div class="row g-2 mb-4">
            <!-- search -->
            <div class="col-md-5">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0">
                  <i class="fas fa-search text-muted"></i>
                </span>
                <TextInput
                  v-model="searchQuery"
                  class="form-control border-start-0"
                  placeholder="Search sessions..."
                />
              </div>
            </div>

            <!-- status -->
            <div class="col-md-3">
              <select
                v-model="statusFilter"
                class="form-select form-select-sm"
              >
                <option value="">All Statuses</option>
                <option value="0">Active</option>
                <option value="1">Inactive</option>
              </select>
            </div>

            <!-- clear -->
            <div class="col-md-2">
              <button
                class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-center"
                @click="clearFilters"
                :disabled="!searchQuery && statusFilter === ''"
              >
                <i class="fas fa-times me-1"></i> Clear
              </button>
            </div>
          </div>

          <!-- sessions table -->
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!sessions.data.length">
                  <td colspan="3" class="text-center py-4 text-muted">
                    No sessions found.
                  </td>
                </tr>
                <tr v-for="sess in sessions.data" :key="sess.id">
                  <td>{{ sess.name }}</td>
                  <td>
                    <span :class="getStatusBadge(sess.status)">
                      {{ getStatusText(sess.status) }}
                    </span>
                  </td>
                  <td class="text-end">
                    <Link
                      :href="route('sessions.edit', sess.id)"
                      class="btn btn-outline-info btn-sm me-1"
                    >
                      <i class="fas fa-edit"></i>
                    </Link>
                    <button
                      @click="confirmDelete(sess)"
                      class="btn btn-outline-danger btn-sm"
                    >
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- pagination -->
          <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4"
            v-if="sessions.data.length"
          >
            <small class="text-muted">
              Showing {{ sessions.from }}–{{ sessions.to }} of {{ sessions.total }}
            </small>
            <Pagination :links="sessions.links" />
          </div>

        </div>
      </div>
    </div>

    <!-- delete modal -->
    <div
      class="modal fade show"
      style="display: block; background: rgba(0,0,0,0.4)"
      v-if="showDeleteModal"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Deletion</h5>
            <button class="btn-close" @click="closeDeleteModal"></button>
          </div>
          <div class="modal-body">
            Delete "<strong>{{ sessionToDelete?.name }}</strong>"? This cannot be undone.
          </div>
          <div class="modal-footer">
            <button class="btn btn-light btn-sm" @click="closeDeleteModal">
              Cancel
            </button>
            <button class="btn btn-danger btn-sm" @click="deleteSession">
              <i class="fas fa-trash-alt me-1"></i> Delete
            </button>
          </div>
        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>

<style scoped>
.modal.show {
  display: block;
}
</style>
