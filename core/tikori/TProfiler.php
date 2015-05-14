<?php

class TProfiler
{

    const LEVEL_DEBUG = 1;
    const LEVEL_IMPORTANT = 2;
    const LEVEL_LOG = 0;
    const LEVEL_SQL = 3;
    const LEVEL_AUTOLOAD = 4;

    private static $_log = array();
    public static $enabledTimer = false;
    public static $skipLevelAbove = 99;

    public static function addLog($message, $level = 0)
    {
        $tn = Core::genTimeNow();
        if ($level < self::$skipLevelAbove) {
            self::$_log[] = array(
                $level,
                $message,
                sprintf('%0.4f', $tn),
                (count(self::$_log) > 0)
                    ? sprintf('%0.4f', $tn - self::$_log[count(self::$_log) - 1][2])
                    : sprintf(
                    '%0.4f', $tn
                ),
            );
        }
    }

    public static function getLogs()
    {
        $logs = array();

        $logs[] = 'Profiler:<br/>';
        $logs[] = '<table style="width: 98%; margin: 5px auto; background: white; font-size: 11px; font-family: Arial, sans-serif;" class="tikori-profiler-table">';
        $logs[] = '<tr style="border-bottom: 1px solid black; background: gray; color: white;">';
        $logs[] = '<th>ID</th>';
        $logs[] = '<th>Type</th>';
        $logs[] = '<th>Action</th>';
        $logs[] = '<th>Time</th>';
        $logs[] = '<th>Total</th>';
        $logs[] = '<th colspan="2">%</th>';
        $logs[] = '</tr>';

        $styles = array(
            self::LEVEL_SQL => ' style="background-color: darkcyan; color: white;"',
            self::LEVEL_DEBUG => ' style="background-color: black; color: white;"',
            self::LEVEL_IMPORTANT => ' style="background-color: orange;"',
        );

        $types = array(
            self::LEVEL_SQL => 'SQL',
            self::LEVEL_DEBUG => 'DBG',
        );

        if (count(self::$_log)) {
            $totalTime = self::$_log[count(self::$_log) - 1][2];
            $percentage = 0;

            foreach (self::$_log as $id => $log) {
                $tempPercentage = $percentage;
                $percentage = ($log[2] / $totalTime) * 100;
                $currentPercentage = $percentage - $tempPercentage;

                $style = ($log[0] > 0 and array_key_exists($log[0], $styles)) ? $styles[$log[0]] : '';
                $type = ($log[0] > 0 and array_key_exists($log[0], $types)) ? $types[$log[0]] : '&ndash;';

                $logs[] = '<tr style="border-bottom: 1px solid black;">';
                $logs[] = '<td>' . $id . '</td>';
                $logs[] = '<td' . $style . '>' . $type . '</td>';
                $logs[] = '<td' . $style . '>' . $log[1] . '</td>';
                $logs[] = '<td>+' . $log[3] . 's.</td>';
                $logs[] = '<td>=' . $log[2] . 's.</td>';
                $logs[]
                    = '<td' . self::percentageColor($currentPercentage) . '>+' . sprintf('%0.2f', $currentPercentage)
                    . '%</td>';
                $logs[] = '<td>=' . sprintf('%0.2f', $percentage) . '%</td>';
                $logs[] = '</tr>';
            }
        }
        $logs[] = '</table>';

        return implode(PHP_EOL, $logs);
    }

    public static function percentageColor($percentage)
    {
        $style = ' style="background-color: %s"';

        if ($percentage < 1) {
            return '';
        }
        if ($percentage < 5) {
            return sprintf($style, 'orange');
        }
        if ($percentage < 10) {
            return sprintf($style, 'darkorange');
        }
        if ($percentage >= 10) {
            return sprintf($style, 'red');
        }


    }

}
