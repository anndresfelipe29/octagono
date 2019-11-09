<?php
	error_reporting(E_ALL); @ini_set('display_errors', true);
	@session_start();
	$pages = array(
		'0'	=> array('id' => '1', 'alias' => 'Inicio', 'file' => '1.php','controllers' => array()),
		'1'	=> array('id' => '2', 'alias' => 'Fotógrafos', 'file' => '2.php','controllers' => array()),
		'2'	=> array('id' => '3', 'alias' => 'Inspiración', 'file' => '3.php','controllers' => array()),
		'3'	=> array('id' => '4', 'alias' => 'Compras', 'file' => '4.php','controllers' => array('store'))
	);
	$forms = array(

	);
	$langs = null;
	$def_lang = null;
	$base_lang = 'es';
	$site_id = "76f0373f";
	$base_dir = dirname(__FILE__);
	$base_url = '/';
	$user_domain = 'octagono.com.co';
	$show_comments = false;
	include dirname(__FILE__).'/functions.inc.php';
	require dirname(__FILE__).'/src/store/StoreNavigation.php';
	require dirname(__FILE__).'/src/store/StoreData.php';
	require dirname(__FILE__).'/src/store/StoreModuleBuyer.php';
	require dirname(__FILE__).'/src/store/StoreModuleOrder.php';
	require dirname(__FILE__).'/src/store/StoreModule.php';
	require dirname(__FILE__).'/src/store/StoreBaseElement.php';
	require dirname(__FILE__).'/src/store/StoreElement.php';
	require dirname(__FILE__).'/src/store/StoreCartElement.php';
	StoreModule::init((object) array(
		'defaultStorePageId' => 4,
		'hasTableView' => true,
		'hasPrices' => true
	));
	$home_page = '1';
	list($page_id, $lang, $urlArgs, $route) = parse_uri();
	$user_key = "RjqgyqRqIGXroJZMmjmF";
	$user_hash = "845a8b9fcf38f0be";
	$comment_callback = "https://servidor2.constructorsitiosweb.com:443/es-ES/comment_callback/";
	$preview = false;
	$mod_rewrite = true;
	$store_module_translations = unserialize('a:1:{s:1:"-";a:2:{s:26:"Payment has been submitted";s:23:"El pago ha sido enviado";s:25:"Payment has been canceled";s:25:"El pago ha sido cancelado";}}');
	$page = isset($pages[$page_id]) ? $pages[$page_id] : null;
	if ($page && $page['id'] == $home_page && $route) {
		header('Location: '.$base_url.(($lang && $lang != $def_lang) ? (($mod_rewrite ? '' : '?route=').$lang.'/') : ''), true, 301);
	}
	$hr_out = '';
	if (is_callable('StoreModule::parseRequest')) $hr_out .= call_user_func('StoreModule::parseRequest', $page, $lang, $urlArgs);
	if (!is_null($page)) {
		handleComments($page['id']);
		if (isset($_POST["wb_form_id"])) handleForms($page['id']);
	}
	ob_start();
	if (isset($_REQUEST['view']) && $_REQUEST['view'] == 'news')
		include dirname(__FILE__).'/news.php';
	else if (isset($_REQUEST['view']) && $_REQUEST['view'] == 'blog')
		include dirname(__FILE__).'/blog.php';
	else if ($page) {
		$fl = dirname(__FILE__).'/'.$page['file'];
		if (is_file($fl)) {
			ob_start();
			include $fl;
			$out = ob_get_clean();
			$ga_out = '';
			if ($lang && $langs) {
				foreach ($langs as $ln => $default) {
					$pageUri = getPageUri($page['id'], $ln);
					$out = str_replace(urlencode('{{lang_'.$ln.'}}'), $pageUri, $out);
				}
			}
			if (is_file($ga_file = dirname(__FILE__).'/ga_code') && $ga_code = file_get_contents($ga_file)) {
				$ga_out = str_replace('{{ga_code}}', $ga_code, file_get_contents(dirname(__FILE__).'/ga.html'));
			}
			$out = str_replace('{{ga_code}}', $ga_out, $out);
			$baseUrl = (isHttps() ? 'https' : 'http').'://'.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost').'/';
			$out = str_replace('{{base_url}}', $baseUrl, $out);
			$out = str_replace('{{curr_url}}', $baseUrl.($lang && $lang != $def_lang ? $lang.'/' : '').$route, $out);
			$out = str_replace('{{hr_out}}', $hr_out, $out);
			header('Content-type: text/html; charset=utf-8', true);
			echo $out;
		}
	} else {
		header("Content-type: text/html; charset=utf-8", true, 404);
		if (is_file(dirname(__FILE__).'/404.html')) {
			include '404.html';
		} else {
			echo "<!DOCTYPE html>\n";
			echo "<html>\n";
			echo "<head>\n";
			echo "<title>404 Not found</title>\n";
			echo "</head>\n";
			echo "<body>\n";
			echo "404 Not found\n";
			echo "</body>\n";
			echo "</html>";
		}
	}
	ob_end_flush();

?>