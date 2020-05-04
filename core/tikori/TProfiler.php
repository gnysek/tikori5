<?php

class TProfiler
{

    const LEVEL_DEBUG = 1;
    const LEVEL_IMPORTANT = 2;
    const LEVEL_LOG = 0;
    const LEVEL_SQL = 3;
    const LEVEL_AUTOLOAD = 4;

    private static $_log = array();
    private static $_notices = array();
    public static $enabledTimer = false;
    public static $skipLevelAbove = 99;

    /**
     * @param $message
     * @param int $level
     * @return float
     */
    public static function addLog($message, $level = 0)
    {
        $tn = Core::genTimeNow(4, false);
        if ($level < self::$skipLevelAbove) {
            self::$_log[] = array(
                $level,
                $message,
                $tn,
                (count(self::$_log) > 1) ? sprintf('%0.4f', $tn - self::$_log[count(self::$_log) - 1][2]) : 0,
                memory_get_usage(false),
            );
        }

        return $tn;
    }

    public static function addNotice($message)
    {
        self::$_notices[] = array(
            $message, Core::genTimeNow(4, false),
        );
    }

    public static function getNotices()
    {
        $logs = array();

        $logs[] = 'Notices:<br/>';
        $logs[] = '<style>.tikori-notices-table tr:hover td{background-color: #00bfa8; color: white; border-bottom: 1px solid red;}</style>';
        $logs[] = '<table style="width: 98%; margin: 5px auto; background: white; font-size: 11px; font-family: Arial, sans-serif;" class="tikori-notices-table">';
        $logs[] = '<tr style="border-bottom: 1px solid black; background: gray; color: white;">';
        $logs[] = '<th>ID</th>';
        $logs[] = '<th>Action</th>';
        $logs[] = '<th>Time</th>';
        $logs[] = '</tr>';

        foreach (self::$_notices as $i => $notice) {
            $logs[] = sprintf('<tr><td></td><td>%s</td><td>%s</td></tr>', $i + 1, $notice[0], $notice[1]);
        }

        $logs[] = '</table>';

        if (Core::app()->hasLoadedModule('toolbar')) {
            Core::app()->toolbar->putValueToTab('notices', implode(PHP_EOL, $logs));
            Core::app()->toolbar->setNotificationsNumberOnTab('notices', count(self::$_notices));
            return '';
        }

        return implode(PHP_EOL, $logs);
    }

    public static $getLogsGetAtLeastOnce = false;

    public static function getLogs($forceNotToolbar = false)
    {
        self::$getLogsGetAtLeastOnce = true;
        $logs = array();

        $logs[] = 'Profiler:<br/>';
        $logs[] = '<style>.tikori-profiler-table tr:hover td{background-color: #00bfa8; color: white; border-bottom: 1px solid red;}</style>';
        $logs[] = '<table style="width: 98%; margin: 5px auto; background: white; font-size: 11px; font-family: Arial, sans-serif;" class="tikori-profiler-table">';
        $logs[] = '<tr style="border-bottom: 1px solid black; background: gray; color: white;">';
        $logs[] = '<th>ID</th>';
        $logs[] = '<th>Type</th>';
        $logs[] = '<th>Action</th>';
        $logs[] = '<th colspan="2">Memory</th>';
        $logs[] = '<th>Time</th>';
        $logs[] = '<th>Total</th>';
        $logs[] = '<th colspan="2">%</th>';
        $logs[] = '</tr>';

        $styles = array(
            self::LEVEL_SQL       => ' style="background-color: darkcyan; color: white;"',
            self::LEVEL_DEBUG     => ' style="background-color: black; color: white;"',
            self::LEVEL_IMPORTANT => ' style="background-color: orange;"',
        );

        $types = array(
            self::LEVEL_SQL   => 'SQL',
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

                $memoryAddon = ($id == 0) ? 0 : round(($log[4] - self::$_log[$id - 1][4]) / 1024 / 1024, 4);

                $logs[] = '<tr style="border-bottom: 1px solid black;">';
                $logs[] = '<td>' . ($id + 1) . '</td>';
                $logs[] = '<td' . $style . '>' . $type . '</td>';
                $logs[] = '<td' . $style . '>' . $log[1] . '</td>';
                $logs[] = '<td>' . round($log[4] / 1024 / 1024, 4) . 'MB</td>';
                $logs[] = '<td' . ($memoryAddon > 1 ? ' style="background: red;"' : '') . '>&nbsp;+' . $memoryAddon . 'MB</td>';
                $logs[] = '<td>+' . $log[3] . 's.</td>';
                $logs[] = '<td>=' . $log[2] . 's.</td>';
                $logs[] = '<td' . self::percentageColor($currentPercentage) . '>+' . sprintf('%0.2f', $currentPercentage) . '%</td>';
                $logs[] = '<td>=' . sprintf('%0.2f', $percentage) . '%</td>';
                $logs[] = '</tr>';
            }
        }

