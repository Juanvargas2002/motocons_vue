<script setup lang="ts">
import { computed, ref } from 'vue'
import type { MotoCatalogo, RegistrarVentaPayload } from '@/types/ventas'
import { formatoPesos } from '@/utils/formato'

interface FilaUnidad {
  moto_id: number | ''
  chasis: string
  placa: string
}

const props = defineProps<{
  catalogo: MotoCatalogo[]
  maxUnidades: number
  token: string
  enviando: boolean
  buscandoCliente: boolean
  clienteEstado: string
  clienteEstadoTipo: 'neutro' | 'encontrado' | 'nuevo'
  clienteEncontrado: boolean
}>()

const emit = defineEmits<{
  (e: 'buscar-cliente', documento: string): void
  (e: 'reset-cliente'): void
  (e: 'enviar', payload: RegistrarVentaPayload): void
}>()

const documento = ref('')
const nombre = ref('')
const correo = ref('')
const telefono = ref('')
const formaPago = ref<'contado' | 'credito'>('contado')
const unidades = ref<FilaUnidad[]>([{ moto_id: '', chasis: '', placa: '' }])

const mapaPrecios = computed(() => {
  const mapa = new Map<number, number>()
  for (const m of props.catalogo) {
    mapa.set(Number(m.id), Number(m.precio))
  }
  return mapa
})

const cantidadSeleccionadas = computed(
  () =>
    unidades.value.filter(
      (u) => u.moto_id !== '' && mapaPrecios.value.has(Number(u.moto_id)),
    ).length,
)

const total = computed(() =>
  unidades.value.reduce((acc, u) => {
    if (u.moto_id === '') return acc
    return acc + (mapaPrecios.value.get(Number(u.moto_id)) ?? 0)
  }, 0),
)

function precioDe(motoId: number | ''): string {
  if (motoId === '') return '—'
  const precio = mapaPrecios.value.get(Number(motoId))
  return precio !== undefined ? formatoPesos(precio) : '—'
}

function agregarUnidad() {
  if (unidades.value.length >= props.maxUnidades) return
  unidades.value.push({ moto_id: '', chasis: '', placa: '' })
}

function quitarUnidad(indice: number) {
  if (unidades.value.length <= 1) return
  unidades.value.splice(indice, 1)
}

function onDocumentoInput() {
  if (props.clienteEncontrado) {
    emit('reset-cliente')
  }
}

function llenarCliente(datos: { nombre: string; correo: string | null; telefono: string | null }) {
  nombre.value = datos.nombre
  correo.value = datos.correo ?? ''
  telefono.value = datos.telefono ?? ''
}

defineExpose({ llenarCliente })

function submitForm() {
  emit('enviar', {
    token: props.token,
    documento: documento.value.trim(),
    nombre: nombre.value.trim(),
    correo: correo.value.trim(),
    telefono: telefono.value.trim(),
    forma_pago: formaPago.value,
    unidades: unidades.value.map((u) => ({
      moto_id: u.moto_id,
      chasis: u.chasis.trim().toUpperCase(),
      placa: u.placa.trim().toUpperCase(),
    })),
  })
}
</script>

