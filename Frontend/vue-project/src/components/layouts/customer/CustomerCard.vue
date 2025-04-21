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
  <div class="customer-card">
    <div class="card-content">
      <div v-for="(category, index) in offer.categories" :key="index">
        {{ category.category_name }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.customer-card {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 16px;
  background-color: gray;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  max-width: 25%;
  min-width: 200px;
  min-height: 100px;
  max-height: 10%;
  margin: 16px auto;
}
</style>
