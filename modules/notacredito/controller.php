<?php
require_once "modules/notacredito/model.php";
require_once "modules/notacredito/view.php";
require_once "modules/notacreditodetalle/model.php";
require_once "modules/egreso/model.php";
require_once "modules/tipofactura/model.php";
require_once "tools/facturaAFIPTool.php";


class NotaCreditoController {

	function __construct() {
		$this->model = new NotaCredito();
		$this->view = new NotaCreditoView();
	}

	function listar() {
    	SessionHandler()->check_session();
		$select = "nc.fecha AS FECHA, CONCAT(tifa.nomenclatura, ' ', LPAD(nc.punto_venta, 4, 0), '-', LPAD(nc.numero_factura, 8, 0)) AS NOTCRE,
    			   CASE WHEN nc.emitido_afip = 0 THEN CONCAT((SELECT tf.nomenclatura FROM tipofactura tf WHERE e.tipofactura = tf.tipofactura_id), ' ', LPAD(e.punto_venta, 4, 0), '-', LPAD(e.numero_factura, 8, 0)) 	
    			   ELSE CONCAT((SELECT tf.nomenclatura FROM tipofactura tf WHERE eafip.tipofactura = tf.tipofactura_id), ' ', LPAD(eafip.punto_venta, 4, 0), '-', LPAD(eafip.numero_factura, 8, 0)) END AS REFERENCIA,
    			   cl.razon_social AS CLIENTE, CONCAT(v.apellido, ' ', v.nombre) AS VENDEDOR, nc.importe_total AS IMPORTETOTAL, nc.notacredito_id AS NOTACREDITO_ID";
		$from = "notacredito nc INNER JOIN egreso e ON nc.egreso_id = e.egreso_id INNER JOIN tipofactura tifa ON nc.tipofactura = tifa.tipofactura_id INNER JOIN
				 cliente cl ON e.cliente = cl.cliente_id INNER JOIN vendedor v ON e.vendedor = v.vendedor_id LEFT JOIN 
				 egresoafip eafip ON e.egreso_id = eafip.egreso_id";
		$notacredito_collection = CollectorCondition()->get('NotaCredito', null, 4, $from, $select);
		$this->view->listar($notacredito_collection);
	}

	function buscar() {
    	SessionHandler()->check_session();
		$select = "nc.fecha AS FECHA, CONCAT(tifa.nomenclatura, ' ', LPAD(nc.punto_venta, 4, 0), '-', LPAD(nc.numero_factura, 8, 0)) AS NOTCRE,
    			   CASE WHEN nc.emitido_afip = 0 THEN CONCAT((SELECT tf.nomenclatura FROM tipofactura tf WHERE e.tipofactura = tf.tipofactura_id), ' ', LPAD(e.punto_venta, 4, 0), '-', LPAD(e.numero_factura, 8, 0)) 	
    			   ELSE CONCAT((SELECT tf.nomenclatura FROM tipofactura tf WHERE eafip.tipofactura = tf.tipofactura_id), ' ', LPAD(eafip.punto_venta, 4, 0), '-', LPAD(eafip.numero_factura, 8, 0)) END AS REFERENCIA,
    			   cl.razon_social AS CLIENTE, CONCAT(v.apellido, ' ', v.nombre) AS VENDEDOR, nc.importe_total AS IMPORTETOTAL, nc.notacredito_id AS NOTACREDITO_ID";
		$from = "notacredito nc INNER JOIN egreso e ON nc.egreso_id = e.egreso_id INNER JOIN tipofactura tifa ON nc.tipofactura = tifa.tipofactura_id INNER JOIN
				 cliente cl ON e.cliente = cl.cliente_id INNER JOIN vendedor v ON e.vendedor = v.vendedor_id LEFT JOIN 
				 egresoafip eafip ON e.egreso_id = eafip.egreso_id";

		$tipo_busqueda = filter_input(INPUT_POST, 'tipo_busqueda');
		switch ($tipo_busqueda) {
			case 1:
				$desde = filter_input(INPUT_POST, 'desde');
				$hasta = filter_input(INPUT_POST, 'hasta');
				$where = "nc.fecha BETWEEN '{$desde}' AND '{$hasta}' ORDER BY nc.fecha DESC";
				break;
		}
		
		$notacredito_collection = CollectorCondition()->get('NotaCredito', $where, 4, $from, $select);
		$this->view->buscar($notacredito_collection);
	}

