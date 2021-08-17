<?php
require_once "modules/estadopedido/model.php";


class PedidoVendedor extends StandardObject {
	
	function __construct(EstadoPedido $estadopedido=NULL) {
		$this->pedidovendedor_id = 0;
		$this->fecha = '';
		$this->hora = '';
		$this->subtotal = 0.00;
		$this->importe_total = 0.00;
		$this->vendedor_id = 0;
		$this->cliente_id = 0;
		$this->estadopedido = $estadopedido;
	}
}
?>