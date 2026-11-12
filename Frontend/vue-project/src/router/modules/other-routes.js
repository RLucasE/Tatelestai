export default [
  {
    path: "/select",
    alias: ["/onboarding/role-selector"],
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
    alias: ["/onboarding/register-establishment"],
    name: "register-establishment",
    component: () =>
      import("@/components/layouts/others/register-establishment.vue"),
    meta: {
      requiresAuth: true,
      requiresSeller: true,
      requiresRegisteringEstablishment: true,
    },
  },
  {
    path: "/waiting-confirmation",
    alias: ["/onboarding/waiting-confirmation"],
    name: "waiting-confirmation",
    component: () =>
      import("@/components/layouts/others/waiting-confirmation.vue"),
    meta: {
      requiresAuth: true,
      requiresSeller: true,
      requiresWaitingConfirmation: true,
    },
  },
  {
    path: "/inactive",
    name: "inactive",
    component: () => import("@/components/layouts/others/Inactive.vue"),
    meta: {
      requiresAuth: true,
      requiresInactive: true,
    },
  },
  {
    path: "/denied-confirmation",
    alias: ["/onboarding/denied-confirmation"],
    name: "denied-confirmation",
    component: () => import("@/components/layouts/others/denied-confirmation.vue"),
    meta: {
      requiresAuth: true,
      requiresSeller: true,
      requiresDeniedConfirmation: true,
    },
  },
  {
    path: "/payment/return",
    redirect: (to) => ({
      path: "/customer/payment/return",
      query: to.query,
    }),
    meta: {
      requiresAuth: true,
      requiresCustomer: true,
    },
  },
  {
    path: "/design-system",
    name: "design-system",
    component: () => import("@/views/DesignSystemView.vue"),
    meta: {
      allowAny: true,
    },
  },
];
