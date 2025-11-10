<template>
  <div class="logo">
    <span class="logo-text">
      <svg
        viewBox="0 0 40 40"
        class="logo-letter-t"
        xmlns="http://www.w3.org/2000/svg"
      >
        <!-- T letter stylized -->
        <path
          d="M8 8 L32 8 M20 8 L20 32"
          stroke="currentColor"
          stroke-width="3"
          stroke-linecap="round"
          fill="none"
        />
        <!-- Dot accent -->
        <circle
          cx="20"
          cy="34"
          r="2"
          fill="var(--color-accent)"
        />
      </svg>
      <span class="logo-rest">atelestai</span>
    </span>
  </div>
</template>

<script setup>
defineProps({
  size: {
    type: String,
    default: 'medium'
  }
});
</script>

<style scoped>
.logo {
  display: inline-flex;
  align-items: center;
  user-select: none;
}

.logo-text {
  display: inline-flex;
  align-items: center;
  gap: 0.125rem;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: -0.02em;
}

.logo-letter-t {
  width: 1.25em;
  height: 1.25em;
  color: var(--color-text);
  margin-bottom: -0.1em;
}

.logo-rest {
  color: var(--color-text);
}

.logo:hover .logo-letter-t {
  color: var(--color-accent);
}

.logo:hover .logo-rest {
  color: var(--color-accent);
}

/* Tamaños opcionales */
.logo.small .logo-text {
  font-size: 1rem;
}

.logo.large .logo-text {
  font-size: 1.75rem;
}
</style>
