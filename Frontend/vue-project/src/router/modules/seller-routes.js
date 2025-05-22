export default [
  {
    path: "/seller",
    name: "seller",
    component: () => import("@/components/layouts/DashboardLayout.vue"),
    meta: {
      requiresAuth: true,
      requiresSeller: true,
    },
  },
];
