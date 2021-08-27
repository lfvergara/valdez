<?php
require_once "modules/pedidovendedor/model.php";
require_once "modules/pedidovendedor/view.php";
require_once "modules/cliente/model.php";
require_once "modules/vendedor/model.php";
require_once "modules/producto/model.php";
require_once "modules/stock/model.php";
require_once "modules/pedidovendedordetalle/model.php";
require_once "modules/usuariovendedor/model.php";


class PedidoVendedorController {

	function __construct() {
		$this->model = new PedidoVendedor();
		$this->view = new PedidoVendedorView();
	}

	function panel($arg) {
    	SessionHandler()->check_session();
    	$usuario_rol = $_SESSION["data-login-" . APP_ABREV]["usuario-configuracionmenu"];
    	$usuario_id = $_SESSION["data-login-" . APP_ABREV]["usuario-usuario_id"];
    	$select = "uv.usuario_id AS USUID, uv.vendedor_id AS VENID";
		$from = "usuariovendedor uv";
		$where = "uv.usuario_id = {$usuario_id}";
		$usuariovendedor_id = CollectorCondition()->get('UsuarioVendedor', $where, 4, $from, $select);

    	$dia_actual = date('Y-m-d');
    	$select = "pv.pedidovendedor_id AS PEDVENID, CONCAT(date_format(pv.fecha, '%d/%m/%Y'), ' ', LEFT(pv.hora,5)) AS FECHA,
    			   UPPER(cl.razon_social) AS CLIENTE, UPPER(cl.nombre_fantasia) AS FANTASIA, pv.subtotal AS SUBTOTAL,
    			   pv.importe_total AS IMPORTETOTAL, UPPER(CONCAT(ve.APELLIDO, ' ', ve.nombre)) AS VENDEDOR,
    			   CASE pv.estadopedido WHEN 1 THEN 'inline-block' WHEN 2 THEN 'none' WHEN 3 THEN 'none' END AS DSPBTN,
    			   CASE pv.estadopedido WHEN 1 THEN 'SOLICITADO' WHEN 2 THEN 'PROCESADO' WHEN 3 THEN 'CANCELADO' END AS LBLEST,
    			   CASE pv.estadopedido WHEN 1 THEN 'primary' WHEN 2 THEN 'success' WHEN 3 THEN 'danger' END AS CLAEST,
    			   LPAD(pv.pedidovendedor_id, 8, 0) AS NUMPED";
		$from = "pedidovendedor pv INNER JOIN cliente cl ON pv.cliente_id = cl.cliente_id INNER JOIN
				 vendedor ve ON pv.vendedor_id = ve.vendedor_id INNER JOIN
				 estadopedido ep ON pv.estadopedido = ep.estadopedido_id";

		if ($usuario_rol == 5) {
			$vendedor_id = $usuariovendedor_id[0]['VENID'];
			$where = "pv.fecha = '{$dia_actual}' AND pv.vendedor_id = {$vendedor_id} ORDER BY cl.razon_social ASC";
		} else {
			$where = "pv.fecha = '{$dia_actual}' ORDER BY CONCAT(ve.APELLIDO, ' ', ve.nombre) DESC";
		}

		$pedidovendedor_collection = CollectorCondition()->get('PedidoVendedor', $where, 4, $from, $select);
		$pedidovendedor_collection = (is_array($pedidovendedor_collection) AND !empty($pedidovendedor_collection)) ? $pedidovendedor_collection : array();

		$select = "v.vendedor_id AS ID, CONCAT(v.apellido, ' ', v.nombre) AS DENOMINACION";
		$from = "vendedor v ORDER BY CONCAT(v.apellido, ' ', v.nombre) ASC";
		$vendedor_collection = CollectorCondition()->get('Vendedor', NULL, 4, $from, $select);
		$this->view->panel($pedidovendedor_collection, $vendedor_collection);
	}

