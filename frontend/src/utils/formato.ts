import type { EstadoVenta } from '@/types/ventas'

export function formatoPesos(valor: number | string): string {
  const numero = Number(valor) || 0
  return '$' + Math.round(numero).toLocaleString('es-CO')
}

export function formatoFecha(valor: string): string {
  if (!valor) return ''
  const fecha = new Date(valor.includes('T') ? valor : valor.replace(' ', 'T'))
  if (Number.isNaN(fecha.getTime())) return valor
  const dia = String(fecha.getDate()).padStart(2, '0')
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const anio = fecha.getFullYear()
  return `${dia}/${mes}/${anio}`
}

export function claseEstado(estado: EstadoVenta | string): string {
  return 'estado-' + estado.toLowerCase().replace(/_/g, '-')
}
