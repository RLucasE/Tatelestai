export default [
  {
    path: "/select",
    name: "select-role",
    component: () => import("@/components/layouts/others/role-selector.vue"),
    meta: {
      requiresAuth: true,
      requiresDefault: true,
      requiresSelectingRole: true,
    },
  },
  {
    path: "/register-establishment",
    name: "register-establishment",
    component: () =>
      import("@/components/layouts/others/register-establishment.vue"),
    meta: {
      requiresAuth: true,
      requiresSeller: true,
      requiresRegisteringEstablishment: true,
    },
  },
];