	function agregar() {
    	SessionHandler()->check_session();
    	$usuario_rol = $_SESSION["data-login-" . APP_ABREV]["usuario-configuracionmenu"];
    	$usuario_id = $_SESSION["data-login-" . APP_ABREV]["usuario-usuario_id"];
    	$select = "uv.usuario_id AS USUID, uv.vendedor_id AS VENID";
		$from = "usuariovendedor uv";
		$where = "uv.usuario_id = {$usuario_id}";
		$usuariovendedor_id = CollectorCondition()->get('UsuarioVendedor', $where, 4, $from, $select);

		$select = "p.producto_id AS PRODUCTO_ID, CONCAT(pm.denominacion, ' ', p.denominacion) AS DENOMINACION,
				   pc.denominacion AS CATEGORIA, p.codigo AS CODIGO";
		$from = "producto p INNER JOIN productocategoria pc ON p.productocategoria = pc.productocategoria_id INNER JOIN
				 productomarca pm ON p.productomarca = pm.productomarca_id";
		$where = "p.oculto = 0";
		$groupby = "p.producto_id";
		$producto_collection = CollectorCondition()->get('Producto', $where, 4, $from, $select, $groupby);

		$select = "c.cliente_id AS CLIENTE_ID, LPAD(c.cliente_id, 5, 0) AS CODCLI, c.razon_social AS RAZON_SOCIAL,
				   CONCAT(dt.denominacion, ' ', c.documento) AS DOCUMENTO";
		$from = "cliente c INNER JOIN documentotipo dt ON c.documentotipo = dt.documentotipo_id";

		if ($usuario_rol == 5) {
			$vendedor_id = $usuariovendedor_id[0]['VENID'];
			$where = "c.oculto = 0 AND c.vendedor = {$vendedor_id}";
		} else {
			$where = "c.oculto = 0";
		}

		$cliente_collection = CollectorCondition()->get('Cliente', $where, 4, $from, $select);
		$this->view->agregar($producto_collection, $cliente_collection);
	}

	function traer_formulario_producto_ajax($arg) {
		$producto_id = $arg;
		$pm = new Producto();
		$pm->producto_id = $producto_id;
		$pm->get();
		$select = "MAX(s.stock_id) AS MAXID";
		$from = "stock s";
		$where = "s.producto_id = {$producto_id}";
		$stock_id = CollectorCondition()->get('Stock', $where, 4, $from, $select);
		$stock_id = $stock_id[0]['MAXID'];

		$sm = new Stock();
		$sm->stock_id = $stock_id;
		$sm->get();
		$cantidad_disponible = $sm->cantidad_actual;

		$this->view->traer_formulario_producto_ajax($pm, $cantidad_disponible);
	}