	function consultar($arg) {
    	SessionHandler()->check_session();
		require_once 'tools/notacreditoPDFTool.php';
		require_once 'modules/configuracion/model.php';
		require_once "core/helpers/file.php";
		

		$notacredito_id = $arg;
		$this->model->notacredito_id = $notacredito_id;
		$this->model->get();

		$em = new Egreso();
		$em->egreso_id = $this->model->egreso_id;
		$em->get();

		$cm = new Configuracion();
		$cm->configuracion_id = 1;
		$cm->get();


		$select = "ncd.codigo_producto AS CODIGO, ncd.descripcion_producto AS DESCRIPCION, ncd.cantidad AS CANTIDAD,
				   pu.denominacion AS UNIDAD, ncd.descuento AS DESCUENTO, ncd.valor_descuento AS VD, 
				   ncd.costo_producto AS COSTO, ROUND(ncd.importe, 2) AS IMPORTE, ncd.iva AS IVA";
		$from = "notacreditodetalle ncd INNER JOIN producto p ON ncd.producto_id = p.producto_id INNER JOIN
				 productounidad pu ON p.productounidad = pu.productounidad_id";
		$where = "ncd.notacredito_id = {$notacredito_id}";
		$notacreditodetalle_collection = CollectorCondition()->get('NotaCreditoDetalle', $where, 4, $from, $select);
		
		$select_egresoafip = "eafip.punto_venta AS PUNTO_VENTA, eafip.numero_factura AS NUMERO_FACTURA, tf.nomenclatura AS TIPOFACTURA,
							  eafip.cae AS CAE, eafip.vencimiento AS FVENCIMIENTO, eafip.fecha AS FECHA, tf.tipofactura_id AS TF_ID";
		$from_egresoafip = "egresoafip eafip INNER JOIN tipofactura tf ON eafip.tipofactura = tf.tipofactura_id";
		$where_egresoafip = "eafip.egreso_id = {$egreso_id}";
		$egresoafip = CollectorCondition()->get('EgresoAfip', $where_egresoafip, 4, $from_egresoafip, $select_egresoafip);
		$egresoafip = (is_array($egresoafip)) ? $egresoafip : array();

		if (!empty($egresoafip)) {
			$em->punto_venta = $egresoafip[0]['PUNTO_VENTA'];
			$em->numero_factura = $egresoafip[0]['NUMERO_FACTURA'];		
		}		

		
		$notacreditoPDFHelper = new NotaCreditoPDF();
		$notacreditoPDFHelper->genera_notacredito($notacreditodetalle_collection, $cm, $em, $this->model);
		
		$em = new Egreso();
		$em->egreso_id = $this->model->egreso_id;
		$em->get();
		
		$this->view->consultar($notacreditodetalle_collection, $this->model, $egresoafip, $em, $notacredito_id);
	}

