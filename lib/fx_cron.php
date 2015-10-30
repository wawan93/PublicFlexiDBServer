<?php

class FX_Cron {

	private $connection;
	private $path;
	private $handle;
	private $cron_file;
	private $cron_option;
	var $error = false;

	function __construct($host=NULL, $username=NULL, $password=NULL)
	{
		$this->cron_option = 'crontab'.($username ? '_'.$username : '');
		$this->cron_file = CONF_UPLOADS_DIR."/temp/".($this->cron_option).'.txt';
				
		try {
			if (is_null($host) || is_null($username) || is_null($password)) {
				throw new Exception(_("The host, username and password arguments must be specified"));
			}

			if (!$this->connection = new Net_SSH2($host)) {
				throw new Exception(_("The SSH2 connection could not be established"));
			}

			if (!$authentication = $this->connection->login($username, $password)) {
				throw new Exception(_("Unable to authenticate using local FTP credentials"));
			}
		}
		catch (Exception $e) {
			add_log_message('cron_construct',$e->getMessage());
			$this->error = $e->getMessage();
		}
	}

	public function exec()
	{
		$argument_count = func_num_args();

		try {
			if (!$argument_count) {
				throw new Exception(_("There is nothing to exececute, no arguments specified"));
			}

			$arguments = func_get_args();
			
			for ($i=0; $i<$argument_count; $i++){
				$stream = $this->connection->exec($arguments[$i]);
			}
		}
		catch (Exception $e){
			add_log_message('cron_exec',$e->getMessage());
		}

		return $this;
	}

	public function append_cronjob($cron_jobs=NULL)
	{
		$cron_array = get_fx_option($this->cron_option);

		if($cron_array || !is_array($cron_array)) {
			$cron_array = array();
		}

		if (is_array($cron_jobs)) {
			$cron_array = array_merge($cron_array,$cron_jobs);
		}
		else {
			$cron_array[] = $cron_jobs;
		}

		file_put_contents($this->cron_file,implode("\n", $cron_array)."\n");
		$install_cron = "crontab ".$this->cron_file;
		$this->exec($install_cron);
		update_fx_option($this->cron_option,$cron_array);
		
		return $this;		
	}
	
	public function remove_cronjob($cron_jobs=NULL)
	{
		if (is_null($cron_jobs)) {
			return $this;
		}
	
		$cron_array = get_fx_option($this->cron_option);
		$original_count = count($cron_array);
		
		if (is_array($cron_jobs)) {
			foreach ($cron_jobs as $pattern) {
				for($i=0; $i<$original_count;$i++) {
					if(!$cron_array[$i] || substr_count($cron_array[$i], $pattern)) unset($cron_array[$i]);
				}
			}
		}
		else {
			for($i=0; $i<$original_count;$i++) {
				if(!$cron_array[$i] || substr_count($cron_array[$i], $cron_jobs)) unset($cron_array[$i]);
			}
		}

		if($original_count && $original_count === count($cron_array)) {
			return $this;
		}
		else {
			update_fx_option($this->cron_option,$cron_array);
			return $this->remove_crontab()->append_cronjob($cron_array);
		}
	}

	public function remove_crontab()
	{
		$this->exec("crontab -r");		
		return $this;
	}
}