	function guardar() {
		SessionHandler()->check_session();

		$usuario_id = $_SESSION["data-login-" . APP_ABREV]["usuario-usuario_id"];
    	$select = "uv.usuario_id AS USUID, uv.vendedor_id AS VENID";
		$from = "usuariovendedor uv";
		$where = "uv.usuario_id = {$usuario_id}";
		$usuariovendedor_id = CollectorCondition()->get('UsuarioVendedor', $where, 4, $from, $select);
		if (is_array($usuariovendedor_id) AND !empty($usuariovendedor_id)) {
			$vendedor_id = $usuariovendedor_id[0]['VENID'];
		} else {
			header("Location: " . URL_APP . "/reporte/vdr_panel");
		}

		$vm = new Vendedor();
		$vm->vendedor_id = $vendedor_id;
		$vm->get();

		$cliente_id = filter_input(INPUT_POST, 'cliente');
		$cm = new Cliente();
		$cm->cliente_id = $cliente_id;
		$cm->get();

		$importe_total = filter_input(INPUT_POST, 'importe_total');
		$this->model->fecha = date('Y-m-d');
		$this->model->hora = date('H:i:s');
		$this->model->subtotal = filter_input(INPUT_POST, 'subtotal');
		$this->model->importe_total = $importe_total;
		$this->model->estadopedido = 1;
		$this->model->cliente_id = $cliente_id;
		$this->model->vendedor_id = $vendedor_id;
		$this->model->save();
		$pedidovendedor_id = $this->model->pedidovendedor_id;

		$pedidosvendedor_array = $_POST['egreso'];
		$pedidovendedordetalle_ids = array();
		foreach ($pedidosvendedor_array as $pedidovendedor) {
			$producto_id = $pedidovendedor['producto_id'];
			$cantidad = $pedidovendedor['cantidad'];
			$costo_producto = $pedidovendedor['costo'];
			$valor_descuento = $pedidovendedor['importe_descuento'];
			$importe = $pedidovendedor['costo_total'];

			$pm = new Producto();
			$pm->producto_id = $producto_id;
			$pm->get();

			$neto = $pm->costo;
			$flete = $pm->flete;
			$porcentaje_ganancia = $pm->porcentaje_ganancia;
			$valor_neto = $neto + ($flete * $neto / 100);
			$total_neto = $valor_neto * $cantidad;

			$ganancia_temp = $total_neto * ($porcentaje_ganancia / 100 + 1);
			$ganancia = round(($ganancia_temp - $total_neto),2);

			$edm = new PedidoVendedorDetalle();
			$edm->codigo_producto = $pedidovendedor['codigo'];
			$edm->descripcion_producto = $pedidovendedor['descripcion'];
			$edm->cantidad = $cantidad;
			$edm->descuento = $pedidovendedor['descuento'];
			$edm->valor_descuento = $valor_descuento;
			$edm->costo_producto = $costo_producto;
			$edm->iva = $pedidovendedor['iva'];
			$edm->importe = $importe;
			$edm->valor_ganancia = $ganancia;
			$edm->producto_id = $pedidovendedor['producto_id'];
			$edm->pedidovendedor_id = $pedidovendedor_id;
			$edm->save();
			$pedidovendedordetalle_ids[] = $edm->pedidovendedordetalle_id;
		}

		header("Location: " . URL_APP . "/pedidovendedor/consultar/{$pedidovendedor_id}");
	}

	function consultar($arg) {
    	SessionHandler()->check_session();
		require_once 'modules/configuracion/model.php';

		$pedidovendedor_id = $arg;
		$this->model->pedidovendedor_id = $pedidovendedor_id;
		$this->model->get();
		$cliente_id = $this->model->cliente_id;
		$vendedor_id = $this->model->vendedor_id;

		$cm = new Cliente();
		$cm->cliente_id = $cliente_id;
		$cm->get();

		$vm = new Vendedor();
		$vm->vendedor_id = $vendedor_id;
		$vm->get();

		$select = "pvd.codigo_producto AS CODIGO, pvd.descripcion_producto AS DESCRIPCION, pvd.cantidad AS CANTIDAD,
				   pu.denominacion AS UNIDAD, pvd.descuento AS DESCUENTO, pvd.valor_descuento AS VD,
				   pvd.costo_producto AS COSTO, ROUND(pvd.importe, 2) AS IMPORTE, pvd.iva AS IVA";
		$from = "pedidovendedordetalle pvd INNER JOIN producto p ON pvd.producto_id = p.producto_id INNER JOIN
				 productounidad pu ON p.productounidad = pu.productounidad_id";
		$where = "pvd.pedidovendedor_id = {$pedidovendedor_id}";
		$pedidovendedordetalle_collection = CollectorCondition()->get('PedidoVendedorDetalle', $where, 4, $from, $select);

		$this->view->consultar($pedidovendedordetalle_collection, $this->model, $cm, $vm);
	}

