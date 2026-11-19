<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { EstadoVenta, VentaResumen } from '@/types/ventas'
import { claseEstado, formatoFecha, formatoPesos } from '@/utils/formato'

const props = defineProps<{
  ventas: VentaResumen[]
  conteo: Record<string, number>
  estados: Record<EstadoVenta, string>
  estadoActivo: string
  busqueda: string
  cargando: boolean
}>()

const emit = defineEmits<{
  (e: 'update:busqueda', value: string): void
  (e: 'filtrar-estado', estado: string): void
  (e: 'buscar'): void
  (e: 'limpiar'): void
}>()

const totalTodas = computed(() =>
  Object.values(props.conteo).reduce((acc, n) => acc + (Number(n) || 0), 0),
)
</script>

<template>
  <nav class="vp-filtros" aria-label="Filtrar por estado">
    <button
      type="button"
      class="vp-filtro"
      :class="{ 'es-activo': estadoActivo === '' }"
      @click="emit('filtrar-estado', '')"
    >
      Todas <span>{{ totalTodas }}</span>
    </button>
    <button
      v-for="(texto, clave) in estados"
      :key="clave"
      type="button"
      class="vp-filtro"
      :class="{ 'es-activo': estadoActivo === clave }"
      @click="emit('filtrar-estado', clave)"
    >
      {{ texto }} <span>{{ Number(conteo[clave] ?? 0) }}</span>
    </button>
  </nav>

  <form class="vp-buscador" @submit.prevent="emit('buscar')">
    <div class="campo">
      <label for="q">Buscar por factura, documento o nombre del cliente</label>
      <input
        id="q"
        :value="busqueda"
        type="text"
        placeholder="Ej.: FV-0001 o 1067123456"
        @input="emit('update:busqueda', ($event.target as HTMLInputElement).value)"
      />
    </div>
    <button type="submit" class="btn">Buscar</button>
    <button
      v-if="busqueda.trim() !== ''"
      type="button"
      class="btn btn-secundario"
      @click="emit('limpiar')"
    >
      Limpiar
    </button>
  </form>

  <div class="vp-tabla">
    <table class="tabla">
      <thead>
        <tr>
          <th>Factura</th>
          <th>Fecha</th>
          <th>Cliente</th>
          <th class="vp-numero-col">Motos</th>
          <th class="vp-numero-col">Total</th>
          <th>Estado</th>
          <th>Vendedor</th>
          <th class="vp-numero-col">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <template v-if="cargando">
          <tr v-for="i in 4" :key="i">
            <td colspan="8"><div class="vp-skeleton"></div></td>
          </tr>
        </template>
        <tr v-else-if="ventas.length === 0">
          <td colspan="8" class="vp-vacio">No hay ventas con ese filtro.</td>
        </tr>
        <tr v-for="v in ventas" v-else :key="v.id">
          <td>
            <strong v-if="v.numero_factura !== null" class="vp-factura">
              {{ v.numero_factura }}
            </strong>
            <span v-else class="vp-suave">Sin factura</span>
          </td>
          <td>{{ formatoFecha(v.fecha) }}</td>
          <td>
            {{ v.cliente }}
            <small>CC {{ v.documento }}</small>
          </td>
          <td class="vp-numero-col">{{ v.unidades }}</td>
          <td class="vp-numero-col">{{ formatoPesos(v.total) }}</td>
          <td>
            <span class="vp-estado" :class="claseEstado(v.estado)">
              {{ estados[v.estado] ?? v.estado }}
            </span>
          </td>
          <td>{{ v.vendedor ?? 'Sin registro' }}</td>
          <td class="vp-numero-col">
            <div class="vp-acciones-fila">
              <RouterLink class="btn btn-secundario btn-chico" :to="`/ventas/${v.id}`">
                Ver
              </RouterLink>
              <RouterLink class="btn btn-chico" :to="`/ventas/${v.id}/editar`">
                Editar
              </RouterLink>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