	function prepara_notacredito_afip($arg) {
		SessionHandler()->check_session();

		$egreso_id = $arg;
		
		$select = "nc.notacredito_id AS NCID";
		$from = "notacredito nc";
		$where = "nc.egreso_id = {$egreso_id}";
		$notacredito_id = CollectorCondition()->get('NotaCredito', $where, 4, $from, $select);
		$notacredito_id = $notacredito_id[0]['NCID'];
		
		$this->model->notacredito_id = $notacredito_id;
		$this->model->get();

		$em = new Egreso();
		$em->egreso_id = $egreso_id;
		$em->get();
		$tipofactura_id = $em->tipofactura->tipofactura_id;

		switch ($tipofactura_id) {
			case 1:
				$tiponc_id = 4;
				break;
			case 3:
				$tiponc_id = 5;
				break;
		}

		$tfm = new TipoFactura();
		$tfm->tipofactura_id = $tiponc_id;
		$tfm->get();

		$select = "ncd.codigo_producto AS CODIGO, ncd.descripcion_producto AS DESCRIPCION, ncd.cantidad AS CANTIDAD,
				   pu.denominacion AS UNIDAD, ncd.descuento AS DESCUENTO, ncd.valor_descuento AS VD, p.no_gravado AS NOGRAVADO,
				   ncd.costo_producto AS COSTO, ROUND(ncd.importe, 2) AS IMPORTE, ncd.iva AS IVA, p.exento AS EXENTO";
		$from = "notacreditodetalle ncd INNER JOIN producto p ON ncd.producto_id = p.producto_id INNER JOIN
				 productounidad pu ON p.productounidad = pu.productounidad_id";
		$where = "ncd.egreso_id = {$egreso_id}";
		$notacreditodetalle_collection = CollectorCondition()->get('NotaCreditoDetalle', $where, 4, $from, $select);

		$array_afip = FacturaAFIPTool()->preparaFacturaAFIPNC($tfm, $this->model, $notacreditodetalle_collection);
		unset($array_afip['array_alicuotas']);
		$array_afip['egreso_id'] = $egreso_id;
		$array_afip['notacredito_id'] = $notacredito_id;
		$this->view->prepara_notacredito_afip($array_afip);
	}

	function notacredito_afip() {
		SessionHandler()->check_session();
		
		$cm = new Configuracion();
		$cm->configuracion_id = 1;
		$cm->get();

		$notacredito_id = filter_input(INPUT_POST, 'notacredito_id');
		$this->model->notacredito_id = $notacredito_id;
		$this->model->get();

		$egreso_id = filter_input(INPUT_POST, 'egreso_id');
		$em = new Egreso();
		$em->egreso_id = $egreso_id;
		$em->get();
		$tipofactura_id = $em->tipofactura->tipofactura_id;

		switch ($tipofactura_id) {
			case 1:
				$tiponc_id = 4;
				break;
			case 3:
				$tiponc_id = 5;
				break;
		}

		$tfm = new TipoFactura();
		$tfm->tipofactura_id = $tiponc_id;
		$tfm->get();

		$select = "ncd.codigo_producto AS CODIGO, ncd.descripcion_producto AS DESCRIPCION, ncd.cantidad AS CANTIDAD,
				   pu.denominacion AS UNIDAD, ncd.descuento AS DESCUENTO, ncd.valor_descuento AS VD, p.no_gravado AS NOGRAVADO,
				   ncd.costo_producto AS COSTO, ROUND(ncd.importe, 2) AS IMPORTE, ncd.iva AS IVA, p.exento AS EXENTO";
		$from = "notacreditodetalle ncd INNER JOIN producto p ON ncd.producto_id = p.producto_id INNER JOIN
				 productounidad pu ON p.productounidad = pu.productounidad_id";
		$where = "ncd.egreso_id = {$egreso_id}";
		$notacreditodetalle_collection = CollectorCondition()->get('NotaCreditoDetalle', $where, 4, $from, $select);

		$resultadoAFIP = FacturaAFIPTool()->notaCreditoAFIP($cm, $tfm, $this->model, $em, $notacreditodetalle_collection);
		if (is_array($resultadoAFIP)) {
			$this->model = new NotaCredito();
			$this->model->notacredito_id = $notacredito_id;
			$this->model->get();
			$this->model->punto_venta = $cm->punto_venta;
			$this->model->numero_factura = $resultadoAFIP['NUMFACTURA'];
			$this->model->emitido_afip = 1;
			$this->model->save();
		}

		header("Location: " . URL_APP . "/egreso/consultar/{$egreso_id}");
	}
}
?>