	function buscar($arg) {
    	SessionHandler()->check_session();
    	$usuario_rol = $_SESSION["data-login-" . APP_ABREV]["usuario-configuracionmenu"];
    	$usuario_id = $_SESSION["data-login-" . APP_ABREV]["usuario-usuario_id"];
    	$select = "uv.usuario_id AS USUID, uv.vendedor_id AS VENID";
		$from = "usuariovendedor uv";
		$where = "uv.usuario_id = {$usuario_id}";
		$usuariovendedor_id = CollectorCondition()->get('UsuarioVendedor', $where, 4, $from, $select);

    	$desde = filter_input(INPUT_POST, 'desde');
    	$hasta = filter_input(INPUT_POST, 'hasta');
    	$select = "pv.pedidovendedor_id AS PEDVENID, CONCAT(date_format(pv.fecha, '%d/%m/%Y'), ' ', LEFT(pv.hora,5)) AS FECHA,
    			   UPPER(cl.razon_social) AS CLIENTE, UPPER(cl.nombre_fantasia) AS FANTASIA, pv.subtotal AS SUBTOTAL,
    			   pv.importe_total AS IMPORTETOTAL, UPPER(CONCAT(ve.APELLIDO, ' ', ve.nombre)) AS VENDEDOR,
    			   CASE pv.estadopedido WHEN 1 THEN 'inline-block' WHEN 2 THEN 'none' WHEN 3 THEN 'none' END AS DSPBTN,
    			   CASE pv.estadopedido WHEN 1 THEN 'SOLICITADO' WHEN 2 THEN 'PROCESADO' WHEN 3 THEN 'CANCELADO' END AS LBLEST,
    			   CASE pv.estadopedido WHEN 1 THEN 'primary' WHEN 2 THEN 'success' WHEN 3 THEN 'danger' END AS CLAEST,
    			   LPAD(pv.pedidovendedor_id, 8, 0) AS NUMPED";
		$from = "pedidovendedor pv INNER JOIN cliente cl ON pv.cliente_id = cl.cliente_id INNER JOIN
				 vendedor ve ON pv.vendedor_id = ve.vendedor_id INNER JOIN
				 estadopedido ep ON pv.estadopedido = ep.estadopedido_id";
		if ($usuario_rol == 5) {
			$vendedor_id = $usuariovendedor_id[0]['VENID'];
			$where = "pv.fecha BETWEEN '{$desde}' AND '{$hasta}' AND pv.vendedor_id = {$vendedor_id} ORDER BY cl.razon_social ASC";
		} else {
			$where = "pv.fecha BETWEEN '{$desde}' AND '{$hasta}' ORDER BY CONCAT(ve.APELLIDO, ' ', ve.nombre) DESC";
		}

		$pedidovendedor_collection = CollectorCondition()->get('PedidoVendedor', $where, 4, $from, $select);
		$pedidovendedor_collection = (is_array($pedidovendedor_collection) AND !empty($pedidovendedor_collection)) ? $pedidovendedor_collection : array();

		$select = "v.vendedor_id AS ID, CONCAT(v.apellido, ' ', v.nombre) AS DENOMINACION";
		$from = "vendedor v ORDER BY CONCAT(v.apellido, ' ', v.nombre) ASC";
		$vendedor_collection = CollectorCondition()->get('Vendedor', NULL, 4, $from, $select);
		$this->view->panel($pedidovendedor_collection, $vendedor_collection);
	}

