<template>
  <div class="relative inline-block text-left" ref="dropdownRef">
    <!-- Notification button with unread count badge -->
    <button 
      @click="toggleDropdown"
      class="relative p-2 rounded-full text-gray-400 hover:text-white hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white transition-colors duration-200"
      aria-label="Show notifications"
    >
      <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 2C6.486 2 2 6.486 2 12c0 5.515 4.486 10 10 10s10-4.485 10-10c0-5.514-4.486-10-10-10zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8zm4-9h-1.5c0-1.654-1.346-3-3-3s-3 1.346-3 3H8v-1.5c0-.828.672-1.5 1.5-1.5s1.5.672 1.5 1.5v1.5H12c.828 0 1.5.672 1.5 1.5s-.672 1.5-1.5 1.5h-1.5v-1.5c0-.828-.672-1.5-1.5-1.5s-1.5.672-1.5 1.5v1.5H8c-.828 0-1.5-.672-1.5-1.5s.672-1.5 1.5-1.5z"></path>
      </svg>
      <!-- Unread count badge -->
      <span v-if="unreadCount > 0" class="absolute top-0 right-0 inline-flex items-center justify-center h-5 w-5 rounded-full bg-red-500 text-white text-xs font-bold ring-2 ring-white transform translate-x-1/4 -translate-y-1/4">
        {{ unreadCount }}
      </span>
    </button>

    <!-- Dropdown panel -->
    <div v-if="isDropdownOpen" class="origin-top-right absolute right-0 mt-2 w-80 rounded-lg shadow-xl bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
      <div class="py-2">
        <!-- Conditional message if there are no notifications -->
        <p v-if="notifications.length === 0" class="text-gray-500 text-sm p-4">No notifications yet.</p>
        
        <!-- Map over the notifications to display them -->
        <div v-else v-for="notification in notifications" :key="notification.id"
          :class="['flex justify-between items-start px-4 py-3 border-b border-gray-200 last:border-b-0 hover:bg-gray-50 transition-colors duration-150', notification.read ? 'text-gray-500' : 'bg-gray-100 text-gray-900 font-medium']">
          
          <div class="flex-1">
            <p class="text-sm">{{ notification.message }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ notification.timestamp }}</p>
          </div>
          
          <!-- Mark as read button, only shown if unread -->
          <button v-if="!notification.read"
            @click="markAsRead(notification.id)"
            class="ml-2 mt-1 text-gray-400 hover:text-green-600 transition-colors duration-200"
            aria-label="Mark as read"
          >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm-1.5 13.5v-6h3v6h-3zm1.5-7.5c-.828 0-1.5-.672-1.5-1.5s.672-1.5 1.5-1.5 1.5.672 1.5 1.5-.672 1.5-1.5 1.5z"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';

// State for the notifications.
const notifications = ref([]);
// State to control the dropdown visibility.
const isDropdownOpen = ref(false);
// Ref to handle clicks outside the dropdown to close it.
const dropdownRef = ref(null);

// Simulate receiving a new notification every 5 seconds.
// This mimics the real-time functionality of a backend service like Laravel Echo.
onMounted(() => {
  const addNotification = () => {
    const newNotification = {
      id: Date.now(),
      message: `New message from User ${Math.floor(Math.random() * 100)}`,
      timestamp: new Date().toLocaleTimeString(),
      read: false,
    };
    notifications.value.unshift(newNotification);
  };
  const intervalId = setInterval(addNotification, 5000);
  
  // Clean up the interval when the component is unmounted.
  onUnmounted(() => clearInterval(intervalId));
});

// Effect to handle clicks outside the dropdown to close it.
onMounted(() => {
  const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
      isDropdownOpen.value = false;
    }
  };
  
  // Attach the event listener to the document.
  document.addEventListener('mousedown', handleClickOutside);
  
  // Clean up the event listener on component unmount.
  onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
  });
});

// Count the number of unread notifications using a computed property.
const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);

// Function to toggle the dropdown's visibility.
const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value;
};

// Function to mark a specific notification as read.
const markAsRead = (id) => {
  const notification = notifications.value.find(n => n.id === id);
  if (notification) {
    notification.read = true;
  }
};
</script>
