<?php
// Archivo: clases.php

// Interfaz Inventariable con el método requerido
interface Inventariable {
    public function obtenerInformacionInventario(): string;
}

// Clase abstracta Producto que implementa la interfaz
abstract class Producto implements Inventariable {
    public $id;
    public $nombre;
    public $descripcion;
    public $estado;
    public $stock;
    public $fechaIngreso;
    public $categoria;

    public function __construct($datos) {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }
}

// Clase ProductoElectronico - hereda de Producto
class ProductoElectronico extends Producto {
    public $garantiaMeses;

    public function obtenerInformacionInventario(): string {
        return "Garantía: " . $this->garantiaMeses . " meses";
    }
}

// Clase ProductoAlimento - hereda de Producto
class ProductoAlimento extends Producto {
    public $fechaVencimiento;

    public function obtenerInformacionInventario(): string {
        return "Fecha de Vencimiento: " . $this->fechaVencimiento;
    }
}

// Clase ProductoRopa - hereda de Producto
class ProductoRopa extends Producto {
    public $talla;

    public function obtenerInformacionInventario(): string {
        return "Talla: " . $this->talla;
    }
}

// Clase GestorInventario
class GestorInventario {
    private $items = [];
    private $rutaArchivo = 'productos.json';

    // Obtiene todos los productos cargados
    public function obtenerTodos() {
        if (empty($this->items)) {
            $this->cargarDesdeArchivo();
        }
        return $this->items;
    }

    // Carga productos desde el archivo JSON y crea instancias según categoría
    private function cargarDesdeArchivo() {
        if (!file_exists($this->rutaArchivo)) {
            return;
        }
        
        $jsonContenido = file_get_contents($this->rutaArchivo);
        $arrayDatos = json_decode($jsonContenido, true);
        
        if ($arrayDatos === null) {
            return;
        }
        
        // Crear instancias de las clases hijas según la categoría
        foreach ($arrayDatos as $datos) {
            $producto = null;
            
            switch ($datos['categoria']) {
                case 'electronico':
                    $producto = new ProductoElectronico($datos);
                    break;
                case 'alimento':
                    $producto = new ProductoAlimento($datos);
                    break;
                case 'ropa':
                    $producto = new ProductoRopa($datos);
                    break;
                default:
                    // Si la categoría no es reconocida, se omite
                    continue 2;
            }
            
            $this->items[] = $producto;
        }
    }

    // Guarda los productos en el archivo JSON
    private function persistirEnArchivo() {
        $arrayParaGuardar = array_map(function($item) {
            return get_object_vars($item);
        }, $this->items);
        
        file_put_contents(
            $this->rutaArchivo, 
            json_encode($arrayParaGuardar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    // Obtiene el ID máximo actual
    public function obtenerMaximoId() {
        if (empty($this->items)) {
            return 0;
        }
        
        $ids = array_map(function($item) {
            return $item->id;
        }, $this->items);
        
        return max($ids);
    }

    // Agrega un nuevo producto al inventario
    public function agregar($nuevoProducto) {
        // Asignar nuevo ID
        $nuevoProducto->id = $this->obtenerMaximoId() + 1;
        
        // Agregar al arreglo
        $this->items[] = $nuevoProducto;
        
        // Guardar en archivo
        $this->persistirEnArchivo();
    }

    // Elimina un producto por su ID
    public function eliminar($idProducto) {
        foreach ($this->items as $indice => $item) {
            if ($item->id == $idProducto) {
                unset($this->items[$indice]);
                // Reindexar el arreglo
                $this->items = array_values($this->items);
                $this->persistirEnArchivo();
                return true;
            }
        }
        return false;
    }

    // Actualiza un producto existente
    public function actualizar($productoActualizado) {
        foreach ($this->items as $indice => $item) {
            if ($item->id == $productoActualizado->id) {
                $this->items[$indice] = $productoActualizado;
                $this->persistirEnArchivo();
                return true;
            }
        }
        return false;
    }

    // Cambia el estado de un producto
    public function cambiarEstado($idProducto, $estadoNuevo) {
        foreach ($this->items as $item) {
            if ($item->id == $idProducto) {
                $item->estado = $estadoNuevo;
                $this->persistirEnArchivo();
                return true;
            }
        }
        return false;
    }

    // Filtra productos por estado
    public function filtrarPorEstado($estadoBuscado) {
        // Si el estado está vacío, retornar todos
        if (empty($estadoBuscado)) {
            return $this->items;
        }
        
        // Filtrar por estado usando array_filter
        return array_filter($this->items, function($item) use ($estadoBuscado) {
            return $item->estado === $estadoBuscado;
        });
    }

    // Obtiene un producto por su ID
    public function obtenerPorId($idBuscado) {
        foreach ($this->items as $item) {
            if ($item->id == $idBuscado) {
                return $item;
            }
        }
        return null;
    }
}