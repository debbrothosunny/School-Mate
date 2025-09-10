<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-gray-100 relative overflow-hidden">
    <Head :title="showRegisterForm ? 'Create Account' : 'Log In'" />

    <div class="absolute inset-0 z-0 bg-cover bg-center transition-opacity duration-1000"
         :class="{ 'opacity-100': true }"
         style="background-image: url('https://images.unsplash.com/photo-1549490349-864336284764?fit=crop&w=1974&q=80');">
    </div>

    <div class="relative z-20 w-full max-w-lg md:max-w-xl bg-white rounded-2xl shadow-3xl p-8 md:p-12 text-gray-800 border border-gray-200 transform transition-transform duration-500 ease-in-out">
      
      <div class="flex flex-col items-center mb-8">
        <div class="w-24 h-24 rounded-full mb-4 shadow-lg animate-fade-in-down overflow-hidden flex items-center justify-center bg-white">
          <img src="/images/logo.jpeg" alt="Your Company Logo" class="max-w-full max-h-full object-contain" />
        </div>
        <h2 class="text-4xl font-extrabold text-gray-800 text-center mb-2 animate-fade-in-up">
          {{ showRegisterForm ? 'Create Your Account' : 'Welcome Back' }}
        </h2>
        <p class="text-gray-500 text-center animate-fade-in-up delay-200">
          {{ showRegisterForm ? 'Join us and start your educational journey.' : 'Log in to continue your adventure.' }}
        </p>
      </div>

      <div v-if="status" class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
        {{ status }}
      </div>

      <transition name="fade" mode="out-in">
        <form v-if="!showRegisterForm" @submit.prevent="submitLogin" key="login-form" class="space-y-6">
          <div>
            <InputLabel for="login_field" value="Email or Contact Number" class="text-gray-700" />
            <div class="relative mt-1">
              <TextInput
                id="login_field"
                type="text"
                class="block w-full rounded-lg border-gray-200 bg-gray-50 pr-10 text-gray-800 placeholder-gray-400 focus:ring-teal-500"
                v-model="loginForm.login_field"
                required
                autofocus
                autocomplete="username"
                placeholder="Enter your email or contact number"
              />
              <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                <i class="fas fa-user"></i>
              </span>
            </div>
            <InputError class="mt-1" :message="loginForm.errors.login_field" />
          </div>
          
          <div>
            <div class="flex items-center justify-between">
              <InputLabel for="password" value="Password" class="text-gray-700" />
              <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-teal-600 hover:text-teal-500 transition-colors">
                Forgot password?
              </Link>
            </div>
            <div class="relative mt-1">
              <TextInput
                id="password"
                :type="passwordFieldType"
                class="block w-full rounded-lg border-gray-200 bg-gray-50 pr-10 text-gray-800 placeholder-gray-400 focus:ring-teal-500"
                v-model="loginForm.password"
                required
                autocomplete="current-password"
              />
              <button type="button" @click="togglePasswordVisibility" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 focus:outline-none">
                <i :class="passwordFieldType === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash'"></i>
              </button>
            </div>
            <InputError class="mt-1" :message="loginForm.errors.password" />
          </div>
          
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <Checkbox name="remember" v-model:checked="loginForm.remember" class="form-checkbox text-teal-600 bg-gray-200 border-gray-300 rounded" />
              <label class="ms-2 text-sm text-gray-600">Remember me</label>
            </div>
          </div>
          
          <PrimaryButton
            class="w-full justify-center py-3 rounded-full bg-teal-600 hover:bg-teal-700 transition-colors duration-300 shadow-md transform hover:scale-105"
            :class="{ 'opacity-25': loginForm.processing }"
            :disabled="loginForm.processing"
          >
            Log In
          </PrimaryButton>

          <div class="text-center mt-4">
            <p class="text-sm text-gray-600">
              Don't have an account?
              <button @click.prevent="toggleForm" class="font-medium text-teal-600 hover:text-teal-500 transition-colors">
                Register now
              </button>
            </p>
          </div>
        </form>

        <form v-else @submit.prevent="submitRegister" key="register-form" class="space-y-6">
          <div>
            <InputLabel for="name" value="Full Name" class="text-gray-700" />
            <div class="relative mt-1">
              <TextInput
                id="name"
                type="text"
                class="block w-full rounded-lg border-gray-200 bg-gray-50 pr-10 text-gray-800 placeholder-gray-400 focus:ring-teal-500"
                v-model="registerForm.name"
                required
                autofocus
                autocomplete="name"
                placeholder="Enter your full name"
              />
              <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                <i class="fas fa-user"></i>
              </span>
            </div>
            <InputError class="mt-1" :message="registerForm.errors.name" />
          </div>
          
          <div>
            <InputLabel for="register_email" value="Email Address" class="text-gray-700" />
            <div class="relative mt-1">
              <TextInput
                id="register_email"
                type="email"
                class="block w-full rounded-lg border-gray-200 bg-gray-50 pr-10 text-gray-800 placeholder-gray-400 focus:ring-teal-500"
                v-model="registerForm.email"
                required
                autocomplete="username"
                placeholder="john.teacher@example.com"
              />
              <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                <i class="fas fa-envelope"></i>
              </span>
            </div>
            <InputError class="mt-1" :message="registerForm.errors.email || realTimeErrors.email" />
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel for="register_password" value="Password 8(Digit)" class="text-gray-700" />
              <div class="relative mt-1">
                <TextInput
                  id="register_password"
                  :type="passwordFieldType"
                  class="block w-full rounded-lg border-gray-200 bg-gray-50 pr-10 text-gray-800 placeholder-gray-400 focus:ring-teal-500"
                  v-model="registerForm.password"
                  required
                  autocomplete="new-password"
                  minlength="8"
                />
                <button type="button" @click="togglePasswordVisibility" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 focus:outline-none">
                  <i :class="passwordFieldType === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash'"></i>
                </button>
              </div>
              <InputError class="mt-1" :message="registerForm.errors.password || (registerForm.password && registerForm.password.length < 8 ? 'Password must be at least 8 characters long.' : '')" />
            </div>
            
            <div>
              <InputLabel for="password_confirmation" value="Confirm Password" class="text-gray-700" />
              <div class="relative mt-1">
                <TextInput
                  id="password_confirmation"
                  :type="passwordFieldType"
                  class="block w-full rounded-lg border-gray-200 bg-gray-50 pr-10 text-gray-800 placeholder-gray-400 focus:ring-teal-500"
                  v-model="registerForm.password_confirmation"
                  required
                  autocomplete="new-password"
                  minlength="8"
                />
                <button type="button" @click="togglePasswordVisibility" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 focus:outline-none">
                  <i :class="passwordFieldType === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash'"></i>
                </button>
              </div>
              <InputError class="mt-1" :message="registerForm.errors.password_confirmation || (registerForm.password_confirmation && registerForm.password !== registerForm.password_confirmation ? 'Password confirmation does not match.' : '')" />
            </div>
          </div>
          
          <div>
            <InputLabel for="contact_info" value="Contact Information" class="text-gray-700" />
            <div class="relative mt-1">
              <TextInput
                id="contact_info"
                type="text"
                class="block w-full rounded-lg border-gray-200 bg-gray-50 pr-10 text-gray-800 placeholder-gray-400 focus:ring-teal-500"
                v-model="registerForm.contact_info"
                required
                autocomplete="tel"
                placeholder="Phone number or other contact"
              />
              <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                <i class="fas fa-phone"></i>
              </span>
            </div>
            <InputError class="mt-1" :message="registerForm.errors.contact_info || realTimeErrors.contact_info" />
          </div>
          
          <PrimaryButton
            class="w-full justify-center py-3 rounded-full bg-teal-600 hover:bg-teal-700 transition-colors duration-300 shadow-md transform hover:scale-105"
            :class="{ 'opacity-25': registerForm.processing }"
            :disabled="registerForm.processing || Object.keys(realTimeErrors).length > 0 || registerForm.password.length < 8 || registerForm.password !== registerForm.password_confirmation"
          >
            Create Account
          </PrimaryButton>
          
          <div class="text-center mt-4">
            <p class="text-sm text-gray-600">
              Already have an account?
              <button @click.prevent="toggleForm" class="font-medium text-teal-600 hover:text-teal-500 transition-colors">
                Log In
              </button>
            </p>
          </div>
        </form>
      </transition>
    </div>
  </div>
