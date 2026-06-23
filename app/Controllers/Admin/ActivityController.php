<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ActivityLogsModel;
use App\Models\SiteStatsModel;

/**
 * Handles the admin "Activity Logs", "Log Files" and "Site Stats" domains.
 *
 * Extracted from the former God Class AdminController (Extract Class, smell #1).
 * Methods were moved verbatim; URLs are unchanged (see app/Config/Routes.php).
 */
class ActivityController extends BaseController
{
    //############################//
    //       Activity Logs        //
    //############################//
    public function activityLogs()
    {
        $tableName = 'activity_logs';
        $activityLogsModel = new ActivityLogsModel();

        // Set data to pass in view
        $data = [
            'activity_logs' => $activityLogsModel->orderBy('created_at', 'DESC')->paginate(intval(env('QUERY_LIMIT_MAX', 1000))),
            'pager' => $activityLogsModel->pager,
            'total_activities' => $activityLogsModel->pager->getTotal()
        ];

        return view('back-end/admin/activity-logs/index', $data);
    }

    public function viewActivity($activityId)
    {
        $activityLogsModel = new ActivityLogsModel();

        // Fetch the data based on the id
        $activity = $activityLogsModel->where('activity_id', $activityId)->first();

        if (!$activity) {
            $errorMsg = lang('App.not_found_msg');
            session()->setFlashdata('errorAlert', $errorMsg);
            return redirect()->to('/account/admin/activity-logs');
        }

        // Set data to pass in view
        $data = [
            'activity_data' => $activity
        ];

        return view('back-end/admin/activity-logs/view-activity', $data);
    }

    //############################//
    //            Logs            //
    //############################//
    public function viewLogFiles()
    {
        // Path to the logs directory
        $logPath = WRITEPATH . 'logs/';

        // Get all log files
        $logFiles = glob($logPath . 'log-*.log');

        // Array to hold log data
        $logData = [];

        // Read each log file
        foreach ($logFiles as $file) {
            // Read the file content
            $fileContent = file_get_contents($file);

            // Split the content into individual log entries
            $logEntries = explode("\n", $fileContent);

            // Filter out empty entries
            $logEntries = array_filter($logEntries, function($entry) {
                return !empty(trim($entry));
            });

            // Parse and add the log entries to the log data array
            foreach ($logEntries as $entry) {
                if (preg_match('/^(.*?) - (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) --> (.*)$/', $entry, $matches)) {
                    $level = $matches[1];      // Log level (e.g., INFO, ERROR, CRITICAL)
                    $timestamp = $matches[2];  // Timestamp (e.g., 2025-02-10 16:36:40)
                    $message = $matches[3];    // Log message

                    // Add the parsed data to the log data array
                    $logData[] = [
                        'file' => basename($file),
                        'level' => $level,
                        'timestamp' => $timestamp,
                        'message' => $message
                    ];
                }
            }
        }

        // Sort log data by timestamp in descending order (most recent first)
        usort($logData, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Paginate the log data
        $pager = \Config\Services::pager();
        $perPage = 100; // Number of entries per page
        $currentPage = $this->request->getPost('page') ?? 1; // Get current page from query string

        // Slice the log data for the current page
        $totalEntries = count($logData);
        $paginatedData = array_slice($logData, ($currentPage - 1) * $perPage, $perPage);

        // Pass the paginated data and pager to the view
        $data['total_logs'] = $totalEntries;
        $data['logData'] = $paginatedData;
        $data['pager'] = $pager->makeLinks($currentPage, $perPage, $totalEntries, 'bootstrap'); // Use custom template

        return view('back-end/admin/logs/index', $data);
    }

    public function viewLogData($filename)
    {
        // Path to the logs directory
        $logPath = WRITEPATH . 'logs/';

        // Full path to the log file
        $logFile = $logPath . $filename;

        // Check if the file exists
        if (!file_exists($logFile)) {
            // If the file doesn't exist, show an error or redirect
            return redirect()->to('/account/admin/logs')->with('error', 'Log file not found.');
        }

        // Read the file content
        $logContent = file_get_contents($logFile);

        // Split the log content into individual entries
        $logEntries = explode("\n", $logContent);

        // Filter out empty entries
        $logEntries = array_filter($logEntries, function($entry) {
            return !empty(trim($entry));
        });

        // Pass the log data to the view
        $data['logEntries'] = $logEntries;
        $data['filename'] = $filename;

        return view('back-end/admin/logs/view-log', $data);
    }

    //############################//
    //        Site Stats          //
    //############################//
    public function viewStats()
    {
        $tableName = 'site_stats';
        $visitStatsModel = new SiteStatsModel();

        // Set data to pass in view
        $data = [
            'visit_stats' => $visitStatsModel->orderBy('created_at', 'DESC')->paginate(intval(env('QUERY_LIMIT_MAX', 1000))),
            'pager' => $visitStatsModel->pager,
            'total_stats' => $visitStatsModel->pager->getTotal()
        ];

        return view('back-end/admin/visit-stats/index', $data);
    }

    public function viewStat($visitId)
    {
        $visitStatsModel = new SiteStatsModel();

        // Fetch the data based on the id
        $visit = $visitStatsModel->where('site_stat_id', $visitId)->first();

        if (!$visit) {
            $errorMsg = lang('App.not_found_msg');
            session()->setFlashdata('errorAlert', $errorMsg);
            return redirect()->to('/account/admin/visit-stats');
        }

        // Set data to pass in view
        $data = [
            'visit_data' => $visit
        ];

        return view('back-end/admin/visit-stats/view-stat', $data);
    }
}
