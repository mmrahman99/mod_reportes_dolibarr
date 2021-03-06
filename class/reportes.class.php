<?php
require_once ('tercero.class.php');
/*

En esta serie de lclases vamos a necesitar aproximadamente 3

reportes, que sera la encargada de generar todas las consultas de los Reportes

terceros que sera la encargada de cargar los datos de vendedor y rart las listas de cleintes correspondientes

productos que  solos e encarga de traer los productos 

completos y extendida para algunos datos mas.

*/



class Reportes 
{
    var $db;      // instancia de conexion
	var $id_usuario; // el identificador del usuario (vendedores)  o todos
	var $codVendedor;  // codigo de vendedor

	var $fecha_ini;   // fecha de inicio rango de fecha
	var $fecha_fin;   // fecha de fin de rango  para consultas
	var $producto;     // codigo de referencia del producto 
    var $ruta;          // numero de ruta para hacer la consulta

	function __construct($db, $id_usuario, $fecha_ini, $fecha_fin, $producto, $ruta )
	{
		$this->db = $db;
        $this->id_usuario = $id_usuario;
        $this->fecha_ini= $this->change_fecha($fecha_ini);
        $this->fecha_fin= $this->change_fecha($fecha_fin);		
        $this->producto= $producto;
        $this->ruta = $ruta;

		$tercero = new Tercero($this->db, $this->id_usuario);
		$this->codVendedor = $tercero->getCodVendedor($this->id_usuario);
	}





// metodos




function getReporte()
{
        
        $clientes = $this->getClientes();
        $dato= null;
        $clientes_totales= count($clientes, 0);

        if($clientes != null)
        {
            $total_prod=0;      // total de los productos de toda la lista
            $total_importe=0;   // total importe
            $c_con_ventas=0;   // clientes con ventas


                // codigo de prueba


                // var_dump("codVendedor ".$this->codVendedor."-".


                //  $this->id_usuario ."-".$this->producto  ."- ruta ".$this->ruta );

                        // $this->db = $db;
                        // $this->id_usuario = $id_usuario;
                        // $this->fecha_ini= $this->change_fecha($fecha_ini);
                        // $this->fecha_fin= $this->change_fecha($fecha_fin);		
                        // $this->producto= $producto;
                        // $this->ruta = $ruta;


                // var_dump($clientes);
                // exit;


// codigo de prueba

                foreach($clientes as $cliente)
                {

                    $cantidades = $this->getFacturas($cliente['rowid']);  // traigo las cantidades 
                    
                    $ultimaFecha = $this->lastInvoiceDate($cliente['rowid']); // traigo la ultima fecha que le facturaron al cliente
                    $comprobantes = $this->getCantidadComprobantes($cliente['rowid']);        // aqui traigo la cantidad de comprobantes del cliente

                    (is_null($cantidades['valor'])) ? $valor = 0: $valor = $cantidades['valor'];

                    (is_null($cantidades['cantidad'])) ? $cantidad = 0: $cantidad = $cantidades['cantidad'];

                        $dato[]= array( "codigo" => $cliente['code_client'],

                            "nombre" => $cliente['nom'],
                            "direccion" => $cliente['address'],
                            "importe" => $valor,
                            "cantidad" =>$cantidad,
                            "ultimaFactura" => $ultimaFecha['last'],
                            "localidad" => $cliente['town'],
                            "comprobantes" => $comprobantes['comprobante'],                           
                            "ruta"=> $cliente['ruta'],
                            "vendedor"=> $cliente['vendedor']
                        
                        );

                    $total_prod= $total_prod + $cantidades['cantidad'] ;
                    $total_importe= $total_importe + $cantidades['valor'] ;

                    if($cantidades['valor'] > 0) // quiere decir que por lo menos tiene una factura a su nombre en las fechas indicadas
                    {

                        $c_con_ventas++ ;

                    }

                }


            $total= array( "total_prod" => $total_prod,
                            "total_importe" => $total_importe,
                            "total_clientes"=> $clientes_totales,
                            "clientes_con_ventas"=>$c_con_ventas,
                            "clientes_sin_ventas"=>$clientes_totales - $c_con_ventas
            );


        }
        else{

            $dato= "No hay Clientes asignados";
        }

        $datoSort= $this->orderMultiDimensionalArray($dato, 'cantidad', true);

       return  array($datoSort, $total);


}







function lastInvoiceDate($id_cliente)

    {



            // esta consulta devuelve si es un numero de id de producto especifico
            $sql ="SELECT rowid,  DATE_FORMAT(datef,'%d/%m/%Y') AS datef  FROM llx_facture
            WHERE fk_soc = ".$id_cliente." AND  datef BETWEEN '".$this->fecha_ini."' AND '".$this->fecha_fin."' ORDER BY datef DESC LIMIT 1 ";



       

    
            $res = $this->db->query($sql);
            $num = $this->db->num_rows($res);
            // si devuelve producto  entro al proceso
            if ($num){

            $obj = $this->db->fetch_object($res);
                if ($obj)
                {
                    
                    // aqui guardo el valor total de ventas
                    (empty($obj->datef)) ? $valor = "Sin registro": $valor = $obj->datef;

                    $fecha=array('last'=> $valor);

                }
        
            }


            return $fecha;

    }



//todo ajustar consultas para que traiga el  nombre del vendedor

function getClientes()  //trae los clientes correspondientes al vendedor

