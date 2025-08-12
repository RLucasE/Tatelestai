export default [
{
    path: "/adm",
    name: "admin",
    component: () => import("@/components/layouts/admin/AdminLayout.vue"),
    meta: {
        requiresAuth: true,
        requiresAdmin: true,
    }
}
]