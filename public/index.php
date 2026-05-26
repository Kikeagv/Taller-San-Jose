<?php
declare(strict_types=1);

session_start();
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/El_Salvador');

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/../config/database.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function excerpt(?string $value, int $limit = 60): string
{
    $text = trim((string)$value);
    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
}

function route(): string
{
    return $_GET['r'] ?? 'dashboard';
}

function redirect(string $route): never
{
    header('Location: ?r=' . $route);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): void
{
    if (!current_user()) {
        redirect('login');
    }
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function post(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function get_int(string $key): int
{
    return max(0, (int)($_GET[$key] ?? 0));
}

function query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function scalar(string $sql, array $params = []): int|string|null
{
    $value = query($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}

function layout(string $title, callable $content): void
{
    $user = current_user();
    $active = route();
    $nav = [
        'dashboard' => 'Dashboard',
        'repuestos' => 'Repuestos',
        'movimientos' => 'Movimientos',
        'proveedores' => 'Proveedores',
        'compras' => 'Compras',
        'servicios' => 'Servicios',
        'clientes' => 'Clientes',
        'reportes' => 'Reportes',
    ];
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?> | Taller San José</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/css/app.css" rel="stylesheet">
    </head>
    <body>
    <?php if ($user): ?>
        <?php $flash = flash(); ?>
        <div class="container-fluid app-shell">
            <div class="row">
                <aside class="col-md-2 sidebar p-3 no-print">
                    <div class="brand mb-4">Taller San José</div>
                    <nav class="nav flex-column">
                        <?php foreach ($nav as $key => $label): ?>
                            <a class="nav-link <?= str_starts_with($active, $key) ? 'active' : '' ?>" href="?r=<?= h($key) ?>"><?= h($label) ?></a>
                        <?php endforeach; ?>
                    </nav>
                    <div class="mt-4 small text-white-50">
                        <?= h($user['nombre']) ?><br>
                        Rol: <?= h($user['rol']) ?>
                    </div>
                    <a class="btn btn-sm btn-outline-light mt-3" href="?r=logout">Cerrar sesión</a>
                </aside>
                <main class="col-md-10 p-4" id="app-main">
                    <?php if ($flash): ?>
                        <div class="alert alert-<?= h($flash['type']) ?> no-print"><?= h($flash['message']) ?></div>
                    <?php endif; ?>
                    <?php $content(); ?>
                </main>
            </div>
        </div>
    <?php else: ?>
        <?php $content(); ?>
    <?php endif; ?>
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    </body>
    </html>
    <?php
}

function table_empty(int $colspan, string $message = 'No hay registros para mostrar.'): void
{
    echo '<tr><td colspan="' . $colspan . '" class="text-center text-muted py-4">' . h($message) . '</td></tr>';
}

function csv_response(string $filename, array $headers, array $rows, callable $map): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers, ',', '"', '\\');
    foreach ($rows as $row) {
        fputcsv($out, array_map('strval', $map($row)), ',', '"', '\\');
    }
    fclose($out);
    exit;
}

function login_page(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && current_user()) {
        redirect('dashboard');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $correo = trim((string)post('correo'));
        $password = (string)post('password');
        $user = query('SELECT * FROM usuarios WHERE correo = ? AND estado = 1', [$correo])->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id_usuario' => $user['id_usuario'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'rol' => $user['rol'],
            ];
            redirect('dashboard');
        }

        flash('Correo o contraseña inválidos.', 'danger');
    }

    layout('Iniciar sesión', function () {
        $flash = flash();
        ?>
        <div class="container login-shell py-5">
            <div class="row justify-content-center">
                <div class="col-md-5 col-lg-4">
                    <div class="content-card login-card p-4 pt-5">
                        <h1 class="h4 mb-1">Taller Mecánico San José</h1>
                        <p class="text-muted mb-4">Sistema de gestión de inventario</p>
                        <?php if ($flash): ?>
                            <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label" for="login-correo">Correo</label>
                                <input class="form-control" id="login-correo" type="email" name="correo" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="login-password">Contraseña</label>
                                <input class="form-control" id="login-password" type="password" name="password" required>
                            </div>
                            <button class="btn btn-success w-100">Ingresar</button>
                        </form>
                        <p class="form-help mt-3 mb-0">Demo: admin@taller.test / password</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    });
}

