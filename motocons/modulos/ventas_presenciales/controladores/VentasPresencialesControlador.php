<?php
// Ventas en tienda (punto de venta). Solo vendedores y administradores.
// La garantía y el taller leen las motos vendidas de vehiculo: no se duplican aquí.
class VentasPresencialesControlador extends Controlador
{
    private const ROLES = ['vendedor', 'admin'];

    // Texto del botón para pasar a cada estado.
    private const ACCIONES = [
        'PAGADA' => 'Confirmar pago',
        'EN_CREDITO' => 'Pasar a crédito',
        'PENDIENTE' => 'Pasar a contado',
        'ENTREGADA' => 'Registrar entrega',
        'CERRADA' => 'Cerrar venta',
        'ANULADA' => 'Anular venta',
    ];

    // index.php?r=ventas_presenciales/index&q=1067&estado=PENDIENTE
    public function index(): void
    {
        $this->requiereLogin(self::ROLES);
        $modelo = $this->modelo('VentasPresencialesVentaModelo');
        $busqueda = trim((string) ($_GET['q'] ?? ''));
        $estado = (string) ($_GET['estado'] ?? '');
        if (!isset(VentasPresencialesVentaModelo::ESTADOS[$estado])) {
            $estado = '';
        }

        $this->vista('index', [
            'ventas' => $modelo->listar($busqueda, $estado),
            'conteo' => $modelo->contarPorEstado(),
            'estados' => VentasPresencialesVentaModelo::ESTADOS,
            'busqueda' => $busqueda,
            'estado' => $estado,
            'mensaje' => $this->tomarMensaje(),
        ]);
    }

    // index.php?r=ventas_presenciales/nueva
    public function nueva(): void
    {
        $this->requiereLogin(self::ROLES);
        $this->formulario();
    }

    // POST index.php?r=ventas_presenciales/registrar
    public function registrar(): void
    {
        $this->requiereLogin(self::ROLES);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('ventas_presenciales/nueva');
        }
        if (!$this->tokenValido()) {
            $this->formulario($_POST, 'La página expiró. Revisa los datos y vuelve a registrar la venta.');
            return;
        }

        try {
            $ventaId = $this->modelo('VentasPresencialesVentaModelo')->registrar($_POST, (int) $this->usuario()['id']);
        } catch (InvalidArgumentException $e) {
            $this->formulario($_POST, $e->getMessage());
            return;
        } catch (PDOException $e) {
            $this->formulario($_POST, 'No se pudo guardar la venta. Revisa los datos e inténtalo de nuevo.');
            return;
        }

