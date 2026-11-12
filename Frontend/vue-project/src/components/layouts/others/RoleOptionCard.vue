<script setup>
defineProps({
  role: {
    type: String,
    required: true, // 'customer' | 'seller'
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  selected: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['select']);
</script>

<template>
  <button
    type="button"
    :disabled="disabled"
    class="w-full text-left rounded-[18px] p-6 flex flex-col justify-between gap-3.5 transition-all cursor-pointer disabled:opacity-50 select-none"
    :class="
      selected
        ? 'bg-surface-elevated border-2 border-brand shadow-sm'
        : 'bg-surface border border-border-subtle hover:border-brand/50 shadow-sm'
    "
    @click="emit('select', role)"
  >
    <!-- CardHeaderRow (Figma: height 48px, icon box + selection indicator) -->
    <div class="flex items-center justify-between gap-3 w-full">
      <!-- RoleIconBox (Figma: 48x48px, rounded-[14px]) -->
      <div
        class="w-12 h-12 rounded-[14px] flex items-center justify-center shrink-0 transition-colors"
        :class="
          selected
            ? 'bg-brand/15 text-brand'
            : 'bg-content/10 text-content'
        "
      >
        <!-- Shopping Bag / Comprar Icon -->
        <svg
          v-if="role === 'customer'"
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
          />
        </svg>

        <!-- Storefront / Vender Icon -->
        <svg
          v-else
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
          />
        </svg>
      </div>

      <!-- SelectionIndicator (Figma: 26x26px rounded-full) -->
      <div
        class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 transition-colors"
        :class="
          selected
            ? 'bg-brand text-surface'
            : 'bg-surface border border-border-subtle'
        "
      >
        <svg
          v-if="selected"
          class="w-3.5 h-3.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
      </div>
    </div>

    <!-- RoleTextCol (Figma: title 18px font-extrabold, desc 13px leading-snug) -->
    <div class="flex flex-col gap-1 min-h-[58px]">
      <h3 class="text-[18px] font-extrabold text-content leading-tight">
        {{ title }}
      </h3>
      <p class="text-[13px] text-content-secondary leading-snug">
        {{ description }}
      </p>
    </div>
  </button>
</template>

<style scoped>
</style>
