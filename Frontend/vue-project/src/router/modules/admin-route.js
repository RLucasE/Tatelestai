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
        }

    ],
    meta: {
        requiresAuth: true,
        requiresAdmin: true,
    }
}
]