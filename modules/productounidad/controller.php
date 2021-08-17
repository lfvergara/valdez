<?php
require_once "modules/productounidad/model.php";
require_once "modules/productounidad/view.php";


class ProductoUnidadController {

	function __construct() {
		$this->model = new ProductoUnidad();
		$this->view = new ProductoUnidadView();
	}

	function panel() {
    	SessionHandler()->check_session();
		
		$productounidad_collection = Collector()->get('ProductoUnidad');
		$this->view->panel($productounidad_collection);
	}

	function guardar() {
		SessionHandler()->check_session();
		
		foreach ($_POST as $clave=>$valor) $this->model->$clave = $valor;
        $this->model->save();
		header("Location: " . URL_APP . "/productounidad/panel");
	}

	function editar($arg) {
		SessionHandler()->check_session();
		
		$this->model->productounidad_id = $arg;
		$this->model->get();
		$productounidad_collection = Collector()->get('ProductoUnidad');
		$this->view->editar($productounidad_collection, $this->model);
	}
}
?>