<script setup>
const quantity = defineModel({
  type: Number,
  default: 1,
});

const props = defineProps({
  min: {
    type: Number,
    default: 1,
  },
  max: {
    type: Number,
    default: 99,
  },
  available: {
    type: Number,
    default: null,
  },
  showAvailable: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const handleInput = (e) => {
  if (props.disabled) return;
  const val = parseInt(e.target.value, 10);
  if (isNaN(val) || val < props.min) {
    quantity.value = props.min;
  } else if (val > props.max) {
    quantity.value = props.max;
  } else {
    quantity.value = val;
  }
};

const decrement = () => {
  if (!props.disabled && quantity.value > props.min) {
    quantity.value--;
  }
};

const increment = () => {
  if (!props.disabled && quantity.value < props.max) {
    quantity.value++;
  }
};
</script>

<template>
  <div class="inline-flex items-center gap-2">
    <!-- Contenedor Stepper según Figma 19:3104 (114x38px, rounded-lg, border-border-subtle) -->
    <div
      class="inline-flex items-center h-[38px] w-[114px] bg-surface border border-border-subtle rounded-lg overflow-hidden select-none shadow-xs transition-opacity"
      :class="{ 'opacity-50 pointer-events-none': disabled }"
    >
      <button
        type="button"
        class="w-9 h-full flex items-center justify-center text-base font-medium text-content hover:bg-canvas transition-colors disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer active:scale-95"
        :disabled="disabled || quantity <= min"
        aria-label="Disminuir cantidad"
        @click="decrement"
      >
        —
      </button>

      <input
        type="number"
        class="w-10 h-full text-center bg-transparent text-content font-bold text-[15px] tabular-nums focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none disabled:cursor-not-allowed"
        :value="quantity"
        :min="min"
        :max="max"
        :disabled="disabled"
        aria-label="Cantidad a reservar"
        @input="handleInput"
        @change="handleInput"
      />

      <button
        type="button"
        class="w-9 h-full flex items-center justify-center text-base font-medium text-content hover:bg-canvas transition-colors disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer active:scale-95"
        :disabled="disabled || quantity >= max"
        aria-label="Aumentar cantidad"
        @click="increment"
      >
        +
      </button>
    </div>

    <span v-if="showAvailable && available != null" class="text-xs text-content-secondary tabular-nums">
      (disp: {{ available }})
    </span>
  </div>
</template>
