<?php

namespace App\Controllers\Admin;

use App\Constants\ActivityTypes;
use App\Controllers\BaseController;
use App\Models\BackupsModel;

/**
 * Handles the admin "Backups" domain (DB backup generation + downloads).
 *
 * Extracted from the former God Class AdminController (Extract Class, smell #1).
 * Methods were moved verbatim; URLs are unchanged (see app/Config/Routes.php).
 */
class BackupsController extends BaseController
{
    protected $session;
    protected $db;

    public function __construct()
    {
        $this->session = session();
        $this->db = \Config\Database::connect();
    }

    public function backups()
    {
        $tableName = 'backups';
        $backupsModel = new BackupsModel();

        // Set data to pass in view
        $data = [
            'backups' => $backupsModel->orderBy('created_at', 'DESC')->findAll(),
            'total_backups' => getTotalRecords($tableName)
        ];

        return view('back-end/admin/backups/index', $data);
    }

    public function generateDbBackup()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        // Load the BackupsModel
        $backupsModel = new BackupsModel();

        try {
            // Get database configuration
            $hostname = env('database.default.hostname', 'localhost');
            $databaseName = env('database.default.database', 'igniter_cms_db');

            // Generate file name with date and time
            $fileName = 'backup_' . date('Y-m-d_H-i-s') .'-'. rand(). '.sql';
            $filePath = WRITEPATH . 'backups/' . $fileName; // Save path in writable directory


            // Start output buffering
            ob_start();

            // Add SQL header comments
            echo "-- Database Backup\n";
            echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            echo "-- Server: " . $hostname . "\n";
            echo "-- Database: " . $databaseName . "\n\n";

            // Get all tables
            $tables = $this->db->listTables();

            foreach ($tables as $table) {
                // Get create table syntax
                $query = $this->db->query("SHOW CREATE TABLE " . $this->db->escapeIdentifiers($table));
                $row = $query->getRow();

                if ($row) {
                    $createTableField = "Create Table";
                    echo "\n\n-- Table structure for table `" . $table . "`\n\n";
                    echo "DROP TABLE IF EXISTS `" . $table . "`;\n";
                    echo $row->$createTableField . ";\n\n";

                    // Get table data
                    $query = $this->db->query("SELECT * FROM " . $this->db->escapeIdentifiers($table));

                    if ($query->getNumRows() > 0) {
                        echo "-- Dumping data for table `" . $table . "`\n";

                        foreach ($query->getResultArray() as $row) {
                            $fields = array_map(function($value) {
                                if ($value === null) {
                                    return 'NULL';
                                }
                                return $this->db->escape($value);
                            }, $row);

                            echo "INSERT INTO `" . $table . "` VALUES (" . implode(', ', $fields) . ");\n";
                        }
                    }
                }
            }

            $backup = ob_get_clean();

            // Save the backup content to a file
            if (!is_dir(WRITEPATH . 'backups')) {
                mkdir(WRITEPATH . 'backups', 0777, true); // Create directory if not exists
            }
            file_put_contents($filePath, $backup);

            $actionUrl = $this->request->getUri()->getPath();
            $previousData = null;
            // Prepare data for insertion
            $data = [
                'backup_file_path' => $fileName,
                'created_by' => $loggedInUserId
            ];

            if ($backupsModel->createBackup($data)) {
                $insertedId = $backupsModel->getInsertID();

                // Record created successfully. Redirect to view
                $createSuccessMsg = str_replace('[Record]', 'Database Backup', lang('App.create_success_msg'));
                session()->setFlashdata('successAlert', $createSuccessMsg);

                //log activity
                logActivity($loggedInUserId, ActivityTypes::BACKUP_CREATION, 'Backup created with id: ' . $insertedId, $actionUrl, get_class($backupsModel), $insertedId, json_encode($previousData), json_encode($data));

                return redirect()->to('/account/admin/backups');
            } else {
                // Failed to create record. Redirect to view
                $errorMsg = lang('App.error_msg');
                session()->setFlashdata('errorAlert', $errorMsg);

                //log activity
                logActivity($loggedInUserId, ActivityTypes::FAILED_BACKUP_CREATION, 'Failed to create backup.', $actionUrl, get_class($backupsModel), null, json_encode($previousData), json_encode($data));

                return view('back-end/admin/backups');
            }

        } catch (\Exception $e) {
            // Set flash message and redirect
            $errorMsg = lang('App.error_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            return redirect()->to('/account/admin/backups');
        }
    }

    public function downloadDbBackup($fileName)
    {
        // Path to the backup file in the writable directory
        $filePath = WRITEPATH . 'backups/' . $fileName;

        // Check if the file exists
        if (file_exists($filePath)) {
            // Use CodeIgniter's response to download the file
            return $this->response->download($filePath, null)->setFileName($fileName);
        } else {
            // File not found, set an error message
            session()->setFlashdata('errorAlert', 'Backup file not found.');
            return redirect()->to('/account/admin/backups');
        }
    }

    public function downloadPublicFolderBackup()
    {
        // Define the path to the public folder
        $publicFolderPath = FCPATH . 'public'; // FCPATH points to the root directory

        // Generate a unique name for the zip file
        $zipFileName = 'public_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zipFilePath = WRITEPATH . 'backups/' . $zipFileName;

        // Ensure the backups directory exists
        if (!is_dir(WRITEPATH . 'backups')) {
            mkdir(WRITEPATH . 'backups', 0777, true);
        }

        // Initialize the ZipArchive class
        $zip = new \ZipArchive();

        // Attempt to create the zip file
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            // Add the public folder contents to the zip file
            $this->addFolderToZip($publicFolderPath, $zip);

            // Close the zip file
            $zip->close();

            // Check if the zip file was created successfully
            if (file_exists($zipFilePath)) {
                // Use CodeIgniter's response to download the file
                return $this->response->download($zipFilePath, null)->setFileName($zipFileName);
            } else {
                // Handle error if the zip file could not be created
                session()->setFlashdata('errorAlert', 'Failed to create the public folder backup.');
                return redirect()->to('/account/admin/backups');
            }
        } else {
            // Handle error if the zip file could not be opened
            session()->setFlashdata('errorAlert', 'Failed to open the zip archive.');
            return redirect()->to('/account/admin/backups');
        }
    }

    /**
     * Helper function to recursively add folder contents to a zip archive.
     *
     * @param string $folderPath Path to the folder being added.
     * @param ZipArchive $zip ZipArchive instance.
     * @param string $parentFolder Parent folder path (used for recursion).
     */
    private function addFolderToZip($folderPath, $zip, $parentFolder = '')
    {
        // Open the folder
        $files = new \DirectoryIterator($folderPath);

        foreach ($files as $file) {
            // Skip "." and ".." directories
            if ($file->isDot()) {
                continue;
            }

            // Construct the full path and relative path
            $filePath = $file->getPathname();
            $relativePath = $parentFolder ? $parentFolder . '/' . $file->getFilename() : $file->getFilename();

            if ($file->isDir()) {
                // If it's a directory, add it to the zip and recurse into it
                $zip->addEmptyDir($relativePath);
                $this->addFolderToZip($filePath, $zip, $relativePath);
            } else {
                // If it's a file, add it to the zip
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
