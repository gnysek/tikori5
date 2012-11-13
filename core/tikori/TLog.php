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
			(count(self::$_log) > 0) ? sprintf('%0.4f', $tn - self::$_log[count(self::$_log) - 1][2]) : sprintf('%0.4f', 0),
		);
	}

	public static function getLogs() {
		$logs = array();

		$logs[] = 'Logs:<br/>';
		$logs[] = '<table>';
		$logs[] = '<tr>';
		$logs[] = '<th>ID</th>';
		$logs[] = '<th>Action</th>';
		$logs[] = '<th>Time</th>';
		$logs[] = '<th>Total</th>';
		$logs[] = '</tr>';
		foreach (self::$_log as $id => $log) {
			$logs[] = '<tr>';
			$logs[] = '<td>' . $id . '</td>';
			$logs[] = '<td>' . $log[1] . '</td>';
			$logs[] = '<td>+' . $log[3] . 's.</td>';
			$logs[] = '<td>' . $log[2] . 's.</td>';
			$logs[] = '</tr>';
		}
		$logs[] = '</table>';

		return implode(PHP_EOL, $logs);
	}

}
