export const redirects = [
  {
    path: '/',
    name: 'index',
    redirect: to => {
      const userData = useCookie('userData', { default: null }).value

      if(userData){
        if(userData.first_login)
          return { name: 'first-login' }
        else
          return { name: 'dashboard' }
      }

      return { name: 'login', query: to.query }
    },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: to => {
      return { name: '404' }
    },
  },
]
export const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/authentication/login.vue'),
    meta: {
      layout: 'blank',
      unauthenticatedOnly: true,
    },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/authentication/register.vue'),
    meta: {
      layout: 'blank',
      unauthenticatedOnly: true,
    },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/authentication/forgot-password.vue'),
    meta: {
      layout: 'blank',
      unauthenticatedOnly: true,
    },
  },
  {
    path: '/not-authorized',
    name: 'not-authorized',
    component: () => import('@/views/authentication/not-authorized.vue'),
    meta: {
      layout: 'blank',
      public: true,
    },
  },
  {
    path: '/first-login',
    name: 'first-login',
    component: () => import('@/views/authentication/first-login.vue'),
    meta: {
      layout: 'blank',
      public: true,
    },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/dashboard/index.vue'),
  },
  {
    path: '/projects',
    name: 'projects',
    component: () => import('@/views/projects/list.vue'),
  },
  {
    path: '/users',
    name: 'users',
    component: () => import('@/views/users/list.vue'),
  },
  {
    path: '/projects/:id',
    name: 'project-view',
    component: () => import('@/views/projects/view.vue'),
  },
  {
    path: '/projects/:id/container/:containerId',
    name: 'container-view',
    component: () => import('@/views/kanban/index.vue'),
  },
  {
    path: '/404',
    name: '404',
    component: () => import('@/views/error/page-not-found.vue'),
    meta: {
      layout: 'blank',
      public: true,
    },
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('@/views/notifications/index.vue'),
    meta: {
      layout: 'default',
      requiresAuth: true,
      title: 'Notifications'
    }
  },
]
