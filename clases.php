<?php
// Archivo: clases.php

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

    private function persistirEnArchivo() {
        $arrayParaGuardar = array_map(function($item) {
            return get_object_vars($item);
        }, $this->items);
        
        file_put_contents(
            $this->rutaArchivo, 
            json_encode($arrayParaGuardar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function obtenerMaximoId() {
        if (empty($this->items)) {
            return 0;
        }
        
        $ids = array_map(function($item) {
            return $item->id;
        }, $this->items);
        
        return max($ids);
    }

    // Parte #6
    // agregar($nuevoProducto) - Debe asignar un nuevo ID usando obtenerMaximoId() y guardar
    public function agregar($nuevoProducto){
        $nuevoId = $this->obtenerMaximoId() + 1;
        $nuevoProducto['id'] = $nuevoId;
        $this->productos[] = $nuevoProducto;
        return $nuevoId; 
    }

    //eliminar($idProducto) - Debe retornar true si se eliminó, false si no se encontró
    public function  eliminar($idProducto){
        foreach ($this->productos as $index => $producto) {
            if ($producto['id'] == $idProducto) {
                unset($this->productos[$index]);
                $this->productos = array_values($this->productos);
                return true;
            }
        }
        return false;
    }

    //actualizar($productoActualizado) - Debe retornar true si se actualizó, false si no se encontró
    public function actualizar($productoActualizado){
        foreach ($this->productos as $index => $producto) {
            if ($producto['id'] == $productoActualizado['id']) {
                $this->productos[$index] = $productoActualizado;
                return true;
            }
        }
        return false;
    }

    //cambiarEstado($idProducto, $estadoNuevo) - Debe retornar true si se cambió, false si no se encontró
    public function cambiarEstado($idProducto, $estadoNuevo){
         foreach ($this->productos as &$producto) {
            if ($producto['id'] == $idProducto) {
                $producto['estado'] = $estadoNuevo;
                return true;
            }
        }
        return false;
    }

    //filtrarPorEstado($estadoBuscado) - Debe retornar un arreglo con los productos filtrados (si $estadoBuscado está vacío, retornar todos)
    public function filtrarPorEstado($estadoBuscado) {
        if (empty($estadoBuscado)) {
            return $this->productos;
        }
        return array_filter($this->productos, function($producto) use ($estadoBuscado) {
            return strtolower($producto['estado']) == strtolower($estadoBuscado);
        });
    }

    //obtenerPorId($idBuscado) - Debe retornar el producto encontrado o null si no existe
    public function obtenerPorId($idBuscado){
        foreach ($this->productos as $producto) {
            if ($producto['id'] == $idBuscado) {
                return $producto;
            }
        }
        return null;
    }



}