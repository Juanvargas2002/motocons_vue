import axios, { AxiosError } from 'axios'
import type {
  BuscarClienteResponse,
  CambiarEstadoResponse,
  CatalogoResponse,
  EstadoVenta,
  ListarVentasResponse,
  RegistrarVentaPayload,
  RegistrarVentaResponse,
  SesionResponse,
  VerVentaResponse,
} from '@/types/ventas'

export class ApiError extends Error {
  status: number

  constructor(message: string, status: number) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

const http = axios.create({
  withCredentials: true,
  headers: {
    Accept: 'application/json',
  },
})

function normalizarError(err: unknown): never {
  if (err instanceof ApiError) {
    throw err
  }
  if (err instanceof AxiosError) {
    const status = err.response?.status ?? 0
    const data = err.response?.data
    if (typeof data === 'object' && data !== null && 'error' in data && typeof data.error === 'string') {
      throw new ApiError(data.error, status)
    }
    if (status === 401) {
      throw new ApiError('Debes iniciar sesión para acceder a Ventas en tienda.', 401)
    }
    if (status === 403) {
      throw new ApiError('No tienes permiso para realizar esta acción.', 403)
    }
    throw new ApiError(err.message || 'Error de conexión con el servidor.', status)
  }
  throw new ApiError('Error inesperado en la solicitud.', 0)
}

function validarJson<T>(data: unknown, contentType: string): T {
  if (!contentType.includes('application/json')) {
    throw new ApiError('Debes iniciar sesión para acceder a Ventas en tienda.', 401)
  }
  return data as T
}

export const ventasApi = {
  async sesion(): Promise<SesionResponse> {
    try {
      const res = await http.get('/index.php', {
        params: { r: 'ventas_presenciales/apiSesion' },
      })
      return validarJson<SesionResponse>(res.data, String(res.headers['content-type'] ?? ''))
    } catch (err) {
      normalizarError(err)
    }
  },

  async listar(busqueda = '', estado = ''): Promise<ListarVentasResponse> {
    try {
      const params: Record<string, string> = { r: 'ventas_presenciales/apiListar' }
      if (busqueda.trim()) params.q = busqueda.trim()
      if (estado) params.estado = estado
      const res = await http.get('/index.php', { params })
      return validarJson<ListarVentasResponse>(res.data, String(res.headers['content-type'] ?? ''))
    } catch (err) {
      normalizarError(err)
    }
  },

  async catalogo(): Promise<CatalogoResponse> {
    try {
      const res = await http.get('/index.php', {
        params: { r: 'ventas_presenciales/apiCatalogo' },
      })
      return validarJson<CatalogoResponse>(res.data, String(res.headers['content-type'] ?? ''))
    } catch (err) {
      normalizarError(err)
    }
  },

  async buscarCliente(documento: string): Promise<BuscarClienteResponse> {
    try {
      const res = await http.get('/index.php', {
        params: {
          r: 'ventas_presenciales/cliente',
          documento: documento.replace(/[\s.]/g, ''),
        },
      })
      return validarJson<BuscarClienteResponse>(res.data, String(res.headers['content-type'] ?? ''))
    } catch (err) {
      normalizarError(err)
    }
  },

  async ver(id: number): Promise<VerVentaResponse> {
    try {
      const res = await http.get('/index.php', {
        params: { r: 'ventas_presenciales/apiVer', id },
      })
      return validarJson<VerVentaResponse>(res.data, String(res.headers['content-type'] ?? ''))
    } catch (err) {
      normalizarError(err)
    }
  },

  async registrar(payload: RegistrarVentaPayload): Promise<RegistrarVentaResponse> {
    try {
      const res = await http.post('/index.php?r=ventas_presenciales/apiRegistrar', payload)
      return validarJson<RegistrarVentaResponse>(res.data, String(res.headers['content-type'] ?? ''))
    } catch (err) {
      normalizarError(err)
    }
  },

  async cambiarEstado(
    id: number,
    estado: EstadoVenta,
    token: string,
  ): Promise<CambiarEstadoResponse> {
    try {
      const res = await http.post('/index.php?r=ventas_presenciales/apiCambiarEstado', {
        id,
        estado,
        token,
      })
      return validarJson<CambiarEstadoResponse>(res.data, String(res.headers['content-type'] ?? ''))
    } catch (err) {
      normalizarError(err)
    }
  },
}
