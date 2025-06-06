<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue'; // Import ref for reactive state

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

// Reactive state to toggle between login and register forms
const showRegisterForm = ref(false);

// Form for Login
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

// Form for Registration
const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    contact_info: '', 
});

// Submit function for Login
const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
};

// Submit function for Registration
const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};

// Function to toggle between forms
const toggleForm = () => {
    showRegisterForm.value = !showRegisterForm.value;
    // Optionally reset forms when switching
    loginForm.reset();
    registerForm.reset();
};
</script>

<template>
    <GuestLayout>
        <Head :title="showRegisterForm ? 'Register' : 'Log in'" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div class="relative w-full overflow-hidden">
            <!-- School-Mate Title and Logo Illustration -->
            <div class="flex flex-col items-center justify-center mb-8">
                <!-- Placeholder for Logo Illustration -->
                <svg
                    class="w-24 h-24 text-indigo-600 mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 14l9-5-9-5-9 5 9 5z"
                    ></path>
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 14v4l9-5-9-5-9 5 9 5z"
                    ></path>
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 14v4l-9-5 9-5 9 5z"
                    ></path>
                </svg>
                <h1 class="text-4xl font-extrabold text-gray-900 mb-2">School-Mate</h1>
                <p class="text-gray-600 text-lg">Your Partner in Education</p>
            </div>

            <!-- Form Toggle Buttons -->
            <div class="flex justify-center mb-6">
                <button
                    @click="toggleForm"
                    :class="{ 'bg-indigo-600 text-white': !showRegisterForm, 'bg-gray-200 text-gray-700': showRegisterForm }"
                    class="px-6 py-3 rounded-l-lg transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Log In
                </button>
                <button
                    @click="toggleForm"
                    :class="{ 'bg-indigo-600 text-white': showRegisterForm, 'bg-gray-200 text-gray-700': !showRegisterForm }"
                    class="px-6 py-3 rounded-r-lg transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Register
                </button>
            </div>

            <!-- Forms Container with Transition -->
            <transition name="slide-fade" mode="out-in">
                <!-- Login Form -->
                <form v-if="!showRegisterForm" @submit.prevent="submitLogin" key="login-form" class="p-4">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="loginForm.email"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <InputError class="mt-2" :message="loginForm.errors.email" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="password" value="Password" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="loginForm.password"
                            required
                            autocomplete="current-password"
                        />
                        <InputError class="mt-2" :message="loginForm.errors.password" />
                    </div>

                    <div class="mt-4 block">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="loginForm.remember" />
                            <span class="ms-2 text-sm text-gray-600">Remember me</span>
                        </label>
                    </div>

                    <div class="mt-4 flex items-center justify-end">
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Forgot your password?
                        </Link>

                        <PrimaryButton
                            class="ms-4"
                            :class="{ 'opacity-25': loginForm.processing }"
                            :disabled="loginForm.processing"
                        >
                            Log in
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Registration Form -->
                <form v-else @submit.prevent="submitRegister" key="register-form" class="p-4">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="registerForm.name"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <InputError class="mt-2" :message="registerForm.errors.name" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="register_email" value="Email" />
                        <TextInput
                            id="register_email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="registerForm.email"
                            required
                            autocomplete="username"
                            placeholder="john.teacher@gmail.com" 
                        />
                        <InputError class="mt-2" :message="registerForm.errors.email" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="register_password" value="Password" />
                        <TextInput
                            id="register_password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="registerForm.password"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="registerForm.errors.password" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="registerForm.password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="registerForm.errors.password_confirmation" />
                    </div>

                    <!-- New: Contact Info Field -->
                    <div class="mt-4">
                        <InputLabel for="contact_info" value="Contact Info" />
                        <TextInput
                            id="contact_info"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="registerForm.contact_info"
                            required
                            autocomplete="tel"
                        />
                        <InputError class="mt-2" :message="registerForm.errors.contact_info" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <PrimaryButton
                            :class="{ 'opacity-25': registerForm.processing }"
                            :disabled="registerForm.processing"
                        >
                            Register
                        </PrimaryButton>
                    </div>
                </form>
            </transition>
        </div>
    </GuestLayout>
</template>

<style scoped>
/* Basic slide-fade transition for forms */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.6s ease-out;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}
</style>
