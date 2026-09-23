import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuth } from '@/stores/auth'
import { defaultPlanDay, shopNow } from '@/lib/dates'
import AppShell from '@/components/layout/AppShell.vue'

declare module 'vue-router' {
  interface RouteMeta {
    /** needs a signed-in user */
    auth?: boolean
    /** only for signed-out visitors (login, register) */
    guest?: boolean
    /** roles allowed; empty = everyone signed in */
    roles?: string[]
    /** which nav item to highlight for nested screens */
    nav?: string
    /** document title key */
    title?: string
  }
}

const OWNER = ['OWNER']

/** Before noon the baker wants this morning's bake; after noon everyone is heading for the count. */
function startScreen() {
  const auth = useAuth()
  const morning = shopNow(auth.organization?.timezone).minutes < 12 * 60
  return { name: auth.hasRole('OWNER') && morning ? 'morning' : 'count' }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { guest: true },
  },
  { path: '/join/:token', name: 'join', component: () => import('@/views/auth/JoinView.vue') },
  {
    path: '/plan/:date/print',
    name: 'plan-print',
    component: () => import('@/views/PlanPrintView.vue'),
    meta: { auth: true, title: 'plan.sheet.title' },
  },
  {
    path: '/',
    component: AppShell,
    meta: { auth: true },
    children: [
      { path: '', name: 'home', redirect: startScreen },
      {
        path: 'count',
        name: 'count',
        component: () => import('@/views/CountView.vue'),
        meta: { title: 'count.title' },
      },
      {
        path: 'plan',
        name: 'plan',
        redirect: () => ({
          name: 'plan-day',
          params: { date: defaultPlanDay(useAuth().organization?.timezone) },
        }),
      },
      {
        path: 'plan/:date',
        name: 'plan-day',
        component: () => import('@/views/PlanView.vue'),
        meta: { nav: 'plan', title: 'plan.title' },
      },
      {
        path: 'morning',
        name: 'morning',
        component: () => import('@/views/MorningView.vue'),
        meta: { roles: OWNER, title: 'morning.title' },
      },
      {
        path: 'insights',
        name: 'insights',
        component: () => import('@/views/InsightsView.vue'),
        meta: { roles: OWNER, title: 'insights.title' },
      },
      {
        path: 'products',
        name: 'products',
        component: () => import('@/views/ProductsView.vue'),
        meta: { roles: OWNER, title: 'products.title' },
      },
      {
        path: 'messages',
        name: 'messages',
        component: () => import('@/views/MessagesView.vue'),
        meta: { roles: OWNER, title: 'messages.title' },
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/views/SettingsView.vue'),
        meta: { title: 'settings.title' },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, saved) {
    if (saved) return saved
    if (to.hash) return { el: to.hash }
    if (to.path !== from.path) return { top: 0 }
  },
})

router.beforeEach(async (to) => {
  const auth = useAuth()
  if (!auth.ready) await auth.load()
  if (to.meta.auth && !auth.signedIn) {
    return { name: 'login', query: to.fullPath !== '/' ? { next: to.fullPath } : {} }
  }
  if (to.meta.guest && auth.signedIn) return { name: 'home' }
  const roles = to.matched.flatMap((r) => r.meta.roles ?? [])
  if (roles.length && !auth.hasRole(...roles)) return { name: 'count' }
})

export default router
