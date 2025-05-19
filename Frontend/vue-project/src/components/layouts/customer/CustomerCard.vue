<script>
export default {
  name: "CustomerCard",
  props: {
    offer: {
      type: Object,
      required: true,
    },
  },
};
</script>

<template>
  <!-- component -->
  <div class="principal tag"></div>

  <div>{{ offer.id }}</div>
</template>

<style scoped></style>