function dashboard_page(): void
{
    require_auth();
    $lowStockParts = query(
        "SELECT codigo, nombre, stock_actual, stock_minimo, ubicacion
         FROM repuestos
         WHERE estado = 1 AND stock_actual <= stock_minimo
         ORDER BY stock_actual ASC, nombre ASC
         LIMIT 5"
    )->fetchAll();
    $pendingPurchases = query(
        "SELECT c.id_compra, c.fecha_compra, c.total_estimado, p.nombre proveedor
         FROM compras c
         JOIN proveedores p ON p.id_proveedor = c.id_proveedor
         WHERE c.estado = 'pendiente'
         ORDER BY c.fecha_compra DESC, c.id_compra DESC
         LIMIT 5"
    )->fetchAll();
    $movements = query(
        "SELECT m.*, r.nombre repuesto, u.nombre usuario
         FROM movimientos_inventario m
         JOIN repuestos r ON r.id_repuesto = m.id_repuesto
         JOIN usuarios u ON u.id_usuario = m.id_usuario
         ORDER BY m.fecha_movimiento DESC LIMIT 5"
    )->fetchAll();

    layout('Dashboard', function () use ($lowStockParts, $pendingPurchases, $movements) {
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Dashboard</h1>
                <p class="text-muted mb-0">Resumen operativo del inventario.</p>
            </div>
        </div>
        <div class="content-card p-3 mb-4">
            <h2 class="h5 mb-3">Stock bajo</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Código</th><th>Repuesto</th><th>Stock</th><th>Mínimo</th><th>Ubicación</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStockParts as $row): ?>
                        <tr>
                            <td><?= h($row['codigo']) ?></td>
                            <td><?= h($row['nombre']) ?></td>
                            <td><span class="badge badge-stock-low"><?= h((string)$row['stock_actual']) ?></span></td>
                            <td><?= h((string)$row['stock_minimo']) ?></td>
                            <td><?= h($row['ubicacion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowStockParts) table_empty(5, 'No hay repuestos bajo stock mínimo.'); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="content-card p-3 mb-4">
            <h2 class="h5 mb-3">Compras pendientes</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>#</th><th>Fecha</th><th>Proveedor</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($pendingPurchases as $row): ?>
                        <tr>
                            <td><?= h((string)$row['id_compra']) ?></td>
                            <td><?= h($row['fecha_compra']) ?></td>
                            <td><?= h($row['proveedor']) ?></td>
                            <td>$<?= number_format((float)$row['total_estimado'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$pendingPurchases) table_empty(4, 'No hay compras pendientes.'); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="content-card p-3">
            <h2 class="h5 mb-3">Últimos movimientos</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Fecha</th><th>Repuesto</th><th>Tipo</th><th>Cantidad</th><th>Usuario</th></tr></thead>
                    <tbody>
                    <?php foreach ($movements as $row): ?>
                        <tr>
                            <td><?= h($row['fecha_movimiento']) ?></td>
                            <td><?= h($row['repuesto']) ?></td>
                            <td><span class="badge text-bg-<?= $row['tipo_movimiento'] === 'entrada' ? 'success' : 'secondary' ?>"><?= h($row['tipo_movimiento']) ?></span></td>
                            <td><?= h((string)$row['cantidad']) ?></td>
                            <td><?= h($row['usuario']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$movements) table_empty(5); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    });
}

function repuestos_page(): void
{
    require_auth();
    if (($_GET['action'] ?? '') === 'delete') {
        $id = get_int('id');
        if ($id) {
            query('UPDATE repuestos SET estado = 0 WHERE id_repuesto = ?', [$id]);
            flash('Repuesto desactivado.');
        }
        redirect('repuestos');
    }
    $q = trim((string)($_GET['q'] ?? ''));
    $params = [];
    $where = 'WHERE estado = 1';
    if ($q !== '') {
        $where .= ' AND (codigo LIKE ? OR nombre LIKE ? OR numero_parte LIKE ?)';
        $params = ["%$q%", "%$q%", "%$q%"];
    }
    $rows = query("SELECT * FROM repuestos $where ORDER BY nombre", $params)->fetchAll();

    layout('Repuestos', function () use ($rows, $q) {
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h1 class="h3 mb-1">Repuestos</h1><p class="text-muted mb-0">Catálogo y existencias.</p></div>
            <a class="btn btn-success" href="?r=repuestos_form">Nuevo repuesto</a>
        </div>
        <form class="row g-2 mb-3 no-print">
            <input type="hidden" name="r" value="repuestos">
            <div class="col-md-5"><input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Buscar por código, nombre o número de parte"></div>
            <div class="col-auto"><button class="btn btn-outline-secondary">Buscar</button></div>
        </form>
        <div class="content-card p-3 table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Código</th><th>Nombre</th><th>Marca</th><th>Ubicación</th><th>Stock</th><th>Precio</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= h($row['codigo']) ?></td>
                        <td><?= h($row['nombre']) ?><div class="small text-muted"><?= h($row['numero_parte']) ?></div></td>
                        <td><?= h($row['marca']) ?></td>
                        <td><?= h($row['ubicacion']) ?></td>
                        <td>
                            <?= h((string)$row['stock_actual']) ?>
                            <?php if ((int)$row['stock_actual'] <= (int)$row['stock_minimo']): ?>
                                <span class="badge badge-stock-low">stock bajo</span>
                            <?php endif; ?>
                        </td>
                        <td>$<?= number_format((float)$row['precio_referencia'], 2) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="?r=repuestos_form&id=<?= (int)$row['id_repuesto'] ?>">Editar</a>
                            <a class="btn btn-sm btn-outline-danger" data-confirm="¿Desactivar repuesto?" href="?r=repuestos&action=delete&id=<?= (int)$row['id_repuesto'] ?>">Desactivar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows) table_empty(7); ?>
                </tbody>
            </table>
        </div>
        <?php
    });
}

function repuestos_form_page(): void
{
    require_auth();
    $id = get_int('id');
    $row = $id ? query('SELECT * FROM repuestos WHERE id_repuesto = ?', [$id])->fetch() : null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            trim((string)post('codigo')),
            trim((string)post('numero_parte')),
            trim((string)post('nombre')),
            trim((string)post('descripcion')),
            trim((string)post('marca')),
            trim((string)post('ubicacion')),
            (float)post('precio_referencia', 0),
            max(0, (int)post('stock_minimo', 0)),
        ];
        if ($data[0] === '' || $data[2] === '') {
            flash('Código y nombre son obligatorios.', 'danger');
            redirect('repuestos_form' . ($id ? '&id=' . $id : ''));
        }
        try {
            if ($id) {
                query('UPDATE repuestos SET codigo=?, numero_parte=?, nombre=?, descripcion=?, marca=?, ubicacion=?, precio_referencia=?, stock_minimo=? WHERE id_repuesto=?', [...$data, $id]);
                flash('Repuesto actualizado.');
            } else {
                query('INSERT INTO repuestos (codigo, numero_parte, nombre, descripcion, marca, ubicacion, precio_referencia, stock_minimo) VALUES (?,?,?,?,?,?,?,?)', $data);
                flash('Repuesto creado.');
            }
            redirect('repuestos');
        } catch (PDOException $e) {
            flash('No se pudo guardar. Revise si el código ya existe.', 'danger');
        }
    }

    layout($id ? 'Editar repuesto' : 'Nuevo repuesto', function () use ($row, $id) {
        $v = fn(string $key, mixed $default = '') => h((string)($row[$key] ?? $default));
        ?>
        <h1 class="h3 mb-3"><?= $id ? 'Editar repuesto' : 'Nuevo repuesto' ?></h1>
        <div class="content-card p-4">
            <form method="post" class="row g-3">
                <div class="col-md-3"><label class="form-label">Código</label><input class="form-control" name="codigo" value="<?= $v('codigo') ?>" required></div>
                <div class="col-md-3"><label class="form-label">Número de parte</label><input class="form-control" name="numero_parte" value="<?= $v('numero_parte') ?>"></div>
                <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?= $v('nombre') ?>" required></div>
                <div class="col-md-3"><label class="form-label">Marca</label><input class="form-control" name="marca" value="<?= $v('marca') ?>"></div>
                <div class="col-md-3"><label class="form-label">Ubicación</label><input class="form-control" name="ubicacion" value="<?= $v('ubicacion') ?>"></div>
                <div class="col-md-3"><label class="form-label">Precio referencia</label><input class="form-control" type="number" step="0.01" name="precio_referencia" value="<?= $v('precio_referencia', '0') ?>"></div>
                <div class="col-md-3"><label class="form-label">Stock mínimo</label><input class="form-control" type="number" name="stock_minimo" value="<?= $v('stock_minimo', '0') ?>"></div>
                <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" rows="3"><?= $v('descripcion') ?></textarea></div>
                <div class="col-12"><button class="btn btn-success">Guardar</button> <a class="btn btn-outline-secondary" href="?r=repuestos">Cancelar</a></div>
            </form>
        </div>
        <?php
    });
}

