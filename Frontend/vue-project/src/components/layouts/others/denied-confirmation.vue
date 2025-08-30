<script setup>
</script>

<template>
  <main class="min-h-screen flex items-center justify-center bg-[var(--color-bg)] px-4 py-10">
    <section class="w-full max-w-xl bg-[var(--color-background)] text-[var(--color-heading)] rounded-xl shadow-lg border border-[var(--color-border)] p-8 text-center space-y-6">
      <div class="flex flex-col items-center gap-4">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-14 h-14 text-red-500">
          <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm2.47 6.53a.75.75 0 0 0-1.06-1.06L12 9.94 10.59 8.53a.75.75 0 1 0-1.06 1.06L10.94 11l-1.41 1.41a.75.75 0 1 0 1.06 1.06L12 12.06l1.41 1.41a.75.75 0 0 0 1.06-1.06L13.06 11l1.41-1.41Z" clip-rule="evenodd" />
        </svg>
        <h1 class="text-2xl font-semibold text-red-600">Establecimiento Rechazado</h1>
        <p class="text-[var(--color-heading)]/80 leading-relaxed">
          Tu establecimiento ha sido rechazado. Para más información o para continuar con el proceso
          comunícate con <a href="mailto:example@gmail.com" class="font-medium underline text-[var(--color-primary)] hover:text-[var(--color-secondary)]">example@gmail.com</a>.
        </p>
      </div>
    </section>
  </main>
</template>