        $credito = ($_POST['forma_pago'] ?? '') === 'credito';
        $this->darMensaje($credito
            ? 'Venta registrada en crédito. Cuando financiación apruebe el crédito, la venta pasa a pagada y se factura.'
            : 'Venta registrada. Confirma el pago para generar la factura.');
        $this->redirigir('ventas_presenciales/ver&id=' . $ventaId);
    }

    // index.php?r=ventas_presenciales/ver&id=5
    public function ver(): void
    {
        $this->requiereLogin(self::ROLES);
        $venta = $this->modelo('VentasPresencialesVentaModelo')->buscar((int) ($_GET['id'] ?? 0));
        if ($venta === null) {
            http_response_code(404);
        }

        $this->vista('ver', [
            'venta' => $venta,
            'estados' => VentasPresencialesVentaModelo::ESTADOS,
            'acciones' => $venta ? $this->accionesPara($venta['estado']) : [],
            'mensaje' => $this->tomarMensaje(),
            'error' => $this->tomarMensaje('error'),
            'token' => $this->token(),
        ]);
    }

    // POST index.php?r=ventas_presenciales/cambiarEstado (id, estado)
    public function cambiarEstado(): void
    {
        $this->requiereLogin(self::ROLES);
        $ventaId = (int) ($_POST['id'] ?? 0);
        $nuevo = (string) ($_POST['estado'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $ventaId <= 0) {
            $this->redirigir('ventas_presenciales/index');
        }

        if (!$this->tokenValido()) {
            $this->darMensaje('La página expiró. Vuelve a intentarlo.', 'error');
        } elseif ($nuevo === 'ANULADA' && $this->usuario()['rol'] !== 'admin') {
            $this->darMensaje('Solo un administrador puede anular una venta.', 'error');
        } else {
            try {
                $factura = $this->modelo('VentasPresencialesVentaModelo')->cambiarEstado($ventaId, $nuevo);
                $this->darMensaje($this->mensajeEstado($nuevo, $factura));
            } catch (InvalidArgumentException $e) {
                $this->darMensaje($e->getMessage(), 'error');
            } catch (PDOException $e) {
                $this->darMensaje('No se pudo cambiar el estado de la venta. Inténtalo de nuevo.', 'error');
            }
        }
        $this->redirigir('ventas_presenciales/ver&id=' . $ventaId);
    }

    // Para el formulario: index.php?r=ventas_presenciales/cliente&documento=1067123456
    public function cliente(): void
    {
        $this->requiereLogin(self::ROLES);
        $documento = preg_replace('/[\s.]/', '', (string) ($_GET['documento'] ?? ''));
        $cliente = $documento === ''
            ? null
            : $this->modelo('VentasPresencialesClienteModelo')->buscarPorDocumento($documento);
        $this->json(['encontrado' => $cliente !== null, 'cliente' => $cliente]);
    }

    // API para otros módulos: index.php?r=ventas_presenciales/api&id=5 (o &factura=FV-0005)
    public function api(): void
    {
        $this->requiereLogin(self::ROLES);
        $modelo = $this->modelo('VentasPresencialesVentaModelo');
        if (!empty($_GET['id'])) {
            $venta = $modelo->buscar((int) $_GET['id']);
        } elseif (!empty($_GET['factura'])) {
            $venta = $modelo->buscarPorFactura(trim((string) $_GET['factura']));
        } else {
            $this->json(['error' => 'Envía id o factura'], 400);
            return;
        }
        $venta === null
            ? $this->json(['error' => 'No se encontró la venta'], 404)
            : $this->json($venta);
    }

    private function formulario(array $anterior = [], ?string $error = null): void
    {
        $this->vista('nueva', [
            'catalogo' => $this->modelo('VentasPresencialesVentaModelo')->catalogo(),
            'anterior' => $this->datosAnteriores($anterior),
            'maxUnidades' => VentasPresencialesVentaModelo::MAX_UNIDADES,
            'error' => $error,
            'token' => $this->token(),
        ]);
    }

    // Lo que escribió el vendedor, para no perderlo si hay un error.
    private function datosAnteriores(array $post): array
    {
        $texto = static fn($valor): string => is_scalar($valor) ? trim((string) $valor) : '';
        $unidades = [];
        $motos = (array) ($post['unidad_moto'] ?? []);
        foreach (array_keys($motos) as $i) {
            $unidades[] = [
                'moto_id' => (int) $texto($motos[$i]),
                'chasis' => $texto($post['unidad_chasis'][$i] ?? ''),
                'placa' => $texto($post['unidad_placa'][$i] ?? ''),
            ];
        }
        return [
            'documento' => $texto($post['documento'] ?? ''),
            'nombre' => $texto($post['nombre'] ?? ''),
            'correo' => $texto($post['correo'] ?? ''),
            'telefono' => $texto($post['telefono'] ?? ''),
            'forma_pago' => $texto($post['forma_pago'] ?? 'contado'),
            'unidades' => $unidades ?: [['moto_id' => 0, 'chasis' => '', 'placa' => '']],
        ];
    }

    // Estados a los que puede pasar la venta, con el texto de su botón. Anular solo para admin.
    private function accionesPara(string $estado): array
    {
        $acciones = [];
        foreach (VentasPresencialesVentaModelo::TRANSICIONES[$estado] ?? [] as $destino) {
            if ($destino !== 'ANULADA' || $this->usuario()['rol'] === 'admin') {
                $acciones[$destino] = self::ACCIONES[$destino];
            }
        }
        return $acciones;
    }

    private function mensajeEstado(string $estado, ?string $factura): string
    {
        return match ($estado) {
            'PAGADA' => "Pago confirmado. Factura $factura. Las motos ya aparecen en garantía y en el taller.",
            'EN_CREDITO' => 'La venta pasó a crédito. Financiación debe aprobarlo para facturar.',
            'PENDIENTE' => 'La venta pasó a contado. Confirma el pago para generar la factura.',
            'ENTREGADA' => 'Entrega registrada.',
            'CERRADA' => 'Venta cerrada.',
            'ANULADA' => 'Venta anulada.',
            default => 'Estado actualizado.',
        };
    }

    // Mensajes que sobreviven a la redirección (POST -> redirect -> GET).
    private function darMensaje(string $texto, string $tipo = 'exito'): void
    {
        $_SESSION['ventas_presenciales_' . $tipo] = $texto;
    }

    private function tomarMensaje(string $tipo = 'exito'): ?string
    {
        $texto = $_SESSION['ventas_presenciales_' . $tipo] ?? null;
        unset($_SESSION['ventas_presenciales_' . $tipo]);
        return $texto;
    }

    // Token contra envíos de formularios desde otros sitios.
    private function token(): string
    {
        return $_SESSION['ventas_presenciales_token'] ??= bin2hex(random_bytes(16));
    }

    private function tokenValido(): bool
    {
        return hash_equals($this->token(), (string) ($_POST['token'] ?? ''));
    }

    // ==================== Endpoints JSON aditivos para Vue ====================

    // GET index.php?r=ventas_presenciales/apiSesion
    public function apiSesion(): void
    {
        if (!$this->requiereLoginJson()) {
            return;
        }
        $this->json([
            'autenticado' => true,
            'usuario' => $this->usuario(),
            'token' => $this->token(),
        ]);
    }

    // GET index.php?r=ventas_presenciales/apiListar&q=...&estado=...
    public function apiListar(): void
    {
        if (!$this->requiereLoginJson()) {
            return;
        }
        $modelo = $this->modelo('VentasPresencialesVentaModelo');
        $busqueda = trim((string) ($_GET['q'] ?? ''));
        $estado = (string) ($_GET['estado'] ?? '');
        if (!isset(VentasPresencialesVentaModelo::ESTADOS[$estado])) {
            $estado = '';
        }

        $this->json([
            'ventas' => $modelo->listar($busqueda, $estado),
            'conteo' => $modelo->contarPorEstado(),
            'estados' => VentasPresencialesVentaModelo::ESTADOS,
            'busqueda' => $busqueda,
            'estado' => $estado,
        ]);
    }

    // GET index.php?r=ventas_presenciales/apiCatalogo
    public function apiCatalogo(): void
    {
        if (!$this->requiereLoginJson()) {
            return;
        }
        $this->json([
            'catalogo' => $this->modelo('VentasPresencialesVentaModelo')->catalogo(),
            'maxUnidades' => VentasPresencialesVentaModelo::MAX_UNIDADES,
            'token' => $this->token(),
        ]);
    }

    // GET index.php?r=ventas_presenciales/apiVer&id=5
    public function apiVer(): void
    {
        if (!$this->requiereLoginJson()) {
            return;
        }
        $venta = $this->modelo('VentasPresencialesVentaModelo')->buscar((int) ($_GET['id'] ?? 0));
        if ($venta === null) {
            $this->json(['error' => 'No encontramos esa venta en tienda.'], 404);
            return;
        }

        $this->json([
            'venta' => $venta,
            'estados' => VentasPresencialesVentaModelo::ESTADOS,
            'acciones' => $this->accionesPara($venta['estado']),
            'token' => $this->token(),
        ]);
    }

    // POST index.php?r=ventas_presenciales/apiRegistrar
    public function apiRegistrar(): void
    {
        if (!$this->requiereLoginJson()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido.'], 405);
            return;
        }

        $entrada = $this->leerJson();
        if (!hash_equals($this->token(), (string) ($entrada['token'] ?? ''))) {
            $this->json(['error' => 'La sesión del formulario expiró. Recarga e inténtalo de nuevo.'], 403);
            return;
        }

        // Adapta el arreglo de unidades JSON al formato esperado por el modelo PHP.
        $unidades = is_array($entrada['unidades'] ?? null) ? $entrada['unidades'] : [];
        $datos = [
            'documento' => (string) ($entrada['documento'] ?? ''),
            'nombre' => (string) ($entrada['nombre'] ?? ''),
            'correo' => (string) ($entrada['correo'] ?? ''),
            'telefono' => (string) ($entrada['telefono'] ?? ''),
            'forma_pago' => (string) ($entrada['forma_pago'] ?? 'contado'),
            'unidad_moto' => array_map(static fn($u) => $u['moto_id'] ?? '', $unidades),
            'unidad_chasis' => array_map(static fn($u) => $u['chasis'] ?? '', $unidades),
            'unidad_placa' => array_map(static fn($u) => $u['placa'] ?? '', $unidades),
        ];

        try {
            $ventaId = $this->modelo('VentasPresencialesVentaModelo')->registrar($datos, (int) $this->usuario()['id']);
        } catch (InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 422);
            return;
        } catch (PDOException $e) {
            $this->json(['error' => 'No se pudo guardar la venta. Revisa los datos e inténtalo de nuevo.'], 500);
            return;
        }

        $credito = $datos['forma_pago'] === 'credito';
        $this->json([
            'ok' => true,
            'id' => $ventaId,
            'mensaje' => $credito
                ? 'Venta registrada en crédito. Cuando financiación apruebe el crédito, la venta pasa a pagada y se factura.'
                : 'Venta registrada. Confirma el pago para generar la factura.',
        ], 201);
    }

    // POST index.php?r=ventas_presenciales/apiCambiarEstado
    public function apiCambiarEstado(): void
    {
        if (!$this->requiereLoginJson()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido.'], 405);
            return;
        }

        $entrada = $this->leerJson();
        $ventaId = (int) ($entrada['id'] ?? 0);
        $nuevo = (string) ($entrada['estado'] ?? '');
        if ($ventaId <= 0) {
            $this->json(['error' => 'Venta inválida.'], 400);
            return;
        }
        if (!hash_equals($this->token(), (string) ($entrada['token'] ?? ''))) {
            $this->json(['error' => 'La página expiró. Vuelve a intentarlo.'], 403);
            return;
        }
        if ($nuevo === 'ANULADA' && $this->usuario()['rol'] !== 'admin') {
            $this->json(['error' => 'Solo un administrador puede anular una venta.'], 403);
            return;
        }

        try {
            $modelo = $this->modelo('VentasPresencialesVentaModelo');
            $factura = $modelo->cambiarEstado($ventaId, $nuevo);
            $venta = $modelo->buscar($ventaId);
            $this->json([
                'ok' => true,
                'factura' => $factura,
                'mensaje' => $this->mensajeEstado($nuevo, $factura),
                'venta' => $venta,
                'acciones' => $venta ? $this->accionesPara($venta['estado']) : [],
            ]);
        } catch (InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 422);
        } catch (PDOException $e) {
            $this->json(['error' => 'No se pudo cambiar el estado de la venta. Inténtalo de nuevo.'], 500);
        }
    }

    private function requiereLoginJson(): bool
    {
        $usuario = $this->usuario();
        if ($usuario === null) {
            $this->json(['error' => 'Debes iniciar sesión para acceder a Ventas en tienda.'], 401);
            return false;
        }
        if (!in_array($usuario['rol'], self::ROLES, true)) {
            $this->json(['error' => 'No tienes permiso para entrar aquí.'], 403);
            return false;
        }
        return true;
    }

    private function leerJson(): array
    {
        $raw = file_get_contents('php://input');
        $decodificado = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decodificado) ? $decodificado : $_POST;
    }
}
