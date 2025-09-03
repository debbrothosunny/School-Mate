<template>
  <Head title="Groups" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Groups Management
      </h2>
    </template>

    <div class="py-12 bg-gray-100 min-h-screen">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div
          class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6"
        >
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900">
              Group List
            </h3>
            <Link :href="route('groups.create')">
              <PrimaryButton>
                <template #icon>
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </template>
                Add New Group
              </PrimaryButton>
            </Link>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 rounded-lg">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Name
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Status
                  </th>
                  <th
                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="props.groups.data.length === 0">
                  <td
                    colspan="3"
                    class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"
                  >
                    No groups found.
                  </td>
                </tr>
                <tr
                  v-for="group in props.groups.data"
                  :key="group.id"
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ group.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span
                      :class="[
                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                        getStatusBadgeClass(group.status)
                      ]"
                    >
                      {{ getStatusString(group.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <Link
                      :href="route('groups.edit', group.id)"
                    >
                      <PrimaryButton>
                        <template #icon>
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                          >
                            <path
                              fill-rule="evenodd"
                              d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828zM2 15.25V18h2.75l8.336-8.336-2.75-2.75L2 15.25z"
                              clip-rule="evenodd"
                            />
                          </svg>
                        </template>
                        Edit
                      </PrimaryButton>
                    </Link>
                    <DangerButton @click="openDeleteModal(group)">
                      <template #icon>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="h-5 w-5"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M9 2a1 1 0 00-1 1v1H5a1 1 0 000 2h1v10a2 2 0 002 2h6a2 2 0 002-2V6h1a1 1 0 100-2h-3V3a1 1 0 00-1-1H9zm-1 5a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1zm6 0a1 1 0 011 1v6a1 1 0 11-2 0V8a1 1 0 011-1z"
                            clip-rule="evenodd"
                          />
                        </svg>
                      </template>
                      Delete
                    </DangerButton>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="props.groups.links.length > 3" class="mt-6 flex justify-center">
            <div class="flex flex-wrap -mb-1">
              <template v-for="(link, key) in props.groups.links" :key="key">
                <div
                  v-if="!link.url"
                  class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-lg"
                  v-html="link.label"
                />
                <Link
                  v-else
                  :href="link.url"
                  class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-lg hover:bg-gray-200 focus:border-indigo-500 focus:text-indigo-500"
                  :class="{ 'bg-gray-200 font-semibold': link.active }"
                  v-html="link.label"
                />
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
      <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-gray-900">
            Confirm Deletion
          </h3>
          <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
        <p class="mb-6 text-gray-700">
          Are you sure you want to permanently delete
          "<strong>{{ groupToDelete?.name }}</strong>"? This action
          cannot be undone.
        </p>
        <div class="flex justify-end space-x-3">
          <button
            @click="closeDeleteModal"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
          >
            Cancel
          </button>
          <DangerButton @click="deleteGroup">
            <template #icon>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M9 2a1 1 0 00-1 1v1H5a1 1 0 000 2h1v10a2 2 0 002 2h6a2 2 0 002-2V6h1a1 1 0 100-2h-3V3a1 1 0 00-1-1H9zm-1 5a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1zm6 0a1 1 0 011 1v6a1 1 0 11-2 0V8a1 1 0 011-1z"
                  clip-rule="evenodd"
                />
              </svg>
            </template>
            Confirm Delete
          </DangerButton>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

---

## Script

```javascript
<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import Swal from 'sweetalert2'

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'

// Props
const props = defineProps({
  groups: Object,
})

// Flash toast
const page = usePage()
const flash = computed(() => page.props.flash || {})

watch(
  flash,
  (newFlash) => {
    if (newFlash?.message) {
      Swal.fire({
        icon: newFlash.type === 'success' ? 'success' : 'error',
        title: newFlash.type === 'success' ? 'Success!' : 'Error!',
        text: newFlash.message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      })
    }
  },
  { deep: true }
)

// Delete modal & form
const deleteForm = useForm()
const showDeleteModal = ref(false)
const groupToDelete = ref(null)

const openDeleteModal = (group) => {
  groupToDelete.value = group
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  groupToDelete.value = null
}

const deleteGroup = () => {
  if (!groupToDelete.value) return

  deleteForm.delete(
    route('groups.destroy', groupToDelete.value.id),
    {
      preserveScroll: true,
      onSuccess: closeDeleteModal,
    }
  )
}

// Status helpers
const getStatusString = (status) =>
  status ? 'Inactive' : 'Active'

const getStatusBadgeClass = (status) =>
  status
    ? 'bg-red-100 text-red-800'
    : 'bg-green-100 text-green-800'
</script>

<style scoped>
.font-inter {
  font-family: 'Inter', sans-serif;
}
</style>