        $requestHeaders = [];

        if (function_exists('apache_response_headers')) {
            $requestHeaders = ['Request headers' => apache_request_headers(), 'Reponse headers' => array_merge(apache_response_headers(), Core::app()->response->header())];
        } else {
            $rh = [];
            foreach ($_SERVER as $k => $v) {
                if (stripos($k, 'HTTP_') !== false) {
                    $rh[str_replace('HTTP_', '', $k)] = $v;
                }
            }

            $requestHeaders = ['Request headers' => $rh, 'Response headers' => Core::app()->response->header()];
        }

        foreach ($requestHeaders as $title => $headers) {
            $logs[] = '<tr style="border-bottom: 1px solid black;"><th colspan="7">' . $title . '</th></tr>';

            if (is_array($headers)) {
                foreach ($headers as $name => $header) {
                    if (empty($name)) {
                        continue;
                    }
                    $logs[] = '<tr style="border-bottom: 1px solid black;">';
                    $logs[] = '<td colspan="2"><strong>' . $name . '</strong></td>';
                    $logs[] = '<td colspan="5"><kbd>' . $header . '</kbd></td>';
                    $logs[] = '</tr>';
                }
            } else {

            }
        }

        $logs[] = '</table>';

        if ($forceNotToolbar === false and Core::app()->hasLoadedModule('toolbar')) {
            Core::app()->toolbar->putValueToTab('profiler', implode(PHP_EOL, $logs));
            Core::app()->toolbar->setNotificationsNumberOnTab('profiler', count(self::$_log));

            $classes = array();
            foreach (Core::$foundClasses as $class => $filename) {
                $classes[] = sprintf('<code style="display: inline-block; min-width: 200px;">%s</code> <code>%s</code>', $class, $filename);
            }
            Core::app()->toolbar->putValueToTab('loadedClasses', implode('<br>', $classes));
            Core::app()->toolbar->setNotificationsNumberOnTab('loadedClasses', count($classes));

            Core::app()->toolbar->addStatus(sprintf('Zużycie pamięci: <kbd>%s MB</kbd> (<kbd>~%s MB</kbd>)',
                    round(memory_get_peak_usage(false) / 1024 / 1024, 4),
                    round(memory_get_peak_usage(true) / 1024 / 1024, 4))
            );
            Core::app()->toolbar->addStatus(sprintf('PHP %s', preg_replace('/((?:\+|-).*)/', '', phpversion())));

            Core::app()->toolbar->addStatus(sprintf('Zapytań do bazy: <kbd>%s</kbd>.', Core::app()->db->queries()));
            Core::app()->toolbar->addStatus(sprintf('Czas generowania strony: <kbd>%ss</kbd>.', Core::genTimeNow()));

            return '';
        }

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

    public static $benchData = [];
    public static $benchCategories = [];
    protected static $_benchStack = [];

    const BENCH_CAT_SQL = 'Sql Query';
    const BENCH_CAT_SQL_FETCH = 'Sql fetching';
    const BENCH_CAT_CORE = 'Core';

    /**
     * @param $benchCategory
     * @param $benchDesc
     * @return int
     */
    public static function benchStart($benchCategory, $benchDesc)
    {
        $num = count(self::$benchData) - 1;

        if (!in_array($benchCategory, self::$benchCategories)) {
            self::$benchCategories[] = $benchCategory;
            self::$_benchStack[$benchCategory] = 1;
        }

        self::$benchData[$num] = [
            'c'   => $benchCategory,
            'd'   => $benchDesc,
            's'   => Core::genTimeNow(10, false),
            'f'   => Core::genTimeNow(10, false),
            'lvl' => min(10, self::$_benchStack[$benchCategory]),
        ];

        self::$_benchStack[$benchCategory]++;

        return $num;
    }

