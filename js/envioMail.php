<?php
echo "¡hola";
    $nombre = $_POST['nombre'];
    $correo= $_POST['email'];
    $asunto= $_POST['asunto'];
    $mensaje= $_POST['mensaje'];

    $contenido = "Detalles del formulario de contacto:\n\n";
    $contenido .= "Nombre: " . $_POST['nombre'] . "\n";    
    $contenido .= "E-mail: " . $_POST['email'] . "\n";    
    $contenido .= "Mensaje: " . $_POST['mensaje'] . "\n\n";


    if(mail('tengounaidea@octagono.com.co',$asunto,$contenido)){
        
        header('Location: ../index.html#hero');
    }else{ 
         
        header('Location: ../index.html#contact');
         }

?>
