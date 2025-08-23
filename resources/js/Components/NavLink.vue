<!-- resources/js/Components/NavLink.vue -->
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false, // Ensure a default value
    },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center w-full px-3 py-2 text-sm font-medium leading-5 rounded-md focus:outline-none transition duration-150 ease-in-out ' +
          'text-gray-900 bg-gray-100 dark:bg-gray-700 dark:text-gray-100' // Active state classes
        : 'inline-flex items-center w-full px-3 py-2 text-sm font-medium leading-5 rounded-md hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:text-gray-900 focus:bg-gray-100 transition duration-150 ease-in-out ' +
          'text-gray-600 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700' // Inactive state classes
);
</script>

<template>


    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>