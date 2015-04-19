<?php

// Llamando libreria.
require_once "lib/nusoap.php";
require_once "api/SendSMS.php";
require_once "api/IncomingFormat.php";

// creando una instancia de servidor
$server = new soap_server();

// inicializando la configuracion del WSDL
$server->configureWSDL("sms", "urn:sms");



$errstr = "";
$source = "BMSCtest";

function mydie($errstr) {
    die ("Error: " . $errstr . "\n");
}
// definir los métodos
function sendSMS($request) {

     $sms_username = $request['user'];
     $sms_password = $request['pass'];
     $message = $request['message'];
     $destination = $request['destination'];
     $user_reference = $request['user_reference'];

    global $source, $errstr;
    # Construct an SMS object
    $sms = new SMS();

    $sms->setUN($sms_username) or mydie($errstr);

    $sms->setP($sms_password) or mydie($errstr);

    # Set the destination address 
    $sms->setDA($destination) or mydie($errstr);

    # Set the source address
    $sms->setSA($source) or mydie($errstr);

    # Set the user reference
    $sms->setUR($user_reference) or mydie($errstr);

    # Set delivery receipts to 'on'
    $sms->setDR("1") or mydie ($errstr);

    # Set the message content
    $sms->setMSG($message) or mydie ($errstr);

    # Send the message and inspect the responses
    $responses = send_sms_object($sms) or mydie ($errstr);


    $pieces = explode(" ", $responses);


    if($pieces[0] == 'OK')
    {
       
        $status = $pieces[0];
        $error =  0;
        $detail = $pieces[1];
    }
    elseif($pieces[0]=='ERR')
    {
        if($pieces[1]=='-10')
        {

            $status = $pieces[0];
            $error =  $pieces[1];
            $detail = "Nombre de usuario o contraseña no válida.";
        }
        elseif($pieces[1]=='-15')
        {

            $status = $pieces[0];
            $error =  $pieces[1];
            $detail = "Destinatario no válido.";
        }
        elseif($pieces[1]=='-5')
        {

            $status = $pieces[0];
            $error =  $pieces[1];
            $detail = "No hay suficiente crédito.";

        }
        elseif($pieces[1]=='-20')
        {

            $status = $pieces[0];
            $error =  $pieces[1];
            $detail = "Error del sistema.";

        }
        elseif($pieces[1]=='-25')
        {

            $status = $pieces[0];
            $error =  $pieces[1];
            $detail = "Error de solicitud del servicio.";

        }
    }

        $file = 'log.txt';
        // Open the file to get existing content
        $current = file_get_contents($file);
        // Append a new person to the file
        $current .= "sendSMS-> ".$sms_username."|".$sms_password."|".$message."|".$destination."|".$user_reference."|".$pieces[0]."|".$detail."|".date("r").";"."\n";
        // Write the contents back to the file
        file_put_contents($file, $current);
        
return array(
                'status' => $status,
                'error' => $error,
                'detail' => $detail,
                'user_reference' => $user_reference
            );
}