	function editar($arg) {
    	SessionHandler()->check_session();
    	$usuario_rol = $_SESSION["data-login-" . APP_ABREV]["usuario-configuracionmenu"];
    	$usuario_id = $_SESSION["data-login-" . APP_ABREV]["usuario-usuario_id"];
    	$select = "uv.usuario_id AS USUID, uv.vendedor_id AS VENID";
		$from = "usuariovendedor uv";
		$where = "uv.usuario_id = {$usuario_id}";
		$usuariovendedor_id = CollectorCondition()->get('UsuarioVendedor', $where, 4, $from, $select);

		$this->model->pedidovendedor_id = $arg;
		$this->model->get();
		$cliente_id = $this->model->cliente_id;

		$cm = new Cliente();
		$cm->cliente_id = $cliente_id;
		$cm->get();

		$select = "p.producto_id AS PRODUCTO_ID, CONCAT(pm.denominacion, ' ', p.denominacion) AS DENOMINACION,
				   pc.denominacion AS CATEGORIA, p.codigo AS CODIGO, p.stock_minimo AS STMINIMO, p.stock_ideal AS STIDEAL,
				   p.costo as COSTO, p.iva AS IVA, p.porcentaje_ganancia AS GANANCIA, (((p.costo * p.porcentaje_ganancia)/100)+p.costo) AS VENTA";
		$from = "producto p INNER JOIN productocategoria pc ON p.productocategoria = pc.productocategoria_id INNER JOIN
				 productomarca pm ON p.productomarca = pm.productomarca_id INNER JOIN productounidad pu ON p.productounidad = pu.productounidad_id LEFT JOIN
				 productodetalle pd ON p.producto_id = pd.producto_id LEFT JOIN proveedor prv ON pd.proveedor_id = prv.proveedor_id";
		$groupby = "p.producto_id";
		$producto_collection = CollectorCondition()->get('Producto', NULL, 4, $from, $select, $groupby);

		$select = "c.cliente_id AS CLIENTE_ID, LPAD(c.cliente_id, 5, 0) AS CODCLI, CONCAT(c.razon_social, '(', c.nombre_fantasia, ')') AS RAZON_SOCIAL,
				   CONCAT(dt.denominacion, ' ', c.documento) AS DOCUMENTO";
		$from = "cliente c INNER JOIN documentotipo dt ON c.documentotipo = dt.documentotipo_id";
		if ($usuario_rol == 5) {
			$vendedor_id = $usuariovendedor_id[0]['VENID'];
			$where = "c.oculto = 0 AND c.vendedor = {$vendedor_id}";
		} else {
			$where = "c.oculto = 0";
		}

		$cliente_collection = CollectorCondition()->get('Cliente', $where, 4, $from, $select);
		$select = "pvd.codigo_producto AS CODIGO, pvd.descripcion_producto AS DESCRIPCION, pvd.cantidad AS CANTIDAD,
				   pu.denominacion AS UNIDAD, pvd.descuento AS DESCUENTO, pvd.costo_producto AS COSTO, pvd.importe AS IMPORTE,
				   pvd.pedidovendedordetalle_id AS PEDVENID, pvd.producto_id AS PRODUCTO, pvd.valor_descuento AS VD,
				   pvd.iva AS IVA, pvd.valor_ganancia AS VALGAN";
		$from = "pedidovendedordetalle pvd INNER JOIN producto p ON pvd.producto_id = p.producto_id INNER JOIN
		 		 productounidad pu ON p.productounidad = pu.productounidad_id";
		$where = "pvd.pedidovendedor_id = {$arg}";
		$pedidovendedordetalle_collection = CollectorCondition()->get('PedidoVendedorDetalle', $where, 4, $from, $select);

		$this->view->editar($producto_collection, $cliente_collection, $pedidovendedordetalle_collection, $this->model, $cm);
	}

