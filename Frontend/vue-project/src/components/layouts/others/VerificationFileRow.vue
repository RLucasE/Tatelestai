<script setup>
import { computed } from 'vue';

const props = defineProps({
  item: {
    type: Object,
    default: null,
  },
  fileItem: {
    type: Object,
    default: null,
  },
  documentTypes: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  formattedSize: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:documentType', 'update:category', 'remove']);

const resolvedItem = computed(() => props.item || props.fileItem || {});

const fileName = computed(() => {
  return resolvedItem.value.name || resolvedItem.value.file?.name || 'Documento adjunto';
});

const previewUrl = computed(() => {
  return resolvedItem.value.previewUrl || resolvedItem.value.preview || null;
});

const fileExtension = computed(() => {
  if (resolvedItem.value.ext) return resolvedItem.value.ext;
  const name = fileName.value;
  if (name.includes('.')) {
    return name.split('.').pop().toUpperCase();
  }
  return 'DOC';
});

const displaySize = computed(() => {
  if (props.formattedSize) return props.formattedSize;
  const bytes = resolvedItem.value.size ?? resolvedItem.value.file?.size;
  if (typeof bytes === 'number' && bytes > 0) {
    if (bytes >= 1024 * 1024) {
      return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
  }
  return '';
});

const resolvedCategories = computed(() => {
  return props.documentTypes.length > 0 ? props.documentTypes : props.categories;
});

const selectedCategory = computed(() => {
  return (
    resolvedItem.value.documentType ??
    resolvedItem.value.document_category ??
    resolvedCategories.value[0]?.value ??
    ''
  );
});

const handleCategoryChange = (newVal) => {
  emit('update:documentType', newVal);
  emit('update:category', newVal);
};
</script>

<template>
  <div
    class="rounded-[14px] bg-canvas border border-border-subtle px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
  >
    <!-- FileIdentity -->
    <div class="flex items-center gap-3 min-w-0 flex-1">
      <div
        class="w-10 h-10 rounded-[10px] bg-surface border border-border-subtle flex items-center justify-center overflow-hidden shrink-0"
      >
        <img
          v-if="previewUrl"
          :src="previewUrl"
          :alt="fileName"
          class="w-full h-full object-cover"
        />
        <svg
          v-else
          class="w-5 h-5 text-brand"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
          />
        </svg>
      </div>

      <div class="flex flex-col gap-1 min-w-0">
        <p class="text-[13px] font-bold text-content truncate" :title="fileName">
          {{ fileName }}
        </p>
        <div class="flex items-center gap-2">
          <span
            class="px-1.5 py-0.5 rounded-[5px] bg-brand/15 text-brand text-[10px] font-bold uppercase"
          >
            {{ fileExtension }}
          </span>
          <span v-if="displaySize" class="text-[11px] text-content-secondary">
            {{ displaySize }}
          </span>
        </div>
      </div>
    </div>

    <!-- CategorySelectAndDelete -->
    <div class="flex items-center gap-2.5 shrink-0">
      <div class="relative">
        <select
          :value="selectedCategory"
          class="appearance-none rounded-[10px] bg-surface border border-border-subtle pl-3 pr-8 py-2 text-[12px] font-semibold text-content focus:outline-none focus:border-brand cursor-pointer"
          @change="handleCategoryChange($event.target.value)"
        >
          <option
            v-for="dt in resolvedCategories"
            :key="dt.value"
            :value="dt.value"
          >
            {{ dt.label }}
          </option>
        </select>
        <span class="pointer-events-none absolute right-2.5 top-2 text-xs text-content-secondary">
          ▾
        </span>
      </div>

      <button
        type="button"
        class="w-8 h-8 rounded-[8px] bg-danger/10 hover:bg-danger/20 text-danger flex items-center justify-center transition-colors cursor-pointer shrink-0"
        title="Quitar archivo"
        @click="emit('remove', resolvedItem.id)"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
          />
        </svg>
      </button>
    </div>
  </div>
</template>
