<?php
require_once 'modules/empleado/model.php';


class Salario extends StandardObject {
	
	function __construct(Empleado $empleado=NULL) {
		$this->salario_id = 0;
		$this->periodo = '';
		$this->fecha = '';
		$this->hora = '';
		$this->monto = 0.00;
		$this->usuario_id = 0;
		$this->empleado = $empleado;
	}
}
?>