	function procesar($arg) {
    	SessionHandler()->check_session();
    	$usuario_rol = $_SESSION["data-login-" . APP_ABREV]["usuario-configuracionmenu"];
    	$usuario_id = $_SESSION["data-login-" . APP_ABREV]["usuario-usuario_id"];
    	$select = "uv.usuario_id AS USUID, uv.vendedor_id AS VENID";
		$from = "usuariovendedor uv";
		$where = "uv.usuario_id = {$usuario_id}";
		$usuariovendedor_id = CollectorCondition()->get('UsuarioVendedor', $where, 4, $from, $select);

		$this->model->pedidovendedor_id = $arg;
		$this->model->get();
		$cliente_id = $this->model->cliente_id;

		$cm = new Cliente();
		$cm->cliente_id = $cliente_id;
		$cm->get();

		$select = "p.producto_id AS PRODUCTO_ID, CONCAT(pm.denominacion, ' ', p.denominacion) AS DENOMINACION,
				   pc.denominacion AS CATEGORIA, p.codigo AS CODIGO, p.stock_minimo AS STMINIMO, p.stock_ideal AS STIDEAL,
				   p.costo as COSTO, p.iva AS IVA, p.porcentaje_ganancia AS GANANCIA, (((p.costo * p.porcentaje_ganancia)/100)+p.costo) AS VENTA";
		$from = "producto p INNER JOIN productocategoria pc ON p.productocategoria = pc.productocategoria_id INNER JOIN
				 productomarca pm ON p.productomarca = pm.productomarca_id INNER JOIN productounidad pu ON p.productounidad = pu.productounidad_id LEFT JOIN
				 productodetalle pd ON p.producto_id = pd.producto_id LEFT JOIN proveedor prv ON pd.proveedor_id = prv.proveedor_id";
		$groupby = "p.producto_id";
		$producto_collection = CollectorCondition()->get('Producto', NULL, 4, $from, $select, $groupby);

		$select = "c.cliente_id AS CLIENTE_ID, LPAD(c.cliente_id, 5, 0) AS CODCLI, CONCAT(c.razon_social, '(', c.nombre_fantasia, ')') AS RAZON_SOCIAL,
				   CONCAT(dt.denominacion, ' ', c.documento) AS DOCUMENTO";
		$from = "cliente c INNER JOIN documentotipo dt ON c.documentotipo = dt.documentotipo_id";
		if ($usuario_rol == 5) {
			$vendedor_id = $usuariovendedor_id[0]['VENID'];
			$where = "c.oculto = 0 AND c.vendedor = {$vendedor_id}";
		} else {
			$where = "c.oculto = 0";
		}

		$cliente_collection = CollectorCondition()->get('Cliente', $where, 4, $from, $select);
		$select = "pvd.codigo_producto AS CODIGO, pvd.descripcion_producto AS DESCRIPCION, pvd.cantidad AS CANTIDAD,
				   pu.denominacion AS UNIDAD, pvd.descuento AS DESCUENTO, pvd.costo_producto AS COSTO, pvd.importe AS IMPORTE,
				   pvd.pedidovendedordetalle_id AS PEDVENID, pvd.producto_id AS PRODUCTO, pvd.valor_descuento AS VD,
				   pvd.iva AS IVA, pvd.valor_ganancia AS VALGAN";
		$from = "pedidovendedordetalle pvd INNER JOIN producto p ON pvd.producto_id = p.producto_id INNER JOIN
		 		 productounidad pu ON p.productounidad = pu.productounidad_id";
		$where = "pvd.pedidovendedor_id = {$arg}";
		$pedidovendedordetalle_collection = CollectorCondition()->get('PedidoVendedorDetalle', $where, 4, $from, $select);

		$this->view->procesar($producto_collection, $cliente_collection, $pedidovendedordetalle_collection, $this->model, $cm);
	}


