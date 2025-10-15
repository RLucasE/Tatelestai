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
        name: "edit-offer",
        path: "edit-offer/:id",
        component: () => import("@/components/layouts/seller/EditOffer.vue"),
        props: true,
      },
      {
        name: "sells",
        path: "sells",
        component: () => import("@/components/layouts/seller/Ventas.vue"),
      },
      {
        name: "verify-code",
        path: "verify-code",
        component: () => import("@/components/layouts/seller/VerifyCode.vue"),
      },
      {
        name: "create-product",
        path: "create-product",
        component: () => import(("@/components/layouts/seller/CreateProduct.vue")),
      },
      {
        name: "my-products",
        path: "my-products",
        component: () => import("@/components/layouts/seller/MyProducts.vue"),
      },
      {
        name: "establishment",
        path: "establishment",
        component: () => import("@/components/layouts/seller/Establishment.vue"),
      },
      {
        name: "edit-establishment",
        path: "establishment/edit",
        component: () => import("@/components/layouts/seller/EditEstablishment.vue"),
      }
    ],
    meta: {
      requiresAuth: true,
      requiresSeller: true,
      requiresActive: true,
    }
  }
];
