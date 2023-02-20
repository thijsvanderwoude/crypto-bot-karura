<?php

require "config.php";

class Logger {
	protected $logToFile;
	protected $logFile;
	
	function __construct($logToFile) {
		$this->logToFile = $logToFile;
		
		if ($logToFile) {
			if (!file_exists('log/')) {
				mkdir('log/', 0777, true);
			}

			$dateTime = date("d.m.Y H.i.s");
			$logFileName = urlencode($dateTime . ".txt");
			$this->logFile = fopen("log/" . $logFileName, "w");
			if (!$this->logFile) {
				$this->rec("can not open log file: " . $logFileName . NEWLN . "Exiting.", 1);
				die();
			} else {
				$this->rec("Opened log file: " . $logFileName, 0);
			}
		} else {
			$this->rec("Config says do not log to file.", 0);
		}
	}
	
	function __destruct() {
		if ($this->logToFile) {
			fclose($this->logFile);
		}
	}
	
	/*
	 * rec(String $message, Int $level)
	 * 
	 * Records a message to the log.
	 * 
	 * $message = message
	 * $level = 0 (normal), 1 (error)
	 * $type = 0 (nothing), 1 (API)
	 *
	 * Returns: nothing
	 */
	public function rec(string $message, int $level = 0, int $type = 0) {
		$dateTime = date("[d/m/Y H:i:s] ");

		$messageFormatted = "";

		switch ($level) {
			case 1:
				$messageFormatted .= "ERROR: ";
				break;
		}
		
		switch ($type) {
			case 1:
				$messageFormatted .= "API: ";
				break;
		}

		$messageFormatted .= $message;

		echo $dateTime . $messageFormatted . NEWLN;
		if ($this->logToFile) {
			fwrite($this->logFile, $dateTime . $messageFormatted . NEWLN);
		}
	}
}

?>
