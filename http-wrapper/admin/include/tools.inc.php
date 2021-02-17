<?php
require_once realpath(dirname(__FILE__).'/../../').'/include/tools.inc.php';

function generateGiftCode()
{
	$codes = array();
	for($i=0; $i<4; $i++)
	{
		$codes[] = strtoupper(substr(base_convert(rand() % 9999999999, 10, 36), 0, 5));
	}
	$code = implode("-", $codes);
	$link = getSQL();

	$sql = 'SELECT count(code) as cnt FROM gift WHERE code="'.addslashes($code).'";';
	$res = mysqli_query($link, $sql);
	$num = mysqli_fetch_assoc($res);
	mysqli_close($link);
	if(!empty($num['cnt']))
		$code = generate();
	return $code;
}

?>
