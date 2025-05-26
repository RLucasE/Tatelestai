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
        meta: { requiresAuth: true, requiresSeller: true },
      },
      {
        name: "my-offers",
        path: "my-offers",
        component: () => import("@/components/layouts/seller/MyOffers.vue"),
        meta: { requiresAuth: true, requiresSeller: true },
      },
      {
        name: "sells",
        path: "sells",
        component: () => import("@/components/layouts/seller/Ventas.vue"),
        meta: { requiresAuth: true, requiresSeller: true },
      },
    ],
    meta: {
      requiresAuth: true,
      requiresSeller: true,
    },
  },
];