	function actualizar() {
		SessionHandler()->check_session();

		$pedidovendedor_id = filter_input(INPUT_POST, 'pedidovendedor_id');
		$this->model->pedidovendedor_id = $pedidovendedor_id;
		$this->model->get();

		$fecha = date('Y-m-d');
		$hora = date('H:i:s');
		$importe_total = filter_input(INPUT_POST, 'importe_total');
		$this->model->fecha = $fecha;
		$this->model->hora = $hora;
		$this->model->subtotal = filter_input(INPUT_POST, 'subtotal');
		$this->model->importe_total = $importe_total;
		$this->model->cliente_id = filter_input(INPUT_POST, 'cliente');
		$this->model->save();

		$select = "pvd.pedidovendedordetalle_id AS ID";
		$from = "pedidovendedordetalle pvd";
		$where = "pvd.pedidovendedor_id = {$pedidovendedor_id}";
		$pedidovendedordetalle_collection = CollectorCondition()->get('PedidoVendedorDetalle', $where, 4, $from, $select);

		if (!empty($pedidovendedordetalle_collection) AND is_array($pedidovendedordetalle_collection)) {
			foreach ($pedidovendedordetalle_collection as $pedidovendedordetalle) {
				$temp_pedidovendedordetalle_id = $pedidovendedordetalle['ID'];
				$pvdm = new PedidoVendedorDetalle();
				$pvdm->pedidovendedordetalle_id = $temp_pedidovendedordetalle_id;
				$pvdm->delete();
			}
		}

		$pedidovendedor_array = $_POST['egreso'];
		foreach ($pedidovendedor_array as $pedidovendedor) {
			$producto_id = $pedidovendedor['producto_id'];
			$cantidad = $pedidovendedor['cantidad'];
			$costo_producto = $pedidovendedor['costo'];
			$valor_descuento = $pedidovendedor['importe_descuento'];
			$importe = $pedidovendedor['costo_total'];

			$pm = new Producto();
			$pm->producto_id = $producto_id;
			$pm->get();

			$neto = $pm->costo;
			$flete = $pm->flete;
			$porcentaje_ganancia = $pm->porcentaje_ganancia;
			$valor_neto = $neto + ($flete * $neto / 100);
			$total_neto = $valor_neto * $cantidad;

			$ganancia_temp = $total_neto * ($porcentaje_ganancia / 100 + 1);
			$ganancia = round(($ganancia_temp - $total_neto),2);

			$edm = new PedidoVendedorDetalle();
			$edm->codigo_producto = $pedidovendedor['codigo'];
			$edm->descripcion_producto = $pedidovendedor['descripcion'];
			$edm->cantidad = $cantidad;
			$edm->descuento = $pedidovendedor['descuento'];
			$edm->valor_descuento = $valor_descuento;
			$edm->costo_producto = $costo_producto;
			$edm->iva = $pedidovendedor['iva'];
			$edm->importe = $importe;
			$edm->valor_ganancia = $ganancia;
			$edm->producto_id = $pedidovendedor['producto_id'];
			$edm->pedidovendedor_id = $pedidovendedor_id;
			$edm->save();
		}

		header("Location: " . URL_APP . "/pedidovendedor/consultar/{$pedidovendedor_id}");
	}

