export default [
  {
    path: "/seller",
    name: "seller",
    component: () => import("@/components/layouts/DashboardLayout.vue"),
    children: [
      {
        name: "create-offer",
        path: "create-offer",
        component: () => import("@/components/layouts/seller/CreateOffer.vue"),
      },
      {
        name: "my-offers",
        path: "my-offers",
        component: () => import("@/components/layouts/seller/MyOffers.vue"),
      },
      {
        name: "sells",
        path: "sells",
        component: () => import("@/components/layouts/seller/Ventas.vue"),
      },
    ],
    meta: {
      requiresAuth: true,
      requiresSeller: true,
      requiresActive: true,
    },
  },
];
