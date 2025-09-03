<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        required: true,
        default: () => [],
    },
    disabled: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:modelValue']);

const proxyValue = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit('update:modelValue', val);
    },
});

</script>

<template>
    <select
        :class="[
            'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm',
            disabled ? 'bg-gray-100 cursor-not-allowed' : ''
        ]"
        v-model="proxyValue"
        :disabled="disabled"
    >
        <option value="" disabled>Select an option</option>
        <option
            v-for="option in options"
            :key="option.value"
            :value="option.value"
        >
            {{ option.label }}
        </option>
    </select>
</template>