<template>
  <form class="vp-venta" @submit.prevent="submitForm">
    <div class="vp-pasos">
      <section class="vp-seccion">
        <div class="vp-seccion-titulo">
          <span class="vp-paso">01</span>
          <h2>Cliente</h2>
        </div>
        <div class="vp-documento">
          <div class="campo">
            <label for="documento">Documento</label>
            <input
              id="documento"
              v-model="documento"
              maxlength="20"
              placeholder="Ej.: 1067123456"
              required
              @input="onDocumentoInput"
              @blur="emit('buscar-cliente', documento)"
            />
          </div>
          <button
            type="button"
            class="btn btn-secundario"
            :disabled="buscandoCliente || !documento.trim()"
            @click="emit('buscar-cliente', documento)"
          >
            {{ buscandoCliente ? 'Buscando...' : 'Buscar cliente' }}
          </button>
        </div>
        <p
          class="vp-nota vp-cliente-estado"
          :class="{
            'es-encontrado': clienteEstadoTipo === 'encontrado',
            'es-nuevo': clienteEstadoTipo === 'nuevo',
          }"
          aria-live="polite"
        >
          {{ clienteEstado }}
        </p>

        <div class="vp-campos-cliente">
          <div class="campo vp-campo-ancho">
            <label for="nombre">Nombre completo</label>
            <input
              id="nombre"
              v-model="nombre"
              maxlength="120"
              :readonly="clienteEncontrado"
              required
            />
          </div>
          <div class="campo">
            <label for="correo">Correo (opcional)</label>
            <input
              id="correo"
              v-model="correo"
              type="email"
              maxlength="150"
              :readonly="clienteEncontrado"
            />
          </div>
          <div class="campo">
            <label for="telefono">Teléfono (opcional)</label>
            <input
              id="telefono"
              v-model="telefono"
              type="tel"
              maxlength="20"
              :readonly="clienteEncontrado"
            />
          </div>
        </div>
      </section>

      <section class="vp-seccion">
        <div class="vp-seccion-titulo">
          <span class="vp-paso">02</span>
          <h2>Motos</h2>
        </div>
        <div class="vp-tabla">
          <table class="tabla vp-unidades">
            <thead>
              <tr>
                <th>Modelo</th>
                <th>Chasis</th>
                <th>Placa</th>
                <th class="vp-numero-col">Precio</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(u, i) in unidades" :key="i" class="vp-unidad">
                <td>
                  <select
                    v-model="u.moto_id"
                    class="vp-input"
                    aria-label="Modelo de la moto"
                    required
                  >
                    <option value="">Selecciona un modelo...</option>
                    <option v-for="m in catalogo" :key="m.id" :value="m.id">
                      {{ m.marca }} {{ m.modelo }} ·
                      {{ m.propulsion === 'electrica' ? 'Eléctrica' : 'Combustión' }}
                    </option>
                  </select>
                </td>
                <td>
                  <input
                    v-model="u.chasis"
                    class="vp-input vp-mayusculas"
                    maxlength="30"
                    placeholder="Ej.: 9FKRG2110N0001234"
                    aria-label="Número de chasis"
                    required
                  />
                </td>
                <td>
                  <input
                    v-model="u.placa"
                    class="vp-input vp-mayusculas"
                    maxlength="10"
                    placeholder="Sin placa"
                    aria-label="Placa (opcional)"
                  />
                </td>
                <td class="vp-numero-col">{{ precioDe(u.moto_id) }}</td>
                <td class="vp-quitar-col">
                  <button
                    v-if="unidades.length > 1"
                    type="button"
                    class="btn btn-secundario btn-chico"
                    @click="quitarUnidad(i)"
                  >
                    Quitar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="vp-pie-seccion">
          <button
            v-if="unidades.length < maxUnidades"
            type="button"
            class="btn btn-secundario btn-chico"
            @click="agregarUnidad"
          >
            Agregar otra moto
          </button>
          <p class="vp-nota">
            La placa puede quedar vacía si la moto todavía no está matriculada.
          </p>
        </div>
      </section>

      <section class="vp-seccion">
        <div class="vp-seccion-titulo">
          <span class="vp-paso">03</span>
          <h2>Forma de pago</h2>
        </div>
        <div class="vp-opciones">
          <label class="vp-opcion">
            <input v-model="formaPago" type="radio" name="forma_pago" value="contado" />
            <span>
              <strong>Contado</strong>
              Efectivo, transferencia o tarjeta. Queda pendiente hasta que confirmes el pago.
            </span>
          </label>
          <label class="vp-opcion">
            <input v-model="formaPago" type="radio" name="forma_pago" value="credito" />
            <span>
              <strong>Financiada</strong>
              Crédito con entidad o Brilla. Queda en crédito hasta que financiación lo apruebe.
            </span>
          </label>
        </div>
      </section>
    </div>

    <aside class="vp-resumen">
      <p class="vp-antetitulo">Resumen</p>
      <dl>
        <dt>Motos</dt>
        <dd>{{ cantidadSeleccionadas }}</dd>
        <dt>Total</dt>
        <dd class="vp-total">{{ formatoPesos(total) }}</dd>
      </dl>
      <p class="vp-nota">
        El precio sale del catálogo. La factura se asigna al confirmar el pago.
      </p>
      <button type="submit" class="btn btn-bloque" :disabled="enviando">
        {{ enviando ? 'Registrando...' : 'Registrar venta' }}
      </button>
    </aside>
  </form>
</template>