function movimientos_page(): void
{
    require_auth();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idRepuesto = (int)post('id_repuesto');
        $tipo = (string)post('tipo_movimiento');
        $cantidad = (int)post('cantidad');
        $motivo = trim((string)post('motivo'));
        $referencia = trim((string)post('referencia'));
        $repuesto = query('SELECT * FROM repuestos WHERE id_repuesto = ?', [$idRepuesto])->fetch();

        if (!$repuesto || !in_array($tipo, ['entrada', 'salida'], true) || $cantidad <= 0 || $motivo === '') {
            flash('Revise los datos del movimiento.', 'danger');
            redirect('movimientos');
        }
        if ($tipo === 'salida' && (int)$repuesto['stock_actual'] < $cantidad) {
            flash('No hay stock suficiente para registrar la salida.', 'danger');
            redirect('movimientos');
        }

        db()->beginTransaction();
        try {
            $delta = $tipo === 'entrada' ? $cantidad : -$cantidad;
            query('UPDATE repuestos SET stock_actual = stock_actual + ? WHERE id_repuesto = ?', [$delta, $idRepuesto]);
            query('INSERT INTO movimientos_inventario (id_repuesto, id_usuario, tipo_movimiento, cantidad, motivo, referencia) VALUES (?,?,?,?,?,?)', [
                $idRepuesto, current_user()['id_usuario'], $tipo, $cantidad, $motivo, $referencia
            ]);
            db()->commit();
            flash('Movimiento registrado.');
        } catch (Throwable $e) {
            db()->rollBack();
            flash('No se pudo registrar el movimiento.', 'danger');
        }
        redirect('movimientos');
    }

    $repuestos = query('SELECT id_repuesto, codigo, nombre, stock_actual FROM repuestos WHERE estado = 1 ORDER BY nombre')->fetchAll();
    $rows = query(
        "SELECT m.*, r.nombre repuesto, r.codigo, u.nombre usuario
         FROM movimientos_inventario m
         JOIN repuestos r ON r.id_repuesto = m.id_repuesto
         JOIN usuarios u ON u.id_usuario = m.id_usuario
         ORDER BY m.fecha_movimiento DESC LIMIT 50"
    )->fetchAll();

    layout('Movimientos', function () use ($repuestos, $rows) {
        ?>
        <h1 class="h3 mb-3">Movimientos de inventario</h1>
        <div class="content-card p-4 mb-4 no-print">
            <form method="post" class="row g-3">
                <div class="col-md-4"><label class="form-label">Repuesto</label><select class="form-select" name="id_repuesto" required><?php foreach ($repuestos as $r): ?><option value="<?= (int)$r['id_repuesto'] ?>"><?= h($r['nombre']) ?> (stock: <?= (int)$r['stock_actual'] ?>)</option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="form-label">Tipo</label><select class="form-select" name="tipo_movimiento"><option value="entrada">Entrada</option><option value="salida">Salida</option></select></div>
                <div class="col-md-2"><label class="form-label">Cantidad</label><input class="form-control" type="number" min="1" name="cantidad" required></div>
                <div class="col-md-2"><label class="form-label">Referencia</label><input class="form-control" name="referencia"></div>
                <div class="col-md-12"><label class="form-label">Motivo</label><input class="form-control" name="motivo" required></div>
                <div class="col-12"><button class="btn btn-success">Registrar movimiento</button></div>
            </form>
        </div>
        <div class="content-card p-3 table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Fecha</th><th>Repuesto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Usuario</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr><td><?= h($row['fecha_movimiento']) ?></td><td><?= h($row['codigo'] . ' - ' . $row['repuesto']) ?></td><td><?= h($row['tipo_movimiento']) ?></td><td><?= (int)$row['cantidad'] ?></td><td><?= h($row['motivo']) ?></td><td><?= h($row['usuario']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$rows) table_empty(6); ?>
                </tbody>
            </table>
        </div>
        <?php
    });
}

