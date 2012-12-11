<?php

class TLog {

	const LEVEL_DEBUG = 1;
	const LEVEL_IMPORTANT = 0;

	private static $_log = array();

	public static function addLog($message, $level = 1) {
		$tn = Core::genTimeNow();
		self::$_log[] = array(
			$level,
			$message,
			sprintf('%0.4f', $tn),
			(count(self::$_log) > 0) ? sprintf('%0.4f', $tn - self::$_log[count(self::$_log) - 1][2]) : sprintf('%0.4f', $tn),
		);
	}

	public static function getLogs() {
		$logs = array();

		$totalTime = self::$_log[count(self::$_log) - 1][2];

		$logs[] = 'Logs:<br/>';
		$logs[] = '<table style="width: 98%; margin: 5px auto;">';
		$logs[] = '<tr>';
		$logs[] = '<th>ID</th>';
		$logs[] = '<th>Action</th>';
		$logs[] = '<th>Time</th>';
		$logs[] = '<th>Total</th>';
		$logs[] = '<th colspan="2">%</th>';
		$logs[] = '</tr>';
		
		$percentage = 0;
		
		foreach (self::$_log as $id => $log) {
			$tempPercentage = $percentage;
			$percentage = ($log[2] / $totalTime) * 100;
			
			$logs[] = '<tr>';
			$logs[] = '<td>' . $id . '</td>';
			$logs[] = '<td>' . $log[1] . '</td>';
			$logs[] = '<td>+' . $log[3] . 's.</td>';
			$logs[] = '<td>=' . $log[2] . 's.</td>';
			$logs[] = '<td>+' . sprintf('%0.2f', $percentage - $tempPercentage). '%</td>';
			$logs[] = '<td>=' . sprintf('%0.2f', $percentage) .'%</td>';
			$logs[] = '</tr>';
		}
		$logs[] = '</table>';

		return implode(PHP_EOL, $logs);
	}

}
