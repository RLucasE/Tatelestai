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
      {
        path: "carrito",
        name: "cart",
        component: () =>
          import("@/components/layouts/customer/CustomerCart.vue"),
      },
      {
        path: "purchases",
        name: "customer-purchases",
        component: () =>
          import("@/components/layouts/customer/CustomerPurchases.vue"),
      }
    ],
    meta: {
      requiresAuth: true,
      requiresCustomer: true,
    },
  },
];
