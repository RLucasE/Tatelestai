<script setup>
import BaseSkeleton from '../atoms/BaseSkeleton.vue';
</script>

<template>
  <div class="bg-surface border border-border-subtle rounded-2xl rounded-card p-4 sm:p-5 flex flex-col gap-3.5 h-full overflow-hidden">
    <!-- Header skeleton -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <BaseSkeleton shape="circle" class="w-6 h-6" />
        <BaseSkeleton class="h-3.5 w-24" />
      </div>
    </div>

    <!-- Title & Description skeleton -->
    <div class="space-y-2">
      <BaseSkeleton class="h-4 w-3/4" />
      <BaseSkeleton class="h-3 w-full" />
    </div>

    <!-- Tags skeleton -->
    <div class="flex gap-1.5">
      <BaseSkeleton class="h-4 w-14" />
      <BaseSkeleton class="h-4 w-12" />
    </div>

    <!-- Footer skeleton -->
    <div class="space-y-2 pt-2 border-t border-border-subtle/50 mt-auto">
      <BaseSkeleton class="h-4 w-40" />
      <div class="flex items-center justify-between pt-1">
        <BaseSkeleton class="h-6 w-20" />
        <BaseSkeleton class="h-8 w-20 rounded-full" />
      </div>
    </div>
  </div>
</template>
