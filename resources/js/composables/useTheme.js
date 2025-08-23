// resources/js/Composables/useTheme.js

import { ref, onMounted, watch } from 'vue';

export function useTheme() {
    const isDark = ref(false);

    const toggleTheme = () => {
        isDark.value = !isDark.value;
    };

    onMounted(() => {
        // Check local storage for a saved theme preference
        const savedTheme = localStorage.getItem('color-theme');

        if (savedTheme) {
            isDark.value = savedTheme === 'dark';
        } else {
            // Check the user's system preference
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        // Apply the class immediately on mount to prevent FOUC
        // This is crucial for when the Vue app hydrates
        document.documentElement.classList.toggle('dark', isDark.value);
    });

    // Watch for changes in isDark and update the HTML class and local storage
    watch(isDark, (newValue) => {
        if (newValue) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        }
    });

    return { isDark, toggleTheme };
}