	function descargar() {
		SessionHandler()->check_session();
		require_once "tools/excelreport_pedidos.php";
		$fecha_sys = strtotime(date('Y-m-d'));

		//PARÁMETROS
		$tipo_descarga = filter_input(INPUT_POST, 'tipo_busqueda');
		$vendedor_id = filter_input(INPUT_POST, 'vendedor');
		$desde = filter_input(INPUT_POST, 'desde');
		$hasta = filter_input(INPUT_POST, 'hasta');

		$vm = new Vendedor();
		$vm->vendedor_id = $vendedor_id;
		$vm->get();
		$vendedor_denominacion = $vm->apellido . ' ' . $vm->nombre;

		$select = "CONCAT(v.apellido, ' ', v.nombre) AS VENDEDOR, pv.pedidovendedor_id AS NUMPED, c.razon_social AS CLIENTE,
				   c.nombre_fantasia AS FANTASIA, CONCAT(pm.denominacion, ' ', pr.denominacion) AS PRODUCTO, pvd.descuento AS PORDES, pvd.valor_descuento AS VALDES,
				   pvd.cantidad AS CANTIDAD, pvd.costo_producto AS COSTO, pvd.importe AS IMPORTE, c.cliente_id AS CLIID,
				   tf.nomenclatura AS NOMENCLATURA,pr.codigo AS CODIGO";
		$from = "pedidovendedor pv INNER JOIN cliente c ON pv.cliente_id = c.cliente_id INNER JOIN vendedor v ON pv.vendedor_id = v.vendedor_id INNER JOIN
		  		 pedidovendedordetalle pvd ON pv.pedidovendedor_id = pvd.pedidovendedor_id INNER JOIN tipofactura tf ON c.tipofactura = tf.tipofactura_id INNER JOIN
		  		 producto pr ON pvd.producto_id = pr.producto_id INNER JOIN productomarca pm ON pr.productomarca = pm.productomarca_id";

		switch ($tipo_descarga) {
			case 1:
				if ($vendedor_id == 'all') {
					$where = "pv.fecha BETWEEN '{$desde}' AND '{$hasta}' ORDER BY c.razon_social ASC";
				} else {
					$where = "pv.fecha BETWEEN '{$desde}' AND '{$hasta}' AND pv.vendedor_id = {$vendedor_id} ORDER BY c.razon_social ASC";
				}

				$subtitulo = "{$vendedor_denominacion} ({$desde} - {$hasta})";
				break;
		}

		$datos_reporte = CollectorCondition()->get('PedidoVendedor', $where, 4, $from, $select);
		if (is_array($datos_reporte) AND !empty($datos_reporte)) {
			$cliente_ids = array();
			$pedidos = array();

			$array_exportacion = array();
			foreach ($datos_reporte as $clave=>$valor) {
				$cliente_id = $valor["CLIID"];
				$numpedido = $valor["NUMPED"];

				if (!in_array($cliente_id, $cliente_ids) AND !in_array($numpedido, $pedidos)) {
					$cliente_ids[] = $cliente_id;
					$pedidos[] = $numpedido;
					$array_encabezados = array($valor["CLIENTE"], $valor["NOMENCLATURA"], 'P'. $valor["NUMPED"]);
					$array_exportacion[] = $array_encabezados;
					$array_temp = array();
					$array_temp = array($valor["CODIGO"].' - '.$valor["PRODUCTO"]
										, $valor["CANTIDAD"]
										, $valor["PORDES"] . '%');
					$array_exportacion[] = $array_temp;
				} else {
					if (in_array($cliente_id, $cliente_ids) AND !in_array($numpedido, $pedidos)) {
						$pedidos[] = $numpedido;
						$array_encabezados = array($valor["CLIENTE"], $valor["NOMENCLATURA"], 'P'. $valor["NUMPED"]);
						$array_exportacion[] = $array_encabezados;
						$array_temp = array();
						$array_temp = array($valor["CODIGO"].' - '.$valor["PRODUCTO"]
											, $valor["CANTIDAD"]
											, $valor["PORDES"] . '%');
						$array_exportacion[] = $array_temp;
					} else {
						$array_temp = array();
						$array_temp = array($valor["CODIGO"].' - '.$valor["PRODUCTO"]
											, $valor["CANTIDAD"]
											, $valor["PORDES"] . '%');
						$array_exportacion[] = $array_temp;
					}
				}
			}
		}

		ExcelReport()->extraer_informe_conjunto($subtitulo, $array_exportacion);
		exit;
	}

	function eliminar($arg) {
    	SessionHandler()->check_session();
    	$pedidovendedor_id = $arg;
    	$select = "pvd.pedidovendedordetalle_id AS ID";
		$from = "pedidovendedordetalle pvd";
		$where = "pvd.pedidovendedor_id = {$pedidovendedor_id}";
		$pedidovendedordetalle_ids = CollectorCondition()->get('PedidoVendedorDetalle', $where, 4, $from, $select);

		if (is_array($pedidovendedordetalle_ids) AND !empty($pedidovendedordetalle_ids)) {
			foreach ($pedidovendedordetalle_ids as $clave=>$valor) {
				$pedidovendedordetalle_id = $valor['ID'];
				$pvdm = new PedidoVendedorDetalle();
				$pvdm->pedidovendedordetalle_id = $pedidovendedordetalle_id;
				$pvdm->delete();
			}
		}

		$this->model->pedidovendedor_id = $pedidovendedor_id;
		$this->model->delete();

		header("Location: " . URL_APP . "/pedidovendedor/panel");
	}
}
?>