function generic_crud(string $entity): void
{
    require_auth();
    $config = [
        'proveedores' => [
            'pk' => 'id_proveedor',
            'title' => 'Proveedores',
            'fields' => ['nombre' => 'Nombre', 'contacto' => 'Contacto', 'telefono' => 'Teléfono', 'correo' => 'Correo', 'direccion' => 'Dirección', 'productos_ofrecidos' => 'Productos ofrecidos'],
            'search' => ['nombre', 'contacto'],
        ],
        'clientes' => [
            'pk' => 'id_cliente',
            'title' => 'Clientes',
            'fields' => ['nombre' => 'Nombre', 'telefono' => 'Teléfono', 'correo' => 'Correo', 'direccion' => 'Dirección'],
            'search' => ['nombre', 'telefono'],
        ],
    ][$entity];

    $action = $_GET['action'] ?? 'index';
    $id = get_int('id');

    if ($action === 'delete' && $id) {
        query("UPDATE $entity SET estado = 0 WHERE {$config['pk']} = ?", [$id]);
        flash($config['title'] . ': registro desactivado.');
        redirect($entity);
    }

    if ($action === 'form') {
        $row = $id ? query("SELECT * FROM $entity WHERE {$config['pk']} = ?", [$id])->fetch() : null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values = [];
            foreach ($config['fields'] as $field => $label) {
                $values[$field] = trim((string)post($field));
            }
            if ($values['nombre'] === '') {
                flash('El nombre es obligatorio.', 'danger');
                redirect($entity . '&action=form' . ($id ? '&id=' . $id : ''));
            }
            if ($id) {
                $sets = implode(', ', array_map(fn($f) => "$f = ?", array_keys($values)));
                query("UPDATE $entity SET $sets WHERE {$config['pk']} = ?", [...array_values($values), $id]);
                flash('Registro actualizado.');
            } else {
                $fields = implode(', ', array_keys($values));
                $marks = implode(', ', array_fill(0, count($values), '?'));
                query("INSERT INTO $entity ($fields) VALUES ($marks)", array_values($values));
                flash('Registro creado.');
            }
            redirect($entity);
        }
        layout(($id ? 'Editar ' : 'Nuevo ') . strtolower($config['title']), function () use ($config, $row, $entity) {
            ?>
            <h1 class="h3 mb-3"><?= $row ? 'Editar' : 'Nuevo' ?> <?= h(strtolower($config['title'])) ?></h1>
            <div class="content-card p-4">
                <form method="post" class="row g-3">
                    <?php foreach ($config['fields'] as $field => $label): ?>
                        <div class="col-md-6">
                            <label class="form-label"><?= h($label) ?></label>
                            <input class="form-control" name="<?= h($field) ?>" value="<?= h($row[$field] ?? '') ?>" <?= $field === 'nombre' ? 'required' : '' ?>>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-12"><button class="btn btn-success">Guardar</button> <a class="btn btn-outline-secondary" href="?r=<?= h($entity) ?>">Cancelar</a></div>
                </form>
            </div>
            <?php
        });
        return;
    }

    $q = trim((string)($_GET['q'] ?? ''));
    $params = [];
    $where = 'WHERE estado = 1';
    if ($q !== '') {
        $parts = array_map(fn($field) => "$field LIKE ?", $config['search']);
        $where .= ' AND (' . implode(' OR ', $parts) . ')';
        $params = array_fill(0, count($config['search']), "%$q%");
    }
    $rows = query("SELECT * FROM $entity $where ORDER BY nombre", $params)->fetchAll();

    layout($config['title'], function () use ($config, $rows, $q, $entity) {
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0"><?= h($config['title']) ?></h1>
            <a class="btn btn-success" href="?r=<?= h($entity) ?>&action=form">Nuevo</a>
        </div>
        <form class="row g-2 mb-3 no-print"><input type="hidden" name="r" value="<?= h($entity) ?>"><div class="col-md-5"><input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Buscar"></div><div class="col-auto"><button class="btn btn-outline-secondary">Buscar</button></div></form>
        <div class="content-card p-3 table-responsive">
            <table class="table align-middle">
                <thead><tr><?php foreach ($config['fields'] as $label): ?><th><?= h($label) ?></th><?php endforeach; ?><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach (array_keys($config['fields']) as $field): ?><td><?= h($row[$field]) ?></td><?php endforeach; ?>
                        <td class="text-end">
                            <?php if ($entity === 'clientes'): ?><a class="btn btn-sm btn-outline-secondary" href="?r=vehiculos&id_cliente=<?= (int)$row['id_cliente'] ?>">Vehículos</a><?php endif; ?>
                            <a class="btn btn-sm btn-outline-primary" href="?r=<?= h($entity) ?>&action=form&id=<?= (int)$row[$config['pk']] ?>">Editar</a>
                            <a class="btn btn-sm btn-outline-danger" data-confirm="¿Desactivar registro?" href="?r=<?= h($entity) ?>&action=delete&id=<?= (int)$row[$config['pk']] ?>">Desactivar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows) table_empty(count($config['fields']) + 1); ?>
                </tbody>
            </table>
        </div>
        <?php
    });
}

function vehiculos_page(): void
{
    require_auth();
    $clienteId = get_int('id_cliente');
    if (!$clienteId) redirect('clientes');
    $cliente = query('SELECT * FROM clientes WHERE id_cliente = ?', [$clienteId])->fetch();
    if (!$cliente) redirect('clientes');
    $editId = get_int('edit');
    $editRow = $editId ? query('SELECT * FROM vehiculos WHERE id_vehiculo = ? AND id_cliente = ?', [$editId, $clienteId])->fetch() : null;

    if (($_GET['action'] ?? '') === 'delete') {
        $id = get_int('id');
        if ($id) {
            query('UPDATE vehiculos SET estado = 0 WHERE id_vehiculo = ? AND id_cliente = ?', [$id, $clienteId]);
            flash('Vehículo desactivado.');
        }
        redirect('vehiculos&id_cliente=' . $clienteId);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)post('id_vehiculo', 0);
        $data = [
            trim((string)post('marca')),
            trim((string)post('modelo')),
            (int)post('anio', 0),
            strtoupper(trim((string)post('placa'))),
            trim((string)post('tipo_motor')),
            trim((string)post('color')),
        ];
        if ($data[0] === '' || $data[1] === '' || $data[3] === '') {
            flash('Marca, modelo y placa son obligatorios.', 'danger');
            redirect('vehiculos&id_cliente=' . $clienteId);
        }
        try {
            if ($id) {
                query('UPDATE vehiculos SET marca=?, modelo=?, anio=?, placa=?, tipo_motor=?, color=? WHERE id_vehiculo=? AND id_cliente=?', [...$data, $id, $clienteId]);
            } else {
                query('INSERT INTO vehiculos (marca, modelo, anio, placa, tipo_motor, color, id_cliente) VALUES (?,?,?,?,?,?,?)', [...$data, $clienteId]);
            }
            flash('Vehículo guardado.');
        } catch (PDOException $e) {
            flash('No se pudo guardar. Revise si la placa ya existe.', 'danger');
        }
        redirect('vehiculos&id_cliente=' . $clienteId);
    }

    $rows = query('SELECT * FROM vehiculos WHERE id_cliente = ? AND estado = 1 ORDER BY marca, modelo', [$clienteId])->fetchAll();
        layout('Vehículos', function () use ($cliente, $rows, $clienteId, $editRow) {
            $v = fn(string $key) => h((string)($editRow[$key] ?? ''));
            ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h1 class="h3 mb-1">Vehículos de <?= h($cliente['nombre']) ?></h1><p class="text-muted mb-0"><?= h($cliente['telefono']) ?></p></div>
            <a class="btn btn-outline-secondary" href="?r=clientes">Volver</a>
        </div>
        <div class="content-card p-4 mb-4 no-print">
            <form method="post" class="row g-3">
                <input type="hidden" name="id_vehiculo" value="<?= h((string)($editRow['id_vehiculo'] ?? '')) ?>">
                <div class="col-md-2"><label class="form-label">Marca</label><input class="form-control" name="marca" value="<?= $v('marca') ?>" required></div>
                <div class="col-md-2"><label class="form-label">Modelo</label><input class="form-control" name="modelo" value="<?= $v('modelo') ?>" required></div>
                <div class="col-md-2"><label class="form-label">Año</label><input class="form-control" type="number" name="anio" value="<?= $v('anio') ?>"></div>
                <div class="col-md-2"><label class="form-label">Placa</label><input class="form-control" name="placa" value="<?= $v('placa') ?>" required></div>
                <div class="col-md-2"><label class="form-label">Motor</label><input class="form-control" name="tipo_motor" value="<?= $v('tipo_motor') ?>"></div>
                <div class="col-md-2"><label class="form-label">Color</label><input class="form-control" name="color" value="<?= $v('color') ?>"></div>
                <div class="col-12">
                    <button class="btn btn-success"><?= $editRow ? 'Actualizar vehículo' : 'Agregar vehículo' ?></button>
                    <?php if ($editRow): ?><a class="btn btn-outline-secondary" href="?r=vehiculos&id_cliente=<?= (int)$clienteId ?>">Cancelar edición</a><?php endif; ?>
                </div>
            </form>
        </div>
        <div class="content-card p-3 table-responsive">
            <table class="table"><thead><tr><th>Marca</th><th>Modelo</th><th>Año</th><th>Placa</th><th>Motor</th><th>Color</th><th></th></tr></thead><tbody>
                <?php foreach ($rows as $row): ?><tr><td><?= h($row['marca']) ?></td><td><?= h($row['modelo']) ?></td><td><?= h((string)$row['anio']) ?></td><td><?= h($row['placa']) ?></td><td><?= h($row['tipo_motor']) ?></td><td><?= h($row['color']) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="?r=vehiculos&id_cliente=<?= (int)$clienteId ?>&edit=<?= (int)$row['id_vehiculo'] ?>">Editar</a> <a class="btn btn-sm btn-outline-danger" data-confirm="¿Desactivar vehículo?" href="?r=vehiculos&id_cliente=<?= (int)$clienteId ?>&action=delete&id=<?= (int)$row['id_vehiculo'] ?>">Desactivar</a></td></tr><?php endforeach; ?>
                <?php if (!$rows) table_empty(7); ?>
            </tbody></table>
        </div>
        <?php
    });
}

