<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: {
    type: String,
    required: true,
  },
  size: {
    type: [String, Number],
    default: 'md',
  },
  strokeWidth: {
    type: [Number, String],
    default: 2,
  },
});

const icons = {
  compass: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
  },
  ticket: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>',
  },
  history: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
  },
  cart: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
  },
  'shopping-cart': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
  },
  user: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  },
  bell: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
  },
  bag: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
  },
  'shopping-bag': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
  },
  pin: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
  },
  clock: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
  },
  map: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" x2="9" y1="3" y2="18"/><line x1="15" x2="15" y1="6" y2="21"/>',
  },
  list: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/>',
  },
  star: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
  },
  'star-fill': {
    viewBox: '0 0 24 24',
    fill: 'currentColor',
    body: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
  },
  qr: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/>',
  },
  camera: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
  },
  filter: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
  },
  search: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
  },
  check: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<polyline points="20 6 9 17 4 12"/>',
  },
  close: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
  },
  clear: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
  },
  plus: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M5 12h14"/><path d="M12 5v14"/>',
  },
  minus: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M5 12h14"/>',
  },
  'arrow-right': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
  },
  'arrow-left': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
  },
  'chevron-down': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="m6 9 6 6 6-6"/>',
  },
  'chevron-up': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="m18 15-6-6-6 6"/>',
  },
  'chevron-right': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="m9 18 6-6-6-6"/>',
  },
  'chevron-left': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="m15 18-6-6 6-6"/>',
  },
  alert: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>',
  },
  'alert-circle': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>',
  },
  info: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
  },
  trash: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
  },
  heart: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
  },
  refresh: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/>',
  },
  spinner: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/>',
  },
  loader: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/>',
  },
  zap: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
  },
  'log-out': {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
  },
  logout: {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
  },
};

const currentIcon = computed(() => {
  return icons[props.name] || {
    viewBox: '0 0 24 24',
    fill: 'none',
    body: '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" x2="12.01" y1="17" y2="17"/>',
  };
});

const pixelSize = computed(() => {
  if (typeof props.size === 'number' || !isNaN(Number(props.size))) {
    return Number(props.size);
  }
  switch (props.size) {
    case 'xs':
      return 14;
    case 'sm':
      return 16;
    case 'md':
      return 20;
    case 'lg':
      return 24;
    case 'xl':
      return 32;
    default:
      return 20;
  }
});
</script>

<template>
  <svg
    :viewBox="currentIcon.viewBox"
    :width="pixelSize"
    :height="pixelSize"
    :fill="name === 'star-fill' ? 'currentColor' : (currentIcon.fill || 'none')"
    stroke="currentColor"
    :stroke-width="name === 'star-fill' ? 0 : Number(strokeWidth)"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="shrink-0 transition-transform duration-150 inline-block align-middle"
    v-html="currentIcon.body"
  />
</template>
