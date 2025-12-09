<script setup>
import { defineProps, computed, watchEffect } from 'vue';

// ====================== PROPS ======================
const props = defineProps({
  student: {
    type: Object,
    required: true,
  },
  examHistory: {
    type: Array,
    default: () => [],
  },
  invoiceHistory: {
    type: Array,
    default: () => [],
  },   
});

// ====================== GROUPING ======================
const groupedExamHistory = computed(() => {
  if (!Array.isArray(props.examHistory) || props.examHistory.length === 0) {
    return {};
  }

  return props.examHistory.reduce((groups, exam) => {
    const className = exam.class_name ?? 'Unknown Class';
    const sessionName = exam.session_name ?? 'Unknown Session';

    // Initialize class
    if (!groups[className]) {
      groups[className] = {};
    }
    // Initialize session
    if (!groups[className][sessionName]) {
      groups[className][sessionName] = [];
    }
    // Push exam
    groups[className][sessionName].push(exam);

    return groups;
  }, {});
});

// ====================== DEBUG ======================
watchEffect(() => {
  console.log('Student:', props.student);
  console.log('Exam History (raw):', props.examHistory);
  console.log('Grouped Exam History:', groupedExamHistory.value);
  console.log('Invoice History:', props.invoiceHistory);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">

      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
        <h1 class="text-3xl font-bold text-gray-900">
          Student History: <span class="text-indigo-600">{{ student.name }}</span>
        </h1>
        <p class="text-sm text-gray-500 mt-1">Detailed academic and financial records</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white p-6">
          <h2 class="text-2xl font-bold">Exam History</h2>
          <p class="text-sm opacity-90">All published results with subject breakdown</p>
        </div>

        <div class="p-6">
          <div
            v-if="!examHistory.length || Object.keys(groupedExamHistory).length === 0"
            class="text-center py-12"
          >
            <div class="bg-gray-200 border-2 border-dashed rounded-xl w-16 h-16 mx-auto mb-4"></div>
            <p class="text-gray-500 text-lg">No exam history available.</p>
          </div>

          <div v-else class="space-y-10">
            <div
              v-for="(sessions, className) in groupedExamHistory"
              :key="className"
              class="border-l-4 border-indigo-500 pl-4"
            >
              <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-2"></span>
                {{ className }}
              </h3>

              <div
                v-for="(exams, sessionName) in sessions"
                :key="sessionName"
                class="mb-8 bg-gray-50 rounded-lg p-5 shadow-sm"
              >
                <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                  <span class="w-1.5 h-1.5 bg-purple-500 rounded-full mr-2"></span>
                  {{ sessionName }}
                </h4>

                <div
                  v-for="exam in exams"
                  :key="exam.id"
                  class="mb-6 bg-white rounded-lg border border-gray-200 overflow-hidden"
                >
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                      <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                          Exam
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                          Marks
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                          Grade
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr class="hover:bg-indigo-50 transition-colors">
                        <td class="px-6 py-4 text-sm">
                          <div class="font-bold text-gray-900">{{ exam.exam_name || 'N/A' }}</div>
                          <div class="text-xs text-gray-500 mt-1">
                            Published: {{ exam.published_at ? new Date(exam.published_at).toLocaleDateString() : '—' }}
                          </div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                          <div class="font-semibold text-gray-900">
                            {{ exam.total_marks_obtained ?? '—' }} / {{ exam.total_possible_marks ?? '—' }}
                          </div>
                          <div class="text-xs text-green-600 font-medium">
                            {{ exam.percentage ? exam.percentage + '%' : '' }}
                          </div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                          <div class="text-2xl font-bold text-indigo-600">
                            {{ exam.final_letter_grade || 'N/A' }}
                          </div>
                          <div class="text-xs text-gray-500">
                            GP: {{ exam.final_grade_point ?? '—' }}
                          </div>
                        </td>
                      </tr>

                      <tr v-if="exam.subject_wise_data && exam.subject_wise_data.length > 0">
                        <td colspan="3" class="p-0">
                          <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-4 border-t border-gray-200">
                            <p class="font-bold text-gray-700 mb-3 text-sm flex items-center">
                              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                              </svg>
                              Subject-wise Marks
                            </p>
                            <div class="overflow-x-auto">
                              <table class="min-w-full text-xs">
                                <thead>
                                  <tr class="border-b border-gray-300">
                                    <th class="text-left py-2 font-bold text-gray-700">Subject</th>
                                    <th class="text-center py-2 font-bold text-gray-700">CT</th>
                                    <th class="text-center py-2 font-bold text-gray-700">Assign</th>
                                    <th class="text-center py-2 font-bold text-gray-700">Exam</th>
                                    <th class="text-center py-2 font-bold text-gray-700">Attn</th>
                                    <th class="text-center py-2 font-bold text-indigo-600">Total</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <tr
                                    v-for="sub in exam.subject_wise_data"
                                    :key="sub.subject"
                                    class="border-b border-gray-200 hover:bg-indigo-50"
                                  >
                                    <td class="py-2 font-medium text-gray-800">{{ sub.subject }}</td>
                                    <td class="text-center">{{ sub.class_test ?? '-' }}</td>
                                    <td class="text-center">{{ sub.assignment ?? '-' }}</td>
                                    <td class="text-center">{{ sub.exam ?? '-' }}</td>
                                    <td class="text-center text-green-600">{{ sub.attendance ?? '0' }}</td>
                                    <td class="text-center font-bold text-indigo-600">{{ sub.total }}</td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-teal-600 text-white p-6">
          <h2 class="text-2xl font-bold">Invoice History</h2>
          <p class="text-sm opacity-90">All billing records</p>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                  Invoice #
                </th>
                
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                  Billing Period
                </th>
                
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                  Paid Amount
                </th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                  Balance Due
                </th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                  Issued
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="!invoiceHistory || invoiceHistory.length === 0">
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                  <div class="bg-gray-200 border-2 border-dashed rounded-xl w-12 h-12 mx-auto mb-3"></div>
                  No invoice history available.
                </td>
              </tr>

              <tr
                v-else
                v-for="invoice in invoiceHistory"
                :key="invoice.id"
                class="hover:bg-green-50 transition-colors"
              >
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                  {{ invoice.invoice_number }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ invoice.billing_period }}
                </td>
                
                
                
                <td class="px-6 py-4 text-sm font-medium text-green-600">
                  ৳{{ invoice.total_paid_amount }}
                </td>

                <td 
                  class="px-6 py-4 text-sm font-semibold"
                  :class="invoice.remaining_balance > 0 ? 'text-red-700' : 'text-gray-900'"
                >
                  ৳{{ invoice.remaining_balance }}
                </td>

                <td class="px-6 py-4">
                  <span
                    :class="[
                      'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                      invoice.status === 'Paid' ? 'bg-green-100 text-green-800' :
                      invoice.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ invoice.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                  {{ new Date(invoice.issued_at).toLocaleDateString() }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>