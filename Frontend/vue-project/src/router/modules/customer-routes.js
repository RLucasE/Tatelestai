export default [
  {
    path: "/customer",
    name: "customer",
    component: () => import("@/components/layouts/customer/CustomerLayout.vue"),
    children: [
      {
        path: "offers",
        name: "customer-offers",
        component: () =>
          import("@/components/layouts/customer/customerCards.vue"),
        meta: {
          requiresAuth: true,
          requiresCustomer: true,
        },
      },
      {
        path: "profile",
        name: "customer-profile",
        component: () =>
          import("@/components/layouts/customer/CustomerProfile.vue"),
        meta: {
          requiresAuth: true,
          requiresCustomer: true,
        },
      },
    ],
    meta: {
      requiresAuth: true,
      requiresCustomer: true,
    },
  },
];
