<?php
require_once "modules/cliente/model.php";
require_once "modules/vendedor/model.php";
require_once "modules/tipofactura/model.php";
require_once "modules/condicioniva/model.php";
require_once "modules/condicionpago/model.php";
require_once "modules/egresocomision/model.php";
require_once "modules/egresoentrega/model.php";


class Egreso extends StandardObject {
	
	function __construct(Cliente $cliente=NULL, Vendedor $vendedor=NULL, CondicionIVA $condicioniva=NULL, 
						 CondicionPago $condicionpago=NULL, TipoFactura $tipofactura=NULL, EgresoComision $egresocomision=NULL,
						 EgresoEntrega $egresoentrega=NULL) {
		$this->egreso_id = 0;
		$this->punto_venta = 0;
		$this->numero_factura = 0;
		$this->fecha = '';
		$this->hora = '';
		$this->descuento = 0.00;
		$this->subtotal = 0.00;
		$this->importe_total = 0.00;
		$this->emitido = 0;
		$this->dias_alerta_comision = 0;
		$this->dias_vencimiento = 0;
		$this->cliente = $cliente;
        $this->vendedor = $vendedor;
        $this->tipofactura = $tipofactura;
        $this->condicioniva = $condicioniva;
        $this->condicionpago = $condicionpago;
        $this->egresocomision = $egresocomision;
        $this->egresoentrega = $egresoentrega;
	}

	function prueba() {
		$sql = "insert into egreso(punto_venta, numero_factura, fecha, hora, descuento, subtotal, importe_total, emitido, dias_alerta_comision, dias_vencimiento, cliente, vendedor, tipofactura, condicioniva, condicionpago, egresocomision, egresoentrega) VALUES (1,2,'2021-08-25', '08:08:08', 0, 100, 400, 0, 10, 10, 1, 1, 3, 1, 1, 1, 1)";
		execute_query($sql);
	}
}
?>