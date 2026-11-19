import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import LoginView from '@/views/LoginView.vue'
import DashboardView from '@/views/DashboardView.vue'
import VentasListadoView from '@/views/VentasListadoView.vue'
import NuevaVentaView from '@/views/NuevaVentaView.vue'
import VerVentaView from '@/views/VerVentaView.vue'
import EditarVentaView from '@/views/EditarVentaView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/',
      component: AdminLayout,
      children: [
        {
          path: '',
          name: 'dashboard',
          component: DashboardView,
        },
        {
          path: 'ventas',
          name: 'ventas-listado',
          component: VentasListadoView,
        },
        {
          path: 'ventas/nueva',
          name: 'ventas-nueva',
          component: NuevaVentaView,
        },
        {
          path: 'ventas/:id',
          name: 'ventas-ver',
          component: VerVentaView,
        },
        {
          path: 'ventas/:id/editar',
          name: 'ventas-editar',
          component: EditarVentaView,
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

export default router
