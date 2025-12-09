<script setup>
// Imports for the Profile Information Form
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

// Imports for the Password Update Form
import { ref } from 'vue';

// Props inherited from the parent page component
defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

// Get the user data from the Inertia page props
const user = usePage().props.auth.user;

// --- Profile Form ---
const profileForm = useForm({
    name: user.name,
    // email removed
});

// --- Password Form ---
const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Show/hide toggle refs
const showCurrentPassword = ref(true);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// Function to handle the profile information update
const updateProfileInformation = () => {
    profileForm.patch(route('profile.update'));
};

// Function to handle the password update
const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showCurrentPassword.value = false;
            showNewPassword.value = false;
            showConfirmPassword.value = false;
        },
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
  <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <h1 class="text-5xl font-extrabold text-gray-900 tracking-tight mb-8 text-center">Your Account</h1>
    <p class="text-center text-lg text-gray-600 mb-12">Manage your profile information and password in one place.</p>

    <div class="bg-white p-8 sm:p-12 rounded-3xl shadow-2xl border border-gray-100 space-y-12 animate-fade-in-up">

      <div class="space-y-6">
        <header>
          <h2 class="text-3xl font-bold text-gray-800 tracking-tight mb-2">Profile Details</h2>
          <p class="text-md text-gray-500">Update your account's name.</p>
        </header>

        <form @submit.prevent="updateProfileInformation" class="space-y-6">
          <!-- Name -->
          <div>
            <InputLabel for="name" value="Name" class="text-gray-700 font-semibold" />
            <TextInput id="name" type="text" class="mt-1 block w-full"
              v-model="profileForm.name" required autofocus autocomplete="name" />
            <InputError class="mt-2" :message="profileForm.errors.name" />
          </div>

          <!-- Email field removed -->

          <!-- Save -->
          <div class="flex items-center gap-4">
            <PrimaryButton :disabled="profileForm.processing">Save Profile</PrimaryButton>
            <Transition enter-active-class="transition ease-in-out duration-300"
                        enter-from-class="opacity-0 translate-y-1"
                        leave-active-class="transition ease-in-out duration-300"
                        leave-to-class="opacity-0 translate-y-1">
              <p v-if="profileForm.recentlySuccessful"
                 class="text-sm text-green-600 font-semibold flex items-center gap-1">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 
                     0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 
                     0 00-1.414 1.414l2 2a1 1 0 
                     001.414 0l4-4z" clip-rule="evenodd" /></svg>
                Saved!
              </p>
            </Transition>
          </div>
        </form>
      </div>

      <hr class="border-t border-gray-200" />

      <!-- Update Password -->
      <div class="space-y-6">
        <header>
          <h2 class="text-3xl font-bold text-gray-800 tracking-tight mb-2">Update Password</h2>
          <p class="text-md text-gray-500">Ensure your account is using a long, random password to stay secure.Minimum (8 Characters)</p>
        </header>

        <!-- Password update form as before without change -->

        <form @submit.prevent="updatePassword" class="space-y-6">
          <!-- Current Password -->
          <div>
            <InputLabel for="current_password" value="Current Password" class="text-gray-700 font-semibold" />
            <div class="relative">
              <input
                :type="showCurrentPassword ? 'text' : 'password'"
                id="current_password"
                ref="currentPasswordInput"
                v-model="passwordForm.current_password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10"
                autocomplete="current-password"
              />
              <button
                type="button"
                @click="showCurrentPassword = !showCurrentPassword"
                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700"
              >
                <i :class="showCurrentPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
              </button>
            </div>
            <InputError :message="passwordForm.errors.current_password" class="mt-2" />
          </div>

          <!-- New Password -->
          <div>
            <InputLabel for="password" value="New Password" class="text-gray-700 font-semibold" />
            <div class="relative">
              <input
                :type="showNewPassword ? 'text' : 'password'"
                id="password"
                v-model="passwordForm.password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10"
                autocomplete="new-password"
              />
              <button type="button" @click="showNewPassword = !showNewPassword"
                      class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700">
                <i :class="showNewPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
              </button>
            </div>
            <InputError :message="passwordForm.errors.password" class="mt-2" />
          </div>

          <!-- Confirm Password -->
          <div>
            <InputLabel for="password_confirmation" value="Confirm Password" class="text-gray-700 font-semibold" />
            <div class="relative">
              <input
                :type="showConfirmPassword ? 'text' : 'password'"
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10"
                autocomplete="new-password"
              />
              <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                      class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700">
                <i :class="showConfirmPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
              </button>
            </div>
            <InputError :message="passwordForm.errors.password_confirmation" class="mt-2" />
          </div>

          <!-- Save -->
          <div class="flex items-center gap-4">
            <PrimaryButton :disabled="passwordForm.processing">Save Password</PrimaryButton>
            <Transition enter-active-class="transition ease-in-out duration-300"
                        enter-from-class="opacity-0 translate-y-1"
                        leave-active-class="transition ease-in-out duration-300"
                        leave-to-class="opacity-0 translate-y-1">
              <p v-if="passwordForm.recentlySuccessful"
                 class="text-sm text-green-600 font-semibold flex items-center gap-1">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 
                     0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 
                     0 00-1.414 1.414l2 2a1 1 0 
                     001.414 0l4-4z" clip-rule="evenodd" /></svg>
                Saved!
              </p>
            </Transition>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out forwards;
}
</style>
