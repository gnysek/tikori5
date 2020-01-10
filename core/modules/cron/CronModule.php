<?php

class CronModule extends TModule
{
    public function init()
    {
        Core::app()->observer
            ->addObserver(TikoriConsole::EVENT_STARTS_ALL_JOBS, [CronModule::class, 'startJobs'])
            ->addObserver(TikoriConsole::EVENT_START_JOB, [CronModule::class, 'startJob'])
            ->addObserver(TikoriConsole::EVENT_PASS_TO_JOB, [CronModule::class, 'passToJob'])
            ->addObserver(TikoriConsole::EVENT_END_JOB, [CronModule::class, 'endJob'])
            ->addObserver(TikoriConsole::EVENT_ERROR_JOB, [CronModule::class, 'errorJob']);
    }

    public static $_cronTasksModel = [];

    public static function startJobs($observer)
    {
        // finish all unfinished jobs
        Core::app()->db->query("UPDATE cron_result SET cron_task_status = 'failed' WHERE cron_task_status != 'failed' AND (cron_task_start IS NULL OR cron_task_finished IS NULL);");

        $tasksToExecute = $observer['tasks'];

        // add to database
        /** @var CronResult[] $cronTasksModel */
        self::$_cronTasksModel = [];
        foreach ($tasksToExecute as $cronTaskId => $cronTask) {
            $cronResult = CronResult::model();
            $cronResult->cron_task_name = $cronTask;
            $cronResult->cron_task_added = time();
            $cronResult->cron_task_status = TikoriConsole::STATUS_PLANNED;
            $cronResult->save(true);
            self::$_cronTasksModel[$cronTaskId] = $cronResult;
        }
    }

    public static function startJob($observer)
    {
        $cronTaskId = $observer['taskId'];

        self::$_cronTasksModel[$cronTaskId]->cron_task_status = TikoriConsole::STATUS_STARTED;
        self::$_cronTasksModel[$cronTaskId]->cron_task_start = time();
        self::$_cronTasksModel[$cronTaskId]->save(true);
    }

    public static function endJob($observer)
    {
        $cronTaskId = $observer['taskId'];
        $startTime = $observer['startTime'];

        self::$_cronTasksModel[$cronTaskId]->cron_task_status = TikoriConsole::STATUS_FINISHED;
        self::$_cronTasksModel[$cronTaskId]->cron_task_message = 'Done in ' . (Core::genTimeNow() - $startTime) . 's !';
        self::$_cronTasksModel[$cronTaskId]->cron_task_finished = time();
        self::$_cronTasksModel[$cronTaskId]->save(true);
    }

    public static function errorJob($observer)
    {
        $cronTaskId = $observer['taskId'];
        $status = $observer['status'];
        $message = $observer['message'];

        self::$_cronTasksModel[$cronTaskId]->cron_task_status = $status;
        self::$_cronTasksModel[$cronTaskId]->cron_task_message = 'Error: ' . $message;
        self::$_cronTasksModel[$cronTaskId]->save(true);
    }

    public static function passToJob($observer)
    {
        $cronTaskId = $observer['taskId'];
        $taskInstance = $observer['task'];

        /** @var TikoriCron $taskInstance */
        $taskInstance->statusModel = self::$_cronTasksModel[$cronTaskId];
    }
}