function servicios_page(): void
{
    require_auth();
    $action = $_GET['action'] ?? 'index';

    if ($action === 'view') {
        $id = get_int('id');
        $service = query(
            "SELECT s.*, v.placa, v.marca, v.modelo, c.nombre cliente
             FROM servicios s
             JOIN vehiculos v ON v.id_vehiculo = s.id_vehiculo
             JOIN clientes c ON c.id_cliente = v.id_cliente
             WHERE s.id_servicio = ?",
            [$id]
        )->fetch();
        if (!$service) {
            flash('Servicio no encontrado.', 'danger');
            redirect('servicios');
        }
        $details = query(
            "SELECT ds.*, r.codigo, r.nombre
             FROM detalle_servicio_repuesto ds
             JOIN repuestos r ON r.id_repuesto = ds.id_repuesto
             WHERE ds.id_servicio = ?
             ORDER BY r.nombre",
            [$id]
        )->fetchAll();

        layout('Detalle de servicio', function () use ($service, $details) {
            ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h3 mb-1">Servicio #<?= (int)$service['id_servicio'] ?></h1>
                    <p class="text-muted mb-0"><?= h($service['cliente']) ?> · <?= h($service['placa']) ?> · <?= h($service['marca'] . ' ' . $service['modelo']) ?></p>
                </div>
                <a class="btn btn-outline-secondary" href="?r=servicios">Volver</a>
            </div>
            <div class="content-card p-4 mb-4">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Fecha</div><div class="fw-semibold"><?= h($service['fecha_servicio']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Kilometraje</div><div class="fw-semibold"><?= h((string)$service['kilometraje']) ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Observaciones</div><div><?= h($service['observaciones'] ?: 'Sin observaciones') ?></div></div>
                    <div class="col-12"><div class="text-muted small">Trabajo realizado</div><div><?= nl2br(h($service['descripcion'])) ?></div></div>
                </div>
            </div>
            <div class="content-card p-3 table-responsive">
                <h2 class="h5 mb-3">Repuestos utilizados</h2>
                <table class="table align-middle">
                    <thead><tr><th>Código</th><th>Repuesto</th><th>Cantidad</th></tr></thead>
                    <tbody>
                    <?php foreach ($details as $row): ?>
                        <tr><td><?= h($row['codigo']) ?></td><td><?= h($row['nombre']) ?></td><td><?= (int)$row['cantidad_usada'] ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$details) table_empty(3, 'No se registraron repuestos para este servicio.'); ?>
                    </tbody>
                </table>
            </div>
            <?php
        });
        return;
    }

    if ($action === 'form') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idVehiculo = (int)post('id_vehiculo');
            $fecha = (string)post('fecha_servicio');
            $descripcion = trim((string)post('descripcion'));
            $kilometraje = max(0, (int)post('kilometraje', 0));
            $observaciones = trim((string)post('observaciones'));
            $repuestos = $_POST['id_repuesto'] ?? [];
            $cantidades = $_POST['cantidad_usada'] ?? [];

            if ($idVehiculo <= 0 || $fecha === '' || $descripcion === '') {
                flash('Vehículo, fecha y descripción son obligatorios.', 'danger');
                redirect('servicios&action=form');
            }

            $validRows = [];
            foreach ($repuestos as $i => $idRepuesto) {
                $idRepuesto = (int)$idRepuesto;
                $cantidad = (int)($cantidades[$i] ?? 0);
                if ($idRepuesto > 0 && $cantidad > 0) {
                    $validRows[] = [$idRepuesto, $cantidad];
                }
            }

            db()->beginTransaction();
            try {
                foreach ($validRows as [$idRepuesto, $cantidad]) {
                    $stock = (int)scalar('SELECT stock_actual FROM repuestos WHERE id_repuesto = ? AND estado = 1 FOR UPDATE', [$idRepuesto]);
                    if ($stock < $cantidad) {
                        throw new RuntimeException('Stock insuficiente.');
                    }
                }

                query('INSERT INTO servicios (id_vehiculo, fecha_servicio, descripcion, kilometraje, observaciones) VALUES (?,?,?,?,?)', [
                    $idVehiculo, $fecha, $descripcion, $kilometraje ?: null, $observaciones
                ]);
                $idServicio = (int)db()->lastInsertId();

                foreach ($validRows as [$idRepuesto, $cantidad]) {
                    query('INSERT INTO detalle_servicio_repuesto (id_servicio, id_repuesto, cantidad_usada) VALUES (?,?,?)', [$idServicio, $idRepuesto, $cantidad]);
                    query('UPDATE repuestos SET stock_actual = stock_actual - ? WHERE id_repuesto = ?', [$cantidad, $idRepuesto]);
                    query('INSERT INTO movimientos_inventario (id_repuesto, id_usuario, tipo_movimiento, cantidad, motivo, referencia) VALUES (?,?,?,?,?,?)', [
                        $idRepuesto, current_user()['id_usuario'], 'salida', $cantidad, 'Uso en servicio de taller', 'Servicio #' . $idServicio
                    ]);
                }

                db()->commit();
                flash('Servicio registrado e inventario actualizado.');
                redirect('servicios');
            } catch (Throwable $e) {
                db()->rollBack();
                flash('No se pudo registrar el servicio. Revise stock y datos ingresados.', 'danger');
            }
        }

        $vehiculos = query(
            "SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, c.nombre cliente
             FROM vehiculos v
             JOIN clientes c ON c.id_cliente = v.id_cliente
             WHERE v.estado = 1 AND c.estado = 1
             ORDER BY c.nombre, v.placa"
        )->fetchAll();
        $repuestos = query('SELECT id_repuesto, codigo, nombre, stock_actual FROM repuestos WHERE estado = 1 ORDER BY nombre')->fetchAll();

        layout('Nuevo servicio', function () use ($vehiculos, $repuestos) {
            ?>
            <h1 class="h3 mb-3">Nuevo servicio</h1>
            <div class="content-card p-4">
                <form method="post">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Vehículo</label>
                            <select class="form-select" name="id_vehiculo" required>
                                <?php foreach ($vehiculos as $v): ?>
                                    <option value="<?= (int)$v['id_vehiculo'] ?>"><?= h($v['cliente'] . ' · ' . $v['placa'] . ' · ' . $v['marca'] . ' ' . $v['modelo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Fecha</label><input class="form-control" type="date" name="fecha_servicio" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Kilometraje</label><input class="form-control" type="number" min="0" name="kilometraje"></div>
                        <div class="col-12"><label class="form-label">Descripción del trabajo</label><textarea class="form-control" name="descripcion" rows="3" required></textarea></div>
                        <div class="col-12"><label class="form-label">Observaciones</label><input class="form-control" name="observaciones"></div>
                    </div>
                    <h2 class="h5 mb-3">Repuestos utilizados</h2>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Repuesto</th><th>Cantidad usada</th><th></th></tr></thead>
                            <tbody data-service-rows>
                                <tr data-service-row>
                                    <td><select class="form-select" name="id_repuesto[]"><option value="">Sin repuesto</option><?php foreach ($repuestos as $r): ?><option value="<?= (int)$r['id_repuesto'] ?>"><?= h($r['codigo'] . ' - ' . $r['nombre'] . ' (stock: ' . $r['stock_actual'] . ')') ?></option><?php endforeach; ?></select></td>
                                    <td><input class="form-control" type="number" min="1" name="cantidad_usada[]"></td>
                                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" data-remove-service-row hidden>Eliminar</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <template data-service-template><tr data-service-row><td><select class="form-select" name="id_repuesto[]"><option value="">Sin repuesto</option><?php foreach ($repuestos as $r): ?><option value="<?= (int)$r['id_repuesto'] ?>"><?= h($r['codigo'] . ' - ' . $r['nombre'] . ' (stock: ' . $r['stock_actual'] . ')') ?></option><?php endforeach; ?></select></td><td><input class="form-control" type="number" min="1" name="cantidad_usada[]"></td><td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" data-remove-service-row>Eliminar</button></td></tr></template>
                    <button type="button" class="btn btn-outline-secondary" data-add-service-row>Agregar repuesto</button>
                    <button class="btn btn-success">Guardar servicio</button>
                    <a class="btn btn-outline-secondary" href="?r=servicios">Cancelar</a>
                </form>
            </div>
            <?php
        });
        return;
    }

    $rows = query(
        "SELECT s.*, v.placa, v.marca, v.modelo, c.nombre cliente,
                COUNT(ds.id_detalle_servicio) repuestos_usados
         FROM servicios s
         JOIN vehiculos v ON v.id_vehiculo = s.id_vehiculo
         JOIN clientes c ON c.id_cliente = v.id_cliente
         LEFT JOIN detalle_servicio_repuesto ds ON ds.id_servicio = s.id_servicio
         GROUP BY s.id_servicio, v.placa, v.marca, v.modelo, c.nombre
         ORDER BY s.fecha_servicio DESC, s.id_servicio DESC"
    )->fetchAll();

    layout('Servicios', function () use ($rows) {
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h1 class="h3 mb-1">Servicios</h1><p class="text-muted mb-0">Órdenes de taller y repuestos utilizados.</p></div>
            <a class="btn btn-success" href="?r=servicios&action=form">Nuevo servicio</a>
        </div>
        <div class="content-card p-3 table-responsive">
            <table class="table align-middle">
                <thead><tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Vehículo</th><th>Trabajo</th><th>Repuestos</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= (int)$row['id_servicio'] ?></td>
                        <td><?= h($row['fecha_servicio']) ?></td>
                        <td><?= h($row['cliente']) ?></td>
                        <td><?= h($row['placa'] . ' · ' . $row['marca'] . ' ' . $row['modelo']) ?></td>
                        <td><?= h(excerpt($row['descripcion'])) ?></td>
                        <td><?= (int)$row['repuestos_usados'] ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="?r=servicios&action=view&id=<?= (int)$row['id_servicio'] ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows) table_empty(7); ?>
                </tbody>
            </table>
        </div>
        <?php
    });
}

