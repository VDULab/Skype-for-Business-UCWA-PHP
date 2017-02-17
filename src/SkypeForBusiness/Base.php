<?php namespace SkypeForBusiness;

class Base 
{
	/*************************************************
	//	Variables
	*************************************************/
	
	/*
	 *	Server configuration
	*/
	protected static $ucwa_autodiscover = "http://lyncdiscover.example.com";
	protected static $ucwa_fqdn = "";
	protected static $ucwa_baseserver = "";
	protected static $ucwa_path_oauth = "";
	protected static $ucwa_path_user = "";
	protected static $ucwa_path_xframe = "";
	protected static $ucwa_path_application = "";
	protected static $ucwa_path_application_fq = "";
	protected static $ucwa_path_conversation = "";
	protected static $ucwa_path_events = "";
	
	/*
	 *	Storage
	*/
	protected static $ucwa_accesstoken = "";
	protected static $ucwa_operationid = "";
	protected static $ucwa_user = "";
	protected static $ucwa_pass = "";
	
	public function __construct(Array $config = []) 
	{	
		// override the static properties defining the class config 
		foreach ($config as $key => $setting) {
			if (property_exists($this, $key)) {
				$this::$$key = $setting;
			}
		}
		
	}



	/*************************************************
	//	Helper methods
	*************************************************/
	
	/*
	 *	(void) _error
	 *	######################################
	 *
	 *	Logging feature
	*/
	protected static function _error($text, $debug) {	
		$file = fopen('ucwa.log', 'a');
		fwrite($file, date("d-m-Y H:i:s") . ' | ' . $text . ' | ' . var_export($debug, true) . "\r\n");
		fclose($file);
	}
	
	/*
	 *	(string) _generateUUID
	 *	######################################
	 *
	 *	Generates an unique ID
	*/
	protected static function _generateUUID() {
		return  str_replace(".", "", uniqid(md5( time() ), true));	
	}	
	
	/*
	 *	(array) getUCWAData
	 *	######################################
	 *
	 *	Returns important information for
	 *	connecting UCWA_init with UCWA_use,
	 *	if they aren't running on the same
	 *	instance.
	*/
	public static function getUCWAData() {
		return array(
			"accesstoken" => self::$ucwa_accesstoken,
			"baseserver" => self::$ucwa_baseserver,
			"path_application" => self::$ucwa_path_application,
			"path_xframe" => self::$ucwa_path_xframe,
		);	
	}
}
