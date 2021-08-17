<?php
require_once "modules/productomarca/model.php";
require_once "modules/productocategoria/model.php";
require_once "modules/productounidad/model.php";
require_once "modules/proveedor/model.php";


class Producto extends StandardObject {
	
	function __construct(ProductoMarca $productomarca=NULL, ProductoCategoria $productocategoria=NULL, 
                         ProductoUnidad $productounidad=NULL) {
		$this->producto_id = 0;
		$this->codigo = 0;
		$this->denominacion = '';
		$this->peso = 0.00;
		$this->costo = 0.00;
		$this->descuento = 0.00;
		$this->flete = 0.00;
		$this->porcentaje_ganancia = 0.00;
		$this->iva = 0.00;
		$this->exento = 0;
		$this->no_gravado = 0;
		$this->stock_minimo = 0;
		$this->stock_ideal = 0;
		$this->dias_reintegro = 0;
		$this->oculto = 0;
        $this->barcode = '';
        $this->detalle = '';
        $this->productomarca = $productomarca;
        $this->productocategoria = $productocategoria;
        $this->productounidad = $productounidad;
	}
}
?>