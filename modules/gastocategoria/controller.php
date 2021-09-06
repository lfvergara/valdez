<?php
require_once "modules/gastocategoria/model.php";
require_once "modules/gastocategoria/view.php";
require_once "modules/gastosubcategoria/model.php";


class GastoCategoriaController {

	function __construct() {
		$this->model = new GastoCategoria();
		$this->view = new GastoCategoriaView();
	}

	function panel() {
    	SessionHandler()->check_session();

		$gastocategoria_collection = Collector()->get('GastoCategoria');
		$gastosubcategoria_collection = Collector()->get('GastoSubCategoria');
		foreach ($gastocategoria_collection as $clave=>$valor) {
			if ($valor->oculto == 1) unset($gastocategoria_collection[$clave]);
		}

		$this->view->panel($gastocategoria_collection,$gastosubcategoria_collection);
	}

	function guardar() {
		SessionHandler()->check_session();
		foreach ($_POST as $clave=>$valor) $this->model->$clave = $valor;
		$this->model->oculto = 0;
		print_r($this->model);exit;
        $this->model->save();
		header("Location: " . URL_APP . "/gastocategoria/panel");
	}

	function editar($arg) {
		SessionHandler()->check_session();

		$this->model->gastocategoria_id = $arg;
		$this->model->get();
		$gastocategoria_collection = Collector()->get('GastoCategoria');
		$gastosubcategoria_collection = Collector()->get('GastoSubCategoria');
		foreach ($gastocategoria_collection as $clave=>$valor) {
			if ($valor->oculto == 1) unset($gastocategoria_collection[$clave]);
		}

		$this->view->editar($gastocategoria_collection, $this->model,$gastosubcategoria_collection);
	}
}
?>
