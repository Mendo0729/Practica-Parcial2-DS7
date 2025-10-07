<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'clases.php';

$gestor = new GestorInventario();
$notificacion = '';
$itemParaEditar = null;

// Arreglos para mostrar valores legibles
$estadosLegibles = [
    'disponible' => 'Disponible',
    'agotado' => 'Agotado',
    'por_recibir' => 'Por Recibir'
];

$categoriasLegibles = [
    'electronico' => 'Electrónico',
    'alimento' => 'Alimento',
    'ropa' => 'Ropa'
];

// Capturar parámetros de la URL
$operacion = $_GET['operacion'] ?? 'listar';
$campoOrden = $_GET['ordenar'] ?? 'id';
$tipoOrden = $_GET['tipo'] ?? 'asc';
$filtroEstado = $_GET['estado'] ?? '';

// Procesar las diferentes operaciones
if ($operacion === 'crear' && !empty($_GET['nombre'])) {
    // Capturar datos del formulario
    $categoria = $_GET['categoria'];
    
    // Crear arreglo con datos básicos
    $datos = [
        'nombre' => $_GET['nombre'],
        'descripcion' => $_GET['descripcion'],
        'estado' => $_GET['estado'],
        'stock' => intval($_GET['stock']),
        'fechaIngreso' => date('Y-m-d'),
        'categoria' => $categoria
    ];
    
    // Agregar campos específicos según categoría
    if ($categoria === 'electronico' && isset($_GET['garantiaMeses'])) {
        $datos['garantiaMeses'] = intval($_GET['garantiaMeses']);
        $producto = new ProductoElectronico($datos);
    } elseif ($categoria === 'alimento' && isset($_GET['fechaVencimiento'])) {
        $datos['fechaVencimiento'] = $_GET['fechaVencimiento'];
        $producto = new ProductoAlimento($datos);
    } elseif ($categoria === 'ropa' && isset($_GET['talla'])) {
        $datos['talla'] = $_GET['talla'];
        $producto = new ProductoRopa($datos);
    } else {
        $notificacion = "Error: Categoría no válida o falta información específica.";
    }
    
    if (isset($producto)) {
        $gestor->agregar($producto);
        $notificacion = "Producto agregado correctamente.";
    }
    
} elseif ($operacion === 'modificar' && !empty($_GET['id'])) {
    // Capturar datos del formulario
    $categoria = $_GET['categoria'];
    
    // Crear arreglo con datos actualizados
    $datos = [
        'id' => intval($_GET['id']),
        'nombre' => $_GET['nombre'],
        'descripcion' => $_GET['descripcion'],
        'estado' => $_GET['estado'],
        'stock' => intval($_GET['stock']),
        'fechaIngreso' => $_GET['fechaIngreso'] ?? date('Y-m-d'),
        'categoria' => $categoria
    ];
    
    // Agregar campos específicos según categoría
    if ($categoria === 'electronico' && isset($_GET['garantiaMeses'])) {
        $datos['garantiaMeses'] = intval($_GET['garantiaMeses']);
        $producto = new ProductoElectronico($datos);
    } elseif ($categoria === 'alimento' && isset($_GET['fechaVencimiento'])) {
        $datos['fechaVencimiento'] = $_GET['fechaVencimiento'];
        $producto = new ProductoAlimento($datos);
    } elseif ($categoria === 'ropa' && isset($_GET['talla'])) {
        $datos['talla'] = $_GET['talla'];
        $producto = new ProductoRopa($datos);
    }
    
    if (isset($producto)) {
        if ($gestor->actualizar($producto)) {
            $notificacion = "Producto modificado correctamente.";
        } else {
            $notificacion = "Error: No se pudo modificar el producto.";
        }
    }
    
} elseif ($operacion === 'eliminar' && !empty($_GET['id'])) {
    $idEliminar = intval($_GET['id']);
    if ($gestor->eliminar($idEliminar)) {
        $notificacion = "Producto eliminado correctamente.";
    } else {
        $notificacion = "Error: No se pudo eliminar el producto.";
    }
    
} elseif ($operacion === 'cambiar_estado' && !empty($_GET['id']) && !empty($_GET['nuevo_estado'])) {
    $idCambiar = intval($_GET['id']);
    $nuevoEstado = $_GET['nuevo_estado'];
    if ($gestor->cambiarEstado($idCambiar, $nuevoEstado)) {
        $notificacion = "Estado actualizado correctamente.";
    } else {
        $notificacion = "Error: No se pudo cambiar el estado.";
    }
    
} elseif ($operacion === 'editar' && !empty($_GET['id'])) {
    $idEditar = intval($_GET['id']);
    $itemParaEditar = $gestor->obtenerPorId($idEditar);
}

