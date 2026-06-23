<?php

namespace App\Controllers\Admin;

use App\Constants\ActivityTypes;
use App\Controllers\BaseController;
use App\Models\APIAccessModel;

/**
 * Handles the admin "API Keys" domain (list / create / edit).
 *
 * Extracted from the former God Class AdminController (Extract Class, smell #1).
 * Methods were moved verbatim; URLs are unchanged (see app/Config/Routes.php).
 */
class ApiKeysController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    public function apiKeys()
    {
        $tableName = 'api_accesses';
        $apiKeysModel = new APIAccessModel();

        // Set data to pass in view
        $data = [
            'api_keys' => $apiKeysModel->orderBy('created_at', 'DESC')->findAll(),
            'total_api_keys' => getTotalRecords($tableName)
        ];

        return view('back-end/admin/api-keys/index', $data);
    }

    public function newApiKey()
    {
        return view('back-end/admin/api-keys/new-api-key');
    }

    public function addApiKey()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        // Load the APIAccessModel
        $apiKeysModel = new APIAccessModel();

        // Validation rules from the model
        $validationRules = $apiKeysModel->getValidationRules();

        // Validate the incoming data
        if (!$this->validate($validationRules)) {
            // If validation fails, return validation errors
            $data['validation'] = $this->validator;
            return view('back-end/admin/api-keys/new-api-key');
        }

        $actionUrl = $this->request->getUri()->getPath();
        $previousData = null;
        // If validation passes, create the key
        $data = [
            'api_key' => $this->request->getPost('api_key'),
            'assigned_to' => $this->request->getPost('assigned_to'),
            'status' => $this->request->getPost('status'),
            'created_by' => $loggedInUserId,
            'updated_by' => null
        ];

        // Call createApiAccessKey method from the APIAccessModel
        if ($apiKeysModel->createApiAccessKey($data)) {
            //inserted api_id
            $insertedId = $apiKeysModel->getInsertID();

            // Record created successfully. Redirect to dashboard
            $createSuccessMsg = str_replace('[Record]', 'API Key', lang('App.create_success_msg'));
            session()->setFlashdata('successAlert', $createSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::API_KEY_CREATION, 'ApiKey created with id: ' . $insertedId, $actionUrl, get_class($apiKeysModel), $insertedId, json_encode($previousData), json_encode($data));

            return redirect()->to('/account/admin/api-keys');
        } else {
            // Failed to create record. Redirect to dashboard
            $errorMsg = lang('App.error_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_API_KEY_CREATION, 'Failed to create api-key with key: ' . $this->request->getPost('api_key'), $actionUrl, get_class($apiKeysModel), null, json_encode($previousData), json_encode($data));

            return view('back-end/admin/api-keys/new-api-key');
        }
    }

    public function editApiKey($apiId)
    {
        $apiKeysModel = new APIAccessModel();

        // Fetch the data based on the id
        $apiKey = $apiKeysModel->where('api_id', $apiId)->first();

        if (!$apiKey) {
            $errorMsg = lang('App.not_found_msg');
            session()->setFlashdata('errorAlert', $errorMsg);
            return redirect()->to('/account/admin/api-keys');
        }

        // Set data to pass in view
        $data = [
            'api_key_data' => $apiKey
        ];

        return view('back-end/admin/api-keys/edit-api-key', $data);
    }

    public function updateApiKey()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        $apiKeysModel = new APIAccessModel();

        // Custom validation rules
        $rules = [
            'api_id' => 'required',
            'api_key' => 'required',
            'assigned_to' => 'required',
            'status' => 'required'
        ];

        $apiId = $this->request->getPost('api_id');
        $data['api_key_data'] = $apiKeysModel->where('api_id', $apiId)->first();

        $actionUrl = $this->request->getUri()->getPath() . '/' . $apiId;
        $previousData = $apiKeysModel->find($apiId);
        if($this->validate($rules)){
            $db = \Config\Database::connect();
            $builder = $db->table('api_accesses');
            $data = [
                'api_key' => $this->request->getPost('api_key'),
                'assigned_to' => $this->request->getPost('assigned_to'),
                'status' => $this->request->getPost('status'),
                'created_by' => $this->request->getPost('created_by'),
                'updated_by' => $loggedInUserId
            ];

            $builder->where('api_id', $apiId);
            $builder->update($data);

            // Record updated successfully. Redirect to dashboard
            $editSuccessMsg = str_replace('[Record]', 'API Key', lang('App.edit_success_msg'));
            session()->setFlashdata('successAlert', $editSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::API_KEY_UPDATE, 'ApiKey updated with id: ' . $apiId, $actionUrl, get_class($apiKeysModel), $apiId, json_encode($previousData), json_encode($data));

            return redirect()->to('/account/admin/api-keys');
        }
        else{
            $data['validation'] = $this->validator;
            $errorMsg = lang('App.missing_inputs_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_API_KEY_UPDATE, 'Failed to update ApiKey with id: ' . $apiId, $actionUrl, get_class($apiKeysModel), $apiId, json_encode($previousData), json_encode($data));

            return view('back-end/admin/api-keys/edit-api-key', $data);
        }
    }
}
