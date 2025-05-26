export default [
  {
    path: "/select",
    name: "select-role",
    component: () => import("@/components/layouts/others/role-selector.vue"),
    meta: {
      requiresAuth: true,
      requiresUnknowknChoice: true,
    },
  },
];