    {

        if ($this->codVendedor != 0) // representa a un vendedor en especifico
        {

            if($this->ruta != 0){  // representa a una ruta en especifico1

                    $sql="SELECT  llx_societe.code_client, 
                                llx_societe.rowid , 
                                llx_societe.nom, llx_societe.address, 
                                llx_societe.town ,llx_societe_extrafields.ruta1 AS ruta, 
                                llx_user.firstname AS vendedor
                            FROM    llx_societe 
                            INNER JOIN llx_societe_extrafields ON llx_societe.rowid= llx_societe_extrafields.fk_object
                            INNER JOIN llx_user_extrafields ON llx_user_extrafields.codvendedor= llx_societe_extrafields.vendedor
                            INNER JOIN llx_user ON llx_user.rowid = llx_user_extrafields.fk_object
                            WHERE   llx_societe_extrafields.vendedor = " .$this->codVendedor." AND llx_societe_extrafields.ruta1 = " .$this->ruta. " 
                            AND llx_societe.status = 1 AND llx_societe.client= 1 
                            ORDER BY code_client DESC ";

            }else{      // representa a todas las rutas

                    $sql="SELECT  llx_societe.code_client, 
                                    llx_societe.rowid , 
                                    llx_societe.nom, llx_societe.address, 
                                    llx_societe.town ,llx_societe_extrafields.ruta1 AS ruta, 
                                    llx_user.firstname AS vendedor
                            FROM    llx_societe 
                            INNER JOIN llx_societe_extrafields ON llx_societe.rowid= llx_societe_extrafields.fk_object
                            INNER JOIN llx_user_extrafields ON llx_user_extrafields.codvendedor= llx_societe_extrafields.vendedor
                            INNER JOIN llx_user ON llx_user.rowid = llx_user_extrafields.fk_object
                            WHERE   llx_societe_extrafields.vendedor = " .$this->codVendedor. "  AND llx_societe.status = 1
                            AND llx_societe.client= 1 
                            ORDER BY code_client DESC";

            }
            


        }else{   // aqui se representa a todos los vendedores


                if($this->ruta != 0){
                    // consulta que representa a una ruta especifica

                    $sql= "SELECT  llx_societe.code_client, 
                                    llx_societe.rowid , 
                                    llx_societe.nom, llx_societe.address, 
                                    llx_societe.town ,llx_societe_extrafields.ruta1 AS ruta, 
                                    llx_user.firstname AS vendedor
                            FROM    llx_societe
                            INNER JOIN llx_societe_extrafields ON llx_societe.rowid= llx_societe_extrafields.fk_object
                            INNER JOIN llx_user_extrafields ON llx_user_extrafields.codvendedor= llx_societe_extrafields.vendedor
                            INNER JOIN llx_user ON llx_user.rowid = llx_user_extrafields.fk_object
                            WHERE   llx_societe_extrafields.ruta1 = " .$this->ruta. " AND llx_societe.status = 1  AND llx_societe.client= 1 
                            ORDER BY code_client DESC";


                }else{
                    // consulta que representa a todas las rutas

                    $sql= "SELECT  llx_societe.code_client, 
                                llx_societe.rowid , 
                                llx_societe.nom, llx_societe.address, 
                                llx_societe.town ,llx_societe_extrafields.ruta1 AS ruta, 
                                llx_user.firstname AS vendedor
                            FROM    llx_societe 
                            INNER JOIN llx_societe_extrafields ON llx_societe.rowid= llx_societe_extrafields.fk_object
                            INNER JOIN llx_user_extrafields ON llx_user_extrafields.codvendedor= llx_societe_extrafields.vendedor
                            INNER JOIN llx_user ON llx_user.rowid = llx_user_extrafields.fk_object
                            WHERE llx_societe.status = 1 AND llx_societe.client= 1 
                            ORDER BY code_client DESC";

                }



        }



            $resql = $this->db->query($sql);

            if ($resql)
            {
                $num = $this->db->num_rows($resql);
                $i = 0;
                if ($num)
                {
                        while ($i < $num)
                        {
                                $obj = $this->db->fetch_object($resql);
                                if ($obj)
                                {
                                        // You can use here results
                                        $respuesta[]= array(
                                            'rowid'=> $obj->rowid,
                                            'code_client'=> $obj->code_client,
                                            'nom'=>$obj->nom,
                                            'address'=> $obj->address,
                                            'town'=> $obj->town,
                                            'ruta'=> $obj->ruta,
                                            'vendedor'=> $obj->vendedor

                                        );
                                }
                                $i++;
                        }
                }
            }else{ $respuesta = 'hay un error en la conexion';}

            $this->db->free($resql);
            return  $respuesta;


    }



function getFacturas($id_cliente)
    {

        $sql ="SELECT rowid, datef FROM llx_facture
        WHERE fk_soc = ".$id_cliente." AND  datef BETWEEN '".$this->fecha_ini."' AND '".$this->fecha_fin."' ORDER BY datef DESC ";


        $res = $this->db->query($sql);
        
        // si devuelve producto  entro al proceso
        if ($res){

            $num = $this->db->num_rows($res);

            $i = 0;
            if ($num)
            {
                $tmp_cantidad=0;
                $tmp_valor =0;

                    while ($i < $num)
                    {
                            $factura = $this->db->fetch_object($res);
                                if ($factura)
                                {
                                    // llamar al metodo que traE las cantidades 
                                    $dato= $this->getcantidadDetalle($factura->rowid);
                                    $tmp_cantidad= $tmp_cantidad +$dato['cantidad'];
                                    $tmp_valor = $tmp_valor + $dato['valor'];
                                        
                                }
                            $i++;

                    }


                    $cantidad = ($tmp_cantidad == null) ? $cantidad=0: $cantidad= $tmp_cantidad;

                    $valor = ($tmp_valor == null) ? $valor=0: $valor= $tmp_valor;

            }


                $datos= ['cantidad'=> $cantidad,
                        'valor'=> $valor,
                        'facturas'=> $num

                ];

                $this->db->free($res);
                return $datos;
        }

    }


function getcantidadDetalle($id_factura_detalle)   // devuelve por cliente la cantidad de producto vendido y el valor

