<?php

pm_Loader::registerAutoload();
pm_Context::init('inwx');

try {
	$result = pm_ApiCli::call('server_dns', ['--disable-custom-backend']);
} catch (pm_Exception $e) {
	echo $e->getMessage() . "\n";
	exit(1);
}
exit(0);