</template>

<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';

defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});

const showRegisterForm = ref(false);
const passwordFieldType = ref('password');

const loginForm = useForm({
  login_field: '',
  password: '',
  remember: false,
});

const registerForm = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  contact_info: '',
});

const realTimeErrors = ref({});

const checkContactExists = debounce(async () => {
  realTimeErrors.value = {}; 
  try {
    await axios.post(route('check.contact'), {
      email: registerForm.email,
      contact_info: registerForm.contact_info,
    });
  } catch (error) {
    if (error.response && error.response.status === 422) {
      realTimeErrors.value = error.response.data.errors;
    } else {
      console.error('An error occurred during real-time validation:', error);
    }
  }
}, 500);

const togglePasswordVisibility = () => {
  passwordFieldType.value = passwordFieldType.value === 'password' ? 'text' : 'password';
};

watch(() => registerForm.email, () => {
  if (registerForm.email) {
    checkContactExists();
  }
});
watch(() => registerForm.contact_info, () => {
  if (registerForm.contact_info) {
    checkContactExists();
  }
});

const submitLogin = () => {
  loginForm.post(route('login'), {
    onFinish: () => loginForm.reset('password'),
  });
};

const submitRegister = () => {
  registerForm.post(route('register'), {
    onFinish: () => registerForm.reset('password', 'password_confirmation'),
  });
};

const toggleForm = () => {
  showRegisterForm.value = !showRegisterForm.value;
  loginForm.reset();
  registerForm.reset();
  realTimeErrors.value = {};
};
</script>

<style scoped>
/* Scoped styles for transition and animations */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom checkbox styling for light background */
:deep(.form-checkbox:checked) {
  background-color: #0d9488; /* Teal-600 */
  border-color: #0d9488;
  box-shadow: none;
}

:deep(.form-checkbox) {
  appearance: none;
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 0.25rem;
  border: 1px solid #d1d5db; /* gray-300 */
  background-color: #e5e7eb; /* gray-200 */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

:deep(.form-checkbox:checked) {
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
  background-size: 100% 100%;
  background-position: center;
  background-repeat: no-repeat;
}

/* Animations */
@keyframes fade-in-down {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

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

.animate-fade-in-down {
  animation: fade-in-down 0.6s ease-out forwards;
}

.animate-fade-in-up {
  animation: fade-in-up 0.6s ease-out forwards;
  animation-delay: 0.2s;
}

.delay-200 {
  animation-delay: 0.4s !important;
}

.shadow-3xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2), 0 0 100px 5px rgba(0, 0, 0, 0.1);
}
</style>