// Obtener productos (con o sin filtro)
if ($operacion === 'filtrar') {
    $listaProductos = $gestor->filtrarPorEstado($filtroEstado);
} else {
    $listaProductos = $gestor->obtenerTodos();
}

// Ordenar productos
if ($operacion === 'ordenar') {
    usort($listaProductos, function($a, $b) use ($campoOrden, $tipoOrden) {
        $valorA = $a->$campoOrden ?? '';
        $valorB = $b->$campoOrden ?? '';
        
        // Comparación
        if ($valorA == $valorB) {
            return 0;
        }
        
        if ($tipoOrden === 'asc') {
            return ($valorA < $valorB) ? -1 : 1;
        } else {
            return ($valorA > $valorB) ? -1 : 1;
        }
    });
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4"><i class="fas fa-warehouse"></i> Sistema de Gestión de Inventario</h1>
        
        <?php if (!empty($notificacion)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($notificacion); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario de Producto -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <?php echo $itemParaEditar ? '<i class="fas fa-edit"></i> Editar Producto' : '<i class="fas fa-plus"></i> Nuevo Producto'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="index.php">
                    <input type="hidden" name="operacion" value="<?php echo $itemParaEditar ? 'modificar' : 'crear'; ?>">
                    <?php if ($itemParaEditar): ?>
                        <input type="hidden" name="id" value="<?php echo $itemParaEditar->id; ?>">
                        <input type="hidden" name="fechaIngreso" value="<?php echo $itemParaEditar->fechaIngreso; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Producto</label>
                            <input type="text" class="form-control" name="nombre" 
                                   value="<?php echo $itemParaEditar->nombre ?? ''; ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Descripción</label>
                            <input type="text" class="form-control" name="descripcion" 
                                   value="<?php echo $itemParaEditar->descripcion ?? ''; ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Stock Disponible</label>
                            <input type="number" class="form-control" name="stock" min="0"
                                   value="<?php echo $itemParaEditar->stock ?? ''; ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="categoria" id="selectorCategoria" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($categoriasLegibles as $valor => $texto): ?>
                                    <option value="<?php echo $valor; ?>" 
                                        <?php echo ($itemParaEditar && $itemParaEditar->categoria == $valor) ? 'selected' : ''; ?>>
                                        <?php echo $texto; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado" required>
                                <?php foreach ($estadosLegibles as $valor => $texto): ?>
                                    <option value="<?php echo $valor; ?>" 
                                        <?php echo ($itemParaEditar && $itemParaEditar->estado == $valor) ? 'selected' : ''; ?>>
                                        <?php echo $texto; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Campos específicos por categoría -->
                        <div class="col-md-4" id="contenedorCampoEspecifico" style="display:none;">
                            <!-- Campo para Electrónico -->
                            <div id="campoElectronico" style="display:none;">
                                <label class="form-label">Garantía (meses)</label>
                                <input type="number" class="form-control" name="garantiaMeses" min="0" 
                                       value="<?php echo $itemParaEditar->garantiaMeses ?? ''; ?>">
                            </div>
                            
                            <!-- Campo para Alimento -->
                            <div id="campoAlimento" style="display:none;">
                                <label class="form-label">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" name="fechaVencimiento"
                                       value="<?php echo $itemParaEditar->fechaVencimiento ?? ''; ?>">
                            </div>
                            
                            <!-- Campo para Ropa -->
                            <div id="campoRopa" style="display:none;">
                                <label class="form-label">Talla</label>
                                <select class="form-select" name="talla">
                                    <option value="">-- Seleccionar --</option>
                                    <?php
                                    $tallas = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                                    foreach ($tallas as $t) {
                                        $sel = ($itemParaEditar && isset($itemParaEditar->talla) && $itemParaEditar->talla == $t) ? 'selected' : '';
                                        echo "<option value='$t' $sel>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $itemParaEditar ? 'Actualizar' : 'Guardar'; ?>
                            </button>
                            <?php if ($itemParaEditar): ?>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="index.php" class="row g-3">
                    <input type="hidden" name="operacion" value="filtrar">
                    <div class="col-auto">
                        <label class="form-label">Filtrar por Estado:</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($estadosLegibles as $valor => $texto): ?>
                                <option value="<?php echo $valor; ?>" <?php echo $filtroEstado == $valor ? 'selected' : ''; ?>>
                                    <?php echo $texto; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-filter"></i> Aplicar Filtro
                        </button>
                        <a href="index.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-redo"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Productos -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Productos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <a href="?operacion=ordenar&ordenar=id&tipo=<?php echo ($campoOrden == 'id' && $tipoOrden == 'asc') ? 'desc' : 'asc'; ?>" class="text-white text-decoration-none">
                                        ID <?php echo ($campoOrden == 'id') ? ($tipoOrden == 'asc' ? '▲' : '▼') : ''; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?operacion=ordenar&ordenar=nombre&tipo=<?php echo ($campoOrden == 'nombre' && $tipoOrden == 'asc') ? 'desc' : 'asc'; ?>" class="text-white text-decoration-none">
                                        Nombre <?php echo ($campoOrden == 'nombre') ? ($tipoOrden == 'asc' ? '▲' : '▼') : ''; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?operacion=ordenar&ordenar=descripcion&tipo=<?php echo ($campoOrden == 'descripcion' && $tipoOrden == 'asc') ? 'desc' : 'asc'; ?>" class="text-white text-decoration-none">
                                        Descripción <?php echo ($campoOrden == 'descripcion') ? ($tipoOrden == 'asc' ? '▲' : '▼') : ''; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?operacion=ordenar&ordenar=estado&tipo=<?php echo ($campoOrden == 'estado' && $tipoOrden == 'asc') ? 'desc' : 'asc'; ?>" class="text-white text-decoration-none">
                                        Estado <?php echo ($campoOrden == 'estado') ? ($tipoOrden == 'asc' ? '▲' : '▼') : ''; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?operacion=ordenar&ordenar=stock&tipo=<?php echo ($campoOrden == 'stock' && $tipoOrden == 'asc') ? 'desc' : 'asc'; ?>" class="text-white text-decoration-none">
                                        Stock <?php echo ($campoOrden == 'stock') ? ($tipoOrden == 'asc' ? '▲' : '▼') : ''; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?operacion=ordenar&ordenar=categoria&tipo=<?php echo ($campoOrden == 'categoria' && $tipoOrden == 'asc') ? 'desc' : 'asc'; ?>" class="text-white text-decoration-none">
                                        Categoría <?php echo ($campoOrden == 'categoria') ? ($tipoOrden == 'asc' ? '▲' : '▼') : ''; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?operacion=ordenar&ordenar=fechaIngreso&tipo=<?php echo ($campoOrden == 'fechaIngreso' && $tipoOrden == 'asc') ? 'desc' : 'asc'; ?>" class="text-white text-decoration-none">
                                        Fecha Ingreso <?php echo ($campoOrden == 'fechaIngreso') ? ($tipoOrden == 'asc' ? '▲' : '▼') : ''; ?>
                                    </a>
                                </th>
                                <th>Información de Inventario</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listaProductos)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-2"></i>
                                        <p>No hay productos registrados</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listaProductos as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item->id); ?></td>
                                        <td><?php echo htmlspecialchars($item->nombre); ?></td>
                                        <td><?php echo htmlspecialchars($item->descripcion); ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = match($item->estado) {
                                                'disponible' => 'success',
                                                'agotado' => 'danger',
                                                'por_recibir' => 'warning',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($estadosLegibles[$item->estado] ?? $item->estado); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($item->stock); ?></td>
                                        <td><?php echo htmlspecialchars($categoriasLegibles[$item->categoria] ?? $item->categoria); ?></td>
                                        <td><?php echo htmlspecialchars($item->fechaIngreso); ?></td>
                                        <td><?php echo htmlspecialchars($item->obtenerInformacionInventario()); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="?operacion=editar&id=<?php echo $item->id; ?>" 
                                                   class="btn btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?operacion=eliminar&id=<?php echo $item->id; ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('¿Eliminar este producto?');"
                                                   title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                            <div class="btn-group btn-group-sm ms-1" role="group">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" 
                                                        data-bs-toggle="dropdown" title="Cambiar Estado">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php foreach ($estadosLegibles as $valor => $texto): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="?operacion=cambiar_estado&id=<?php echo $item->id; ?>&nuevo_estado=<?php echo $valor; ?>">
                                                                <i class="fas fa-circle text-<?php echo $badgeClass; ?>"></i> <?php echo $texto; ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Manejo de campos específicos por categoría
    const selector = document.getElementById('selectorCategoria');
    const contenedor = document.getElementById('contenedorCampoEspecifico');
    const campoElec = document.getElementById('campoElectronico');
    const campoAlim = document.getElementById('campoAlimento');
    const campoRop = document.getElementById('campoRopa');
    
    function actualizarCamposEspecificos() {
        contenedor.style.display = 'none';
        campoElec.style.display = 'none';
        campoAlim.style.display = 'none';
        campoRop.style.display = 'none';
        
        const valor = selector.value;
        
        if (valor === 'electronico') {
            contenedor.style.display = 'block';
            campoElec.style.display = 'block';
        } else if (valor === 'alimento') {
            contenedor.style.display = 'block';
            campoAlim.style.display = 'block';
        } else if (valor === 'ropa') {
            contenedor.style.display = 'block';
            campoRop.style.display = 'block';
        }
    }
    
    selector.addEventListener('change', actualizarCamposEspecificos);
    
    // Ejecutar al cargar para el modo edición
    window.addEventListener('load', actualizarCamposEspecificos);
    </script>
</body>
</html>