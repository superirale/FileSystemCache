<?php

class FileSystemCache
{

	private $cacheDir;
	public $errors;

	public function __construct($dir)
	{
		$this->cacheDir = $dir;
		$this->errors = [];
	}

	public function store($key, $value, $expiry_time)
	{
		try {

			$fileHandle = fopen($this->getFileName($key),'w');

			flock($fileHandle, LOCK_EX); // exclusive lock, will get released when the file is closed
		    fseek($fileHandle, 0); // go to the beginning of the file
		    // truncate the file
		    ftruncate($fileHandle, 0);

			$data = serialize(array(time()+$expiry_time, $value));

		    fwrite($fileHandle, $data);
		    fclose($fileHandle);

		} catch (Exception $e) {
			$this->errors = $e->getMessage();
		}
	}

	public function fetch($key)
	{
		$file = $this->getFileName($key);

		if(!file_exists($file) || !is_readable($file)){
			$this->errors[] = "Cannot read $file";
			return false;
		}

		$fileHandle = fopen($file,'r');

	    if(!$fileHandle) {
	    	$this->error[] = 'Can not read Cache';
	    	return false;
	    }

	    // Getting a shared lock
	    flock($fileHandle,LOCK_SH);

		$data = unserialize(file_get_contents($file));
		fclose($fileHandle);

		if(!$data){
			unlink($file);
			return false;
		}

		if(time() > $data[0]){
			unlink($file);
			$this->errors[] = 'Time Has elapsed';
			return false;
		}

		return $data[1];
	}

	public function delete($key)
	{
		$file = $this->getFileName($key);

		if(file_exists($file)){
			unlink($file);
			return true;
		}

		return false;
	}


	private function generateKey($key)
	{
		return md5($key);
	}

	private function getFileName($key)
	{
		return $this->cacheDir . $this->generateKey($key);
	}
}