function getstatus ($request) {

	$sms_username = $request['user'];
    $sms_password = $request['pass'];
    $user_reference = $request['user_reference'];

    $username = urlencode($sms_username);
    $password = urlencode($sms_password);

    $request = "http://sms1.cardboardfish.com:9001/ClientDR/ClientDR?&UN=${username}&P=${password}";
    $ch = curl_init($request);

    if (!$ch) {
        $errstr = "Could not connect to server.";
        $serverresponse = $errstr;
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $serverresponse = curl_exec($ch);

    $pieces = explode(":", $serverresponse);


    $max = sizeof($pieces);

    $i = 0;
    $point = '';
    while ($i < $max)
    {
       if($pieces[$i]==$user_reference)
        {    
            $point = $i;
            $i = $max;
        }

        $i++;
    }

    $id = $pieces[$point-6];
    $user_reference = $pieces[$point];
    $status = $pieces[$point-3];

    $id = substr($id, 2);

    if($pieces[$point] == $user_reference)
    {

	    if($pieces[$point-3]=='1')
	    {
			$detail = "Entregado";
	    }
	    elseif($pieces[$point-3]=='2')
	    {
			$detail = "En búfer";
	    }
	    elseif($pieces[$point-3]=='3')
	    {
			$detail = "Fracasado";
	    }
	    elseif($pieces[$point-3]=='5')
	    {
			$detail = "Expirado";
	    }
	    elseif($pieces[$point-3]=='6')
	    {
			$detail = "Rechazado";
	    }
	    elseif($pieces[$point-3]=='7')
	    {
			$detail = "Error";
	    }
	}
    else
    {
        $id = "";
        $user_reference = "";
        $status = "-1";
        $detail = "No registrado";
    }
	
    if($serverresponse=='0#')
	{
	    $id = "";
	    $user_reference = "";
	    $status = "-1";
	    $detail = "No registrado";
	}


$pieces2 = explode(" ", $serverresponse);

    if($pieces2[0]=='ERR')
    {
        if($pieces2[1]=='-10')
        {
            $status =  $pieces2[1];
            $detail = "Nombre de usuario o contraseña no válida.";
        }
        elseif($pieces2[1]=='-15')
        {
            $status =  $pieces2[1];
            $detail = "Destinatario no válido.";
        }
        elseif($pieces2[1]=='-5')
        {

            $status =  $pieces2[1];
            $detail = "No hay suficiente crédito.";
        }
        elseif($pieces2[1]=='-20')
        {
            $status =  $pieces2[1];
            $detail = "Error del sistema.";
        }
        elseif($pieces2[1]=='-25')
        {
            $status =  $pieces2[1];
            $detail = "Error de solicitud del servicio.";
        }
    }

        $file = 'log.txt';
        // Open the file to get existing content
        $current = file_get_contents($file);
        // Append a new person to the file
        $current .= "getstatus-> ".$id."|".$user_reference."|".$status."|".$detail."|".date("r").";"."\n";
        // Write the contents back to the file
        file_put_contents($file, $current);

return array(
                'id' => $id,
                'user_reference' => $user_reference,
                'status' => $status,
                'detail' => $detail
            );
}


// Estructuras del servicio
$server->wsdl->addComplexType(
    'request',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'user' => array('name' => 'user', 'type' => 'xsd:string'),
        'pass' => array('name' => 'pass', 'type' => 'xsd:string'),
        'user_reference' => array('name' => 'user_reference', 'type' => 'xsd:string'),
        'message' => array('name' => 'message', 'type' => 'xsd:string'),
        'destination' => array('name' => 'destination', 'type' => 'xsd:string'),
        'user_reference' => array('name' => 'user_reference', 'type' => 'xsd:string')
    )
);
$server->wsdl->addComplexType(
    'response',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'status' => array('name' => 'status', 'type' => 'xsd:string'),
        'error' => array('name' => 'error', 'type' => 'xsd:string'),
        'detail' => array('name' => 'detail', 'type' => 'xsd:string'),
        'user_reference' => array('name' => 'user_reference', 'type' => 'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'reqgetst',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'user' => array('name' => 'user', 'type' => 'xsd:string'),
        'pass' => array('name' => 'pass', 'type' => 'xsd:string'),
        'user_reference' => array('name' => 'user_reference', 'type' => 'xsd:string')

    )
);

$server->wsdl->addComplexType(
    'resgetst',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
    	'id' => array('name' => 'id', 'type' => 'xsd:string'),
        'user_reference' => array('name' => 'user_reference', 'type' => 'xsd:string'),
        'status' => array('name' => 'status', 'type' => 'xsd:string'),
        'detail' => array('name' => 'detail', 'type' => 'xsd:string')
    )
);


// registro de los métodos
$server->register('sendSMS',                   
    array('input' => 'tns:request'),          
    array('output' => 'tns:response'),  
    'urn:sms',                        
    'urn:sms#sendSMS',                  
    'rpc',                                   
    'encoded',                              
    'Send a SMS to web service'   
);

$server->register('getSTATUS',                   
    array('input' => 'tns:reqgetst'),          
    array('output' => 'tns:resgetst'),  
    'urn:sms',                        
    'urn:sms#getSTATUS',                  
    'rpc',                                   
    'encoded',                              
    'get Status to send SMS'   
);


$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>