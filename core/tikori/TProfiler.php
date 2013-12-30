<?php

class TProfiler
{

    const LEVEL_DEBUG = 1;
    const LEVEL_IMPORTANT = 0;
    const LEVEL_LOG = 2;

    private static $_log = array();
    public static $enabledTimer = false;
    public static $skipLevelAbove = 99;

    public static function addLog($message, $level = 2)
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
        $logs[] = '<table style="width: 98%; margin: 5px auto;" class="tikori-profiler-table">';
        $logs[] = '<tr>';
        $logs[] = '<th>ID</th>';
        $logs[] = '<th>Time</th>';
        $logs[] = '<th>Action</th>';
        $logs[] = '<th>Total</th>';
        $logs[] = '<th colspan="2">%</th>';
        $logs[] = '</tr>';

        if (count(self::$_log)) {
            $totalTime = self::$_log[count(self::$_log) - 1][2];
            $percentage = 0;

            foreach (self::$_log as $id => $log) {
                $tempPercentage = $percentage;
                $percentage = ($log[2] / $totalTime) * 100;
                $currentPercentage = $percentage - $tempPercentage;

                $logs[] = '<tr>';
                $logs[] = '<td>' . $id . '</td>';
                $logs[] = '<td>+' . $log[3] . 's.</td>';
                $logs[] = '<td>' . $log[1] . '</td>';
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
