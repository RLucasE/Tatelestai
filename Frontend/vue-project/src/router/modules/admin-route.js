export default [
{
    path: "/adm",
    name: "admin",
    component: () => import("@/components/layouts/admin/AdminLayout.vue"),
    children: [
        {
            path: "users",
            name: "admin-users",
            component: () => import('@/components/layouts/admin/Users.vue'),
        },
        {
            path: "user/:id",
            name: "admin-user-detail",
            component: () => import('@/components/layouts/admin/UserDetail.vue'),
        },
        {
            path: "new-sellers",
            name: "new-sellers",
            component: () => import('@/components/layouts/admin/NewSellers.vue'),
        },
        {
            path: "new-seller/:id",
            name: "new-seller",
            component: () => import('@/components/layouts/admin/NewSeller.vue'),
        },
        {
            path: "offers",
            name: "admin-offers",
            component: () => import('@/components/layouts/admin/AdminOffers.vue'),
        },
        {
            path: "sellers/:id/offers",
            name: "admin-seller-offers",
            component: () => import('@/components/layouts/admin/SellerOffers.vue'),
        },
        {
            path: "sellers/:id/sells",
            name: "adm-seller-sells",
            component: () => import('@/components/layouts/admin/SellerSells.vue'),
        },
        {
            path: "sells",
            name: "admin-sells",
            component: () => import('@/components/layouts/admin/AdminSells.vue'),
        },
        {
            path: "customer/:id/purchases",
            name: "adm-customer-purchases",
            component: () => import('@/components/layouts/admin/CustomerPurchases.vue'),
        }
    ],
    meta: {
        requiresAuth: true,
        requiresAdmin: true,
    }
}
]