    {


        if($this->producto == 0){

            $sql = "SELECT  IFNULL( SUM(qty),0) AS productos  ,IFNULL( SUM(total_ht),0)  AS valor 
            FROM llx_facturedet WHERE fk_facture = ".$id_factura_detalle;

        }else{



            $sql = "SELECT  IFNULL( SUM(qty),0) AS productos  ,IFNULL( SUM(total_ht),0)  AS valor 
            FROM llx_facturedet WHERE fk_facture = ".$id_factura_detalle." AND fk_product= ".$this->producto;


        }
    
    

            $res = $this->db->query($sql);
            $num = $this->db->num_rows($res);
            // si devuelve producto  entro al proceso
            if ($num){

                $obj = $this->db->fetch_object($res);
                if ($obj)
                {


                    $datos= ['cantidad'=> $obj->productos,
                            'valor'=> $obj->valor
                    ];
                }

                
            }

            return $datos;
    }




    /*

    Esta modificacion sera para integrar la funcion de reportes de comprobantes por clientes
    y para que el modulo pueda buscar por producto especifico  o por todos los productos 




    */



function getReportecomprobantes(){

        $clientes = $this->getClientes();
        $dato= null;
        //$clientes_totales= count($clientes, 0);

        if($clientes != null)
        {
            $total_prod=0;
            $total_importe=0;
            $c_con_ventas=0;


                foreach($clientes as $cliente){

                    $comp= $this->getCantidadComprobantes($cliente['rowid']);
                    $dato[]= array( "codigo" => $cliente['code_client'],

                    "nombre" => $cliente['nom'],
                    "direccion" => $cliente['address'],
                    "localidad" => $cliente['town'],
                    "comprobantes"=>$comp['comprobante'],
                    "ruta"=> $cliente['ruta']
                    
                    );

                }


        }        
        else{

            $dato= "No hay Clientes asignados";
        }

return  $this->orderMultiDimensionalArray($dato,'comprobantes', true);

}



function getCantidadComprobantes($id_Cliente){


            $sql="	SELECT COUNT(*) AS comprobantes 
                    FROM llx_facture 
                    WHERE fk_soc = '".$id_Cliente ."'  
                    AND datef BETWEEN '". $this->fecha_ini ."' 
                    AND '". $this->fecha_fin ."' ";


            $resql = $this->db->query($sql);

            if ($resql)
            {
                $num = $this->db->num_rows($resql);
                $i = 0;
                if ($num)
                {

                   
                        while ($i < $num)
                        {
                                $obj = $this->db->fetch_object($resql);
                                if ($obj)
                                {
                                     
                                        // You can use here results
                                        $respuesta= array(
                                            'comprobante'=> $obj->comprobantes


                                        );
                                }
                                $i++;
                        }
                }
            }else{ $respuesta = 'hay un error en la conexion';}

            $this->db->free($resql);
            return  $respuesta;

}







private function change_fecha($fecha)
    {

        $dia = substr($fecha, 0, 2);
        $mes   = substr($fecha, 3, 2);
        $ano = substr($fecha, -4);
        // fechal final realizada el cambio de formato a las fechas europeas
        $fecha = $ano . '-' . $mes . '-' . $dia;

        return $fecha;

    }






function orderMultiDimensionalArray ($toOrderArray, $field, $inverse = false) {
    $position = array();
    $newRow = array();
    foreach ($toOrderArray as $key => $row) {
            $position[$key]  = $row[$field];
            $newRow[$key] = $row;
    }
    if ($inverse) {
        arsort($position);
    }
    else {
        asort($position);
    }
    $returnArray = array();
    foreach ($position as $key => $pos) {     
        $returnArray[] = $newRow[$key];
    }
    return $returnArray;
}






}