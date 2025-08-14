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
        }
    ],
    meta: {
        requiresAuth: true,
        requiresAdmin: true,
    }
}
]