function compras_page(): void
{
    require_auth();
    $action = $_GET['action'] ?? 'index';

    if ($action === 'recibir') {
        $id = get_int('id');
        $compra = query('SELECT * FROM compras WHERE id_compra = ?', [$id])->fetch();
        if (!$compra || $compra['estado'] !== 'pendiente') {
            flash('La compra no está pendiente.', 'danger');
            redirect('compras');
        }
        $details = query('SELECT * FROM detalle_compra WHERE id_compra = ?', [$id])->fetchAll();
        db()->beginTransaction();
        try {
            query("UPDATE compras SET estado = 'recibida' WHERE id_compra = ?", [$id]);
            foreach ($details as $detail) {
                query('UPDATE repuestos SET stock_actual = stock_actual + ? WHERE id_repuesto = ?', [$detail['cantidad'], $detail['id_repuesto']]);
                query('INSERT INTO movimientos_inventario (id_repuesto, id_usuario, tipo_movimiento, cantidad, motivo, referencia) VALUES (?,?,?,?,?,?)', [
                    $detail['id_repuesto'], current_user()['id_usuario'], 'entrada', $detail['cantidad'], 'Recepción de compra', 'Compra #' . $id
                ]);
            }
            db()->commit();
            flash('Compra recibida e inventario actualizado.');
        } catch (Throwable $e) {
            db()->rollBack();
            flash('No se pudo recibir la compra.', 'danger');
        }
        redirect('compras');
    }

    if ($action === 'form') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proveedor = (int)post('id_proveedor');
            $fecha = (string)post('fecha_compra');
            $repuestos = $_POST['id_repuesto'] ?? [];
            $cantidades = $_POST['cantidad'] ?? [];
            $precios = $_POST['precio_unitario'] ?? [];
            if ($proveedor <= 0 || $fecha === '') {
                flash('Proveedor y fecha son obligatorios.', 'danger');
                redirect('compras&action=form');
            }
            db()->beginTransaction();
            try {
                $total = 0.0;
                $validRows = [];
                foreach ($repuestos as $i => $idRepuesto) {
                    $cantidad = (int)($cantidades[$i] ?? 0);
                    $precio = (float)($precios[$i] ?? 0);
                    if ((int)$idRepuesto > 0 && $cantidad > 0 && $precio >= 0) {
                        $subtotal = $cantidad * $precio;
                        $total += $subtotal;
                        $validRows[] = [(int)$idRepuesto, $cantidad, $precio, $subtotal];
                    }
                }
                if (!$validRows) {
                    throw new RuntimeException('Compra sin detalle.');
                }
                query('INSERT INTO compras (id_proveedor, id_usuario, fecha_compra, total_estimado, observaciones) VALUES (?,?,?,?,?)', [
                    $proveedor, current_user()['id_usuario'], $fecha, $total, trim((string)post('observaciones'))
                ]);
                $idCompra = (int)db()->lastInsertId();
                foreach ($validRows as $row) {
                    query('INSERT INTO detalle_compra (id_compra, id_repuesto, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)', [$idCompra, ...$row]);
                }
                db()->commit();
                flash('Compra registrada.');
                redirect('compras');
            } catch (Throwable $e) {
                db()->rollBack();
                flash('La compra debe tener al menos un detalle válido.', 'danger');
            }
        }

        $proveedores = query('SELECT id_proveedor, nombre FROM proveedores WHERE estado = 1 ORDER BY nombre')->fetchAll();
        $repuestos = query('SELECT id_repuesto, codigo, nombre FROM repuestos WHERE estado = 1 ORDER BY nombre')->fetchAll();
        $lineCount = max(1, min(10, (int)($_GET['lines'] ?? 1)));
        layout('Nueva compra', function () use ($proveedores, $repuestos, $lineCount) {
            ?>
            <h1 class="h3 mb-3">Nueva compra</h1>
            <div class="content-card p-4">
                <form method="post">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5"><label class="form-label">Proveedor</label><select class="form-select" name="id_proveedor" required><?php foreach ($proveedores as $p): ?><option value="<?= (int)$p['id_proveedor'] ?>"><?= h($p['nombre']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Fecha</label><input class="form-control" type="date" name="fecha_compra" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Observaciones</label><input class="form-control" name="observaciones"></div>
                    </div>
                    <table class="table">
                        <thead><tr><th>Repuesto</th><th>Cantidad</th><th>Precio unitario</th><th></th></tr></thead>
                        <tbody data-compra-rows>
                            <?php for ($i = 0; $i < $lineCount; $i++): ?>
                                <tr data-compra-row><td><select class="form-select" name="id_repuesto[]"><?php foreach ($repuestos as $r): ?><option value="<?= (int)$r['id_repuesto'] ?>"><?= h($r['codigo'] . ' - ' . $r['nombre']) ?></option><?php endforeach; ?></select></td><td><input class="form-control" type="number" min="1" name="cantidad[]"></td><td><input class="form-control" type="number" step="0.01" min="0" name="precio_unitario[]"></td><td class="text-end"><?php if ($lineCount > 1): ?><a class="btn btn-outline-danger btn-sm" href="?r=compras&action=form&lines=<?= $lineCount - 1 ?>">Eliminar</a><?php endif; ?></td></tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    <template data-compra-template><tr data-compra-row><td><select class="form-select" name="id_repuesto[]"><?php foreach ($repuestos as $r): ?><option value="<?= (int)$r['id_repuesto'] ?>"><?= h($r['codigo'] . ' - ' . $r['nombre']) ?></option><?php endforeach; ?></select></td><td><input class="form-control" type="number" min="1" name="cantidad[]"></td><td><input class="form-control" type="number" step="0.01" min="0" name="precio_unitario[]"></td><td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" data-remove-compra-row>Eliminar</button></td></tr></template>
                    <button type="button" class="btn btn-outline-secondary" data-add-compra-row>Agregar línea</button>
                    <button class="btn btn-success">Guardar compra</button>
                    <a class="btn btn-outline-secondary" href="?r=compras">Cancelar</a>
                </form>
            </div>
            <?php
        });
        return;
    }

    $rows = query("SELECT c.*, p.nombre proveedor FROM compras c JOIN proveedores p ON p.id_proveedor = c.id_proveedor ORDER BY c.fecha_compra DESC, c.id_compra DESC")->fetchAll();
    layout('Compras', function () use ($rows) {
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Compras</h1><a class="btn btn-success" href="?r=compras&action=form">Nueva compra</a></div>
        <div class="content-card p-3 table-responsive">
            <table class="table align-middle"><thead><tr><th>#</th><th>Fecha</th><th>Proveedor</th><th>Estado</th><th>Total</th><th></th></tr></thead><tbody>
                <?php foreach ($rows as $row): ?>
                    <tr><td><?= (int)$row['id_compra'] ?></td><td><?= h($row['fecha_compra']) ?></td><td><?= h($row['proveedor']) ?></td><td><?= h($row['estado']) ?></td><td>$<?= number_format((float)$row['total_estimado'], 2) ?></td><td class="text-end"><?php if ($row['estado'] === 'pendiente'): ?><a class="btn btn-sm btn-success" data-confirm="¿Recibir compra y actualizar inventario?" href="?r=compras&action=recibir&id=<?= (int)$row['id_compra'] ?>">Recibir</a><?php endif; ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$rows) table_empty(6); ?>
            </tbody></table>
        </div>
        <?php
    });
}

