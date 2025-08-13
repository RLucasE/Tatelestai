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
        }
    ],
    meta: {
        requiresAuth: true,
        requiresAdmin: true,
    }
}
]