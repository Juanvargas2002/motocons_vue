<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { EstadoVenta, VentaDetalle } from '@/types/ventas'
import { claseEstado, formatoFecha, formatoPesos } from '@/utils/formato'

const props = defineProps<{
  venta: VentaDetalle
  estados: Record<EstadoVenta, string>
  acciones: Partial<Record<EstadoVenta, string>>
  procesando: boolean
}>()

const emit = defineEmits<{
  (e: 'actualizar', nuevoEstado: EstadoVenta): void
  (e: 'cancelar'): void
}>()

const opcionesEstado = computed(
  () => Object.entries(props.acciones) as Array<[EstadoVenta, string]>,
)

const estadoSeleccionado = ref<EstadoVenta | ''>(opcionesEstado.value[0]?.[0] ?? '')

watch(opcionesEstado, (nuevas) => {
  estadoSeleccionado.value = nuevas[0]?.[0] ?? ''
})

function enviar() {
  if (!estadoSeleccionado.value) return
  if (estadoSeleccionado.value === 'ANULADA') {
    const ok = window.confirm('¿Seguro que quieres anular esta venta? No se puede deshacer.')
    if (!ok) return
  }
  emit('actualizar', estadoSeleccionado.value)
}
</script>

<template>
  <div class="vp-detalle">
    <section class="tarjeta">
      <h2>Datos de la venta</h2>
      <dl class="vp-datos">
        <dt>Venta N.º</dt>
        <dd>{{ venta.id }}</dd>
        <dt>Factura</dt>
        <dd>{{ venta.numero_factura ?? 'Sin factura asignada' }}</dd>
        <dt>Fecha</dt>
        <dd>{{ formatoFecha(venta.fecha) }}</dd>
        <dt>Cliente</dt>
        <dd>{{ venta.cliente }} (CC {{ venta.documento }})</dd>
        <dt>Total</dt>
        <dd><strong>{{ formatoPesos(venta.total) }}</strong></dd>
        <dt>Estado actual</dt>
        <dd>
          <span class="vp-estado" :class="claseEstado(venta.estado)">
            {{ estados[venta.estado] ?? venta.estado }}
          </span>
        </dd>
      </dl>

      <form v-if="opcionesEstado.length > 0" @submit.prevent="enviar">
        <div class="campo">
          <label for="nuevo-estado">Nuevo estado / acción a aplicar</label>
          <select id="nuevo-estado" v-model="estadoSeleccionado" required>
            <option v-for="[clave, texto] in opcionesEstado" :key="clave" :value="clave">
              {{ texto }} ({{ estados[clave] ?? clave }})
            </option>
          </select>
        </div>

        <p class="vp-nota" style="margin-bottom: 1.25rem">
          Las transiciones respetan las reglas del negocio de MOTOCONS (facturación automática al
          confirmar pago, registro en garantía y taller, y anulación exclusiva para administradores).
        </p>

        <div class="vp-botones">
          <button
            type="submit"
            class="btn"
            :class="{ 'btn-peligro': estadoSeleccionado === 'ANULADA' }"
            :disabled="procesando || !estadoSeleccionado"
          >
            {{ procesando ? 'Actualizando...' : 'Guardar actualización' }}
          </button>
          <button type="button" class="btn btn-secundario" @click="emit('cancelar')">
            Cancelar
          </button>
        </div>
      </form>

      <div v-else class="alerta alerta-aviso">
        Esta venta se encuentra en estado final ({{ estados[venta.estado] ?? venta.estado }}) y no
        admite más actualizaciones.
      </div>
    </section>

    <aside class="tarjeta vp-estado-tarjeta" :class="claseEstado(venta.estado)">
      <p class="vp-antetitulo">Resumen rápido</p>
      <span class="vp-estado vp-estado-grande" :class="claseEstado(venta.estado)">
        {{ estados[venta.estado] ?? venta.estado }}
      </span>
      <p class="vp-nota">
        Unidades incluidas: <strong>{{ venta.detalle.length }}</strong>
      </p>
    </aside>
  </div>
</template>