function reportes_page(): void
{
    require_auth();
    $inventario = query('SELECT * FROM repuestos WHERE estado = 1 ORDER BY nombre')->fetchAll();
    $bajoStock = query('SELECT * FROM repuestos WHERE estado = 1 AND stock_actual <= stock_minimo ORDER BY nombre')->fetchAll();
    $compras = query("SELECT p.nombre proveedor, COUNT(*) compras, SUM(c.total_estimado) total FROM compras c JOIN proveedores p ON p.id_proveedor = c.id_proveedor GROUP BY p.id_proveedor, p.nombre ORDER BY total DESC")->fetchAll();
    $clientes = query("SELECT c.nombre, c.telefono, COUNT(v.id_vehiculo) vehiculos FROM clientes c LEFT JOIN vehiculos v ON v.id_cliente = c.id_cliente AND v.estado = 1 WHERE c.estado = 1 GROUP BY c.id_cliente, c.nombre, c.telefono ORDER BY c.nombre")->fetchAll();
    $servicios = query(
        "SELECT s.id_servicio, s.fecha_servicio, c.nombre cliente, v.placa,
                CONCAT(v.marca, ' ', v.modelo) vehiculo, s.descripcion,
                COUNT(ds.id_detalle_servicio) repuestos
         FROM servicios s
         JOIN vehiculos v ON v.id_vehiculo = s.id_vehiculo
         JOIN clientes c ON c.id_cliente = v.id_cliente
         LEFT JOIN detalle_servicio_repuesto ds ON ds.id_servicio = s.id_servicio
         GROUP BY s.id_servicio, s.fecha_servicio, c.nombre, v.placa, v.marca, v.modelo, s.descripcion
         ORDER BY s.fecha_servicio DESC, s.id_servicio DESC"
    )->fetchAll();

    if (($_GET['action'] ?? '') === 'export') {
        match ($_GET['type'] ?? '') {
            'inventario' => csv_response('inventario.csv', ['Código','Nombre','Stock','Mínimo','Ubicación'], $inventario, fn($r) => [$r['codigo'], $r['nombre'], $r['stock_actual'], $r['stock_minimo'], $r['ubicacion']]),
            'stock_bajo' => csv_response('stock_bajo.csv', ['Código','Nombre','Stock','Mínimo'], $bajoStock, fn($r) => [$r['codigo'], $r['nombre'], $r['stock_actual'], $r['stock_minimo']]),
            'compras' => csv_response('compras_por_proveedor.csv', ['Proveedor','Compras','Total'], $compras, fn($r) => [$r['proveedor'], $r['compras'], number_format((float)$r['total'], 2, '.', '')]),
            'clientes' => csv_response('clientes_vehiculos.csv', ['Cliente','Teléfono','Vehículos'], $clientes, fn($r) => [$r['nombre'], $r['telefono'], $r['vehiculos']]),
            'servicios' => csv_response('servicios.csv', ['#','Fecha','Cliente','Placa','Vehículo','Trabajo','Repuestos'], $servicios, fn($r) => [$r['id_servicio'], $r['fecha_servicio'], $r['cliente'], $r['placa'], $r['vehiculo'], $r['descripcion'], $r['repuestos']]),
            default => redirect('reportes'),
        };
    }

    layout('Reportes', function () use ($inventario, $bajoStock, $compras, $clientes, $servicios) {
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Reportes</h1>
            <button class="btn btn-outline-secondary no-print" onclick="window.print()">Imprimir</button>
        </div>
        <?php report_table('Inventario actual', ['Código','Nombre','Stock','Mínimo','Ubicación'], $inventario, fn($r) => [$r['codigo'], $r['nombre'], $r['stock_actual'], $r['stock_minimo'], $r['ubicacion']], 'inventario'); ?>
        <?php report_table('Stock bajo', ['Código','Nombre','Stock','Mínimo'], $bajoStock, fn($r) => [$r['codigo'], $r['nombre'], $r['stock_actual'], $r['stock_minimo']], 'stock_bajo'); ?>
        <?php report_table('Compras por proveedor', ['Proveedor','Compras','Total'], $compras, fn($r) => [$r['proveedor'], $r['compras'], '$' . number_format((float)$r['total'], 2)], 'compras'); ?>
        <?php report_table('Servicios realizados', ['#','Fecha','Cliente','Placa','Vehículo','Trabajo','Repuestos'], $servicios, fn($r) => [$r['id_servicio'], $r['fecha_servicio'], $r['cliente'], $r['placa'], $r['vehiculo'], excerpt($r['descripcion'], 48), $r['repuestos']], 'servicios'); ?>
        <?php report_table('Clientes y vehículos', ['Cliente','Teléfono','Vehículos'], $clientes, fn($r) => [$r['nombre'], $r['telefono'], $r['vehiculos']], 'clientes'); ?>
        <?php
    });
}