    /**
     * @param $id
     * @return string
     * @throws Exception
     */
    public static function benchFinish($id)
    {
        if (!array_key_exists($id, self::$benchData)) {
            throw new \Exception('Benchmark ' . $id . ' not found??');
        }

        self::$benchData[$id]['f'] = Core::genTimeNow(10, false);
        self::$_benchStack[self::$benchData[$id]['c']]--;

        return self::$benchData[$id]['f'] - self::$benchData[$id]['s'];
    }

    public static function getBenchForToolbar()
    {
        $html = '<table style="width: 100%;">';

        // prepare timers for stats
        $totalTime = Core::genTimeNow(4, false);

        $maxTime = 0;
        foreach (self::$benchData as $i => $data) {
            $maxTime = max($maxTime, $data['f'] - $data['s']);
        }
        if ($maxTime > 0) {
            $maxTime = max(0.001, $maxTime / 3);
        }

        // prepare multilevels
        $levels = [];
        foreach (self::$benchCategories as $category) {
            $levels[$category] = 1;
        }

        foreach (self::$benchData as $i => $data) {
            $levels[$data['c']] = min(10, max($data['lvl'], $levels[$data['c']]));
        }

        // prepare table
        $html .= '<tr>
                <td style="width: 10%">TOTAL</td>
                <td style = "width: 80%">
                    <div class="timeline">
                        <div class="timeline-entry" style="width: 100%"></div>
                    </div>
                </td>
                <td style = "width: 5%">= ' . $totalTime . 's</td>
                </tr>';

        $totalTimePerCategory = [];

        foreach (self::$benchCategories as $category) {

            $entries = [];
            $currRowTimeSum = 0;
            foreach (self::$benchData as $i => $data) {
                if ($data['c'] == $category) {
                    $ts = round(($data['s'] / $totalTime) * 100, 4);
                    $tf = round(($data['f'] / $totalTime) * 100, 4);
                    $tf -= $ts;
                    $tt = $data['f'] - $data['s'];

                    $bg = '';
                    if ($data['f'] - $data['s'] > $maxTime) {
                        $bg = ' timeline-entry-orange';
                        if ($data['f'] - $data['s'] > $maxTime * 2) {
                            $bg = ' timeline-entry-orange';
                        }
                    }

                    $currRowTimeSum += ($data['lvl'] == 1) ? ($data['f'] - $data['s']) : 0; // don't add those which are during another one

                    $entries[] = '
                    <div class="timeline-entry' . $bg . '" data-lvl="' . $data['lvl'] . '" style="left: ' . $ts . '%; width: ' . $tf . '%; top: ' . (($data['lvl'] - 1) * 20) . 'px;"></div>
                    <div class="timeline-tips" style="top:' . ($levels[$category] * 20 + 5) . 'px;">Time: ' . round($tt, 5) . 's (' . round($tt / $totalTime * 100, 4) . '%)<br>' . round($data['s'], 5) . 's - ' . round($data['f'], 5) . 's<br>' . $data['d'] . '</div>';
                }
            }

            $html .= '<tr>
                <td style="width: 10%">' . $category . '</td>
                <td style = "width: 80%">
                    <div class="timeline" style="height: ' . ($levels[$category] * 20) . 'px;">
                    ' . implode('', $entries) . '
                    </div>
                </td>
                <td style = "width: 5%">= ' . round($currRowTimeSum, 5) . 's</td>
                </tr>';

            $totalTimePerCategory[$category] = $currRowTimeSum;
        }

        $html .= '<tr><td colspan="3" class="text-center">SUM</td></tr>';

        foreach ($totalTimePerCategory as $category => $time) {
            $html .= '<tr>
                <td style="width: 10%">' . $category . '</td>
                <td style = "width: 80%">
                    <div class="timeline">
                        <div class="timeline-entry" style="width: ' . round(($time / $totalTime * 100), 5) . '%"></div>
                    </div>
                </td>
                <td style = "width: 5%">= ' . round($time, 5) . 's</td>
                </tr>';
        }

        $html .= '<tr><td colspan="3" class="text-center">Timeline generation time: ' . (Core::genTimeNow() - $totalTime) . 's</td></tr>';

        $html .= '</table> ';

        return $html;
    }

}
