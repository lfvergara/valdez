<?php
class User {
	static function get_usuariodetalle_id($hash) {
	    $sql = "SELECT 
	    			usuariodetalle_id
	    		FROM 
	    			usuariodetalle 
	    		WHERE 
	    			token = ?";
	    $datos = array($hash);
        $result = execute_query($sql, $datos);
		return isset($result[0]) ? $result[0]['usuariodetalle_id'] : 0;
	}

	static function get_usuario_id($usuariodetalle_id) {
	    $sql = "SELECT 
	    			usuario_id
	    		FROM 
	    			usuario 
	    		WHERE 
	    			usuariodetalle = ?";
	    $datos = array($usuariodetalle_id);
        $result = execute_query($sql, $datos);
		return isset($result[0]) ? $result[0]['usuario_id'] : 0;
	}
}

function User() {return new User();}
?>