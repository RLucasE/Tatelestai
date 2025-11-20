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
      },
      {
        path: "history",
        name: "customer-history",
        component: () =>
          import("@/components/layouts/customer/CustomerHistory.vue"),
        meta: {
          requiresAuth: true,
          requiresCustomer: true,
        },
      },
      {
        path: "purchase-confirmation/:token",
        name: "purchase-confirmation",
        component: () =>
          import("@/components/layouts/customer/PurchaseConfirmation.vue"),
        meta: {
          requiresAuth: true,
          requiresCustomer: true,
        },
      },
      {
        path: "establishment/:id",
        name: "establishment-view",
        component: () =>
          import("@/components/layouts/customer/EstablishmentView.vue"),
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
