export type EstadoVenta =
  | 'PENDIENTE'
  | 'EN_CREDITO'
  | 'PAGADA'
  | 'ENTREGADA'
  | 'CERRADA'
  | 'ANULADA'

export interface UsuarioSesion {
  id: number
  nombre: string
  correo: string
  rol: string
}

export interface SesionResponse {
  autenticado: boolean
  usuario: UsuarioSesion
  token: string
}

export interface VentaResumen {
  id: number
  numero_factura: string | null
  fecha: string
  cliente: string
  documento: string
  unidades: number
  total: number
  estado: EstadoVenta
  vendedor: string | null
}

export interface ListarVentasResponse {
  ventas: VentaResumen[]
  conteo: Record<EstadoVenta, number>
  estados: Record<EstadoVenta, string>
  busqueda: string
  estado: string
}

export interface MotoCatalogo {
  id: number
  marca: string
  modelo: string
  propulsion: 'electrica' | 'combustion' | string
  precio: number
}

export interface CatalogoResponse {
  catalogo: MotoCatalogo[]
  maxUnidades: number
  token: string
}

export interface ClienteInfo {
  id?: number
  documento: string
  nombre: string
  correo: string | null
  telefono: string | null
}

export interface BuscarClienteResponse {
  encontrado: boolean
  cliente: ClienteInfo | null
}

export interface UnidadDetalle {
  marca: string
  modelo: string
  propulsion: string
  chasis: string
  placa: string | null
  meses_garantia: number
  vehiculo_id: number | null
  precio: number
}

export interface VentaDetalle {
  id: number
  numero_factura: string | null
  fecha: string
  vendedor: string | null
  cliente: string
  documento: string
  correo: string | null
  telefono: string | null
  estado: EstadoVenta
  total: number
  detalle: UnidadDetalle[]
}

export interface VerVentaResponse {
  venta: VentaDetalle
  estados: Record<EstadoVenta, string>
  acciones: Partial<Record<EstadoVenta, string>>
  token: string
}

export interface RegistrarVentaPayload {
  token: string
  documento: string
  nombre: string
  correo: string
  telefono: string
  forma_pago: 'contado' | 'credito'
  unidades: Array<{
    moto_id: number | ''
    chasis: string
    placa: string
  }>
}

export interface RegistrarVentaResponse {
  ok: boolean
  id: number
  mensaje: string
}

export interface CambiarEstadoResponse {
  ok: boolean
  factura: string | null
  mensaje: string
  venta: VentaDetalle
  acciones: Partial<Record<EstadoVenta, string>>
}
