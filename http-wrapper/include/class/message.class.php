<?php
class Message {
	public static function Clear()
	{
		unset($_SESSION['Message']);
	}
	public static function IsSet()
	{
		return !empty($_SESSION['Message']);
	}
	public static function AddMessage($text, $type = 'Success', $id = null)
	{
		if(!isset($_SESSION['Message'])) {
			$_SESSION['Message'] = array();
		}
		if(!isset($_SESSION['Message'][$type])) {
			$_SESSION['Message'][$type] = array();
		}
		if($id === null /* || !isset($_SESSION['Message'][$type][$id]) */)
		{
			if(!in_array($text, $_SESSION['Message'][$type])) {
				$_SESSION['Message'][$type][] = $text;
			}
			return count($_SESSION['Message'][$type]) - 1;
		}
		else
		{
			if(!isset($_SESSION['Message'][$type][$id])) {
				$_SESSION['Message'][$type][$id] = '';
			}
			$_SESSION['Message'][$type][$id] .= "<br />" . $text;
			return $id;
		}
	}
	public static function AddFromApi($output, $id = null)
	{
		if(isset($output['ok']))
			return self::AddSuccess($output['ok'], $id);
		if(isset($output['error']))
			return self::AddError($output['error'], $id);
	}
	public static function AddSuccess($text, $id = null)
	{
		return self::AddMessage($text, substr(__FUNCTION__, 3), $id);
	}
	public static function AddInfo($text, $id = null)
	{
		return self::AddMessage($text, substr(__FUNCTION__, 3), $id);
	}
	public static function AddWarning($text, $id = null)
	{
		return self::AddMessage($text, substr(__FUNCTION__, 3), $id);
	}
	public static function AddError($text, $id = null)
	{
		return self::AddMessage($text, substr(__FUNCTION__, 3), $id);
	}
}
?>
