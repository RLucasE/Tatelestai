import HomeLayout from "@/components/layouts/HomeLayout.vue";

export default [
  {
    path: "/",
    component: HomeLayout,
    redirect: { name: "login" },
    children: [
      {
        name: "register",
        path: "register",
        component: () => import("@/views/auth/register.vue"),
        meta: { requiresAuth: false },
      },
      {
        name: "login",
        path: "login",
        component: () => import("@/views/auth/login.vue"),
        meta: { requiresAuth: false },
      },
    ],
    meta: {
      requiresAuth: false,
    },
  },
];