function report_table(string $title, array $headers, array $rows, callable $map, ?string $exportType = null): void
{
    ?>
    <div class="content-card p-3 table-responsive mb-4">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <h2 class="h5 mb-0"><?= h($title) ?></h2>
            <?php if ($exportType): ?>
                <a class="btn btn-sm btn-outline-secondary no-print" href="?r=reportes&action=export&type=<?= h($exportType) ?>">CSV</a>
            <?php endif; ?>
        </div>
        <table class="table table-sm align-middle">
            <thead><tr><?php foreach ($headers as $header): ?><th><?= h($header) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?><tr><?php foreach ($map($row) as $cell): ?><td><?= h((string)$cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
            <?php if (!$rows) table_empty(count($headers)); ?>
            </tbody>
        </table>
    </div>
    <?php
}

try {
    match (route()) {
        'login' => login_page(),
        'logout' => (session_destroy() || true) ? redirect('login') : null,
        'dashboard' => dashboard_page(),
        'repuestos' => repuestos_page(),
        'repuestos_form' => repuestos_form_page(),
        'movimientos' => movimientos_page(),
        'proveedores' => generic_crud('proveedores'),
        'clientes' => generic_crud('clientes'),
        'vehiculos' => vehiculos_page(),
        'servicios' => servicios_page(),
        'compras' => compras_page(),
        'reportes' => reportes_page(),
        default => redirect('dashboard'),
    };
} catch (PDOException $e) {
    http_response_code(500);
    echo '<h1>Error de base de datos</h1>';
    echo '<p>Revise config/database.php y confirme que MySQL esté activo y que database/schema.sql haya sido importado.</p>';
}
