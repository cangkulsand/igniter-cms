<?php

namespace App\Controllers\Admin;

use App\Constants\ActivityTypes;
use App\Controllers\BaseController;
use App\Models\ConfigurationsModel;

/**
 * Handles the admin "Configurations" domain (list / create / edit / view).
 *
 * Extracted from the former God Class AdminController (Extract Class, smell #1).
 * Methods were moved verbatim; URLs are unchanged (see app/Config/Routes.php).
 */
class ConfigurationsController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    public function configurations()
    {
        $tableName = 'configurations';
        $configModel = new ConfigurationsModel();

        // Set data to pass in view
        $data = [
            'configurations' => $configModel->orderBy('config_for', 'ASC')->findAll(),
            'total_configurations' => getTotalRecords($tableName)
        ];

        return view('back-end/admin/configurations/index', $data);
    }

    public function newConfiguration()
    {
        return view('back-end/admin/configurations/new-config');
    }

    public function addConfiguration()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        // Load the ConfigurationsModel
        $configModel = new ConfigurationsModel();

        // Validation rules from the model
        $validationRules = $configModel->getValidationRules();

        // Validate the incoming data
        if (!$this->validate($validationRules)) {
            // If validation fails, return validation errors
            $data['validation'] = $this->validator;
            return view('back-end/admin/configurations/new-config');
        }

        $actionUrl = $this->request->getUri()->getPath();
        $previousData = null;

        // If validation passes, create the config
        $dataType = $this->request->getPost('data_type') ;
        $configValue = $this->request->getPost('config_value') ?? $this->request->getPost('default_value');
        $configData = [
            'config_for' => removeTextSpace($this->request->getPost('config_for')),
            'description' => $this->request->getPost('description'),
            'config_value' => strtolower($dataType) === "secret" ? configDataEncryption($configValue) : $configValue,
            'group' => $this->request->getPost('group'),
            'icon' => $this->request->getPost('icon'),
            'data_type' => $dataType,
            'options' => $this->request->getPost('options'),
            'default_value' => $this->request->getPost('default_value'),
            'custom_class' => $this->request->getPost('custom_class'),
            'search_terms' => getCsvFromJsonList($this->request->getPost('search_terms')),
            'deletable' => $this->request->getPost('deletable') ?? 1,
            'created_by' => $loggedInUserId,
            'updated_by' => null
        ];

        // Call createConfiguration method from the ConfigModel
        if ($configModel->createConfiguration($configData)) {
            //inserted user_id
            $insertedId = $configModel->getInsertID();

            // Record created successfully. Redirect to dashboard
            $createSuccessMsg = str_replace('[Record]', 'Configuration', lang('App.create_success_msg'));
            session()->setFlashdata('successAlert', $createSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::CONFIG_CREATION, 'Configuration created with id: ' . $insertedId, $actionUrl, get_class($configModel), $insertedId, json_encode($previousData), json_encode($configData));

            return redirect()->to('/account/admin/configurations');
        } else {
            // Failed to create record. Redirect to dashboard
            $errorMsg = lang('App.error_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_CONFIG_CREATION, 'Failed to create configuration with config_for: ' . $this->request->getPost('config_for'), $actionUrl, get_class($configModel), null, json_encode($previousData), json_encode($configData));

            return view('back-end/admin/configurations/new-config');
        }
    }

    public function viewConfiguration($configId)
    {
        $configModel = new ConfigurationsModel();

        // Fetch the data based on the id
        $configuration = $configModel->where('config_id', $configId)->first();

        if (!$configuration) {
            $errorMsg = lang('App.not_found_msg');
            session()->setFlashdata('errorAlert', $errorMsg);
            return redirect()->to('/account/admin/configurations');
        }

        // Set data to pass in view
        $data = [
            'config_data' => $configuration
        ];

        return view('back-end/admin/configurations/view-config', $data);
    }

    public function editConfiguration($configId)
    {
        $configModel = new ConfigurationsModel();

        // Fetch the data based on the id
        $configuration = $configModel->where('config_id', $configId)->first();

        if (!$configuration) {
            $errorMsg = lang('App.not_found_msg');
            session()->setFlashdata('errorAlert', $errorMsg);
            return redirect()->to('/account/admin/configurations');
        }

        // Set data to pass in view
        $data = [
            'config_data' => $configuration
        ];

        return view('back-end/admin/configurations/edit-config', $data);
    }

    public function updateConfiguration()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        $configModel = new ConfigurationsModel();

        // Custom validation rules
        $rules = [
            'config_id' => 'required',
            'config_for' => 'required',
            'config_value' => 'required',
        ];

        $configId = $this->request->getPost('config_id');
        $data['config_data'] = $configModel->where('config_id', $configId)->first();

        $actionUrl = $this->request->getUri()->getPath() . '/' . $configId;
        $previousData = $configModel->find($configId);

        $dataType = $this->request->getPost('data_type') ;
        $configValue = $this->request->getPost('config_value') ?? $this->request->getPost('default_value');
        if($this->validate($rules)){
            $db = \Config\Database::connect();
            $builder = $db->table('configurations');
            $data = [
                'config_for' => removeTextSpace($this->request->getPost('config_for')),
                'description' => $this->request->getPost('description'),
                'config_value'  => strtolower($dataType) === "secret" ? configDataEncryption($configValue) : $configValue,
                'group' => $this->request->getPost('group'),
                'icon' => $this->request->getPost('icon'),
                'data_type' => $dataType,
                'options' => $this->request->getPost('options'),
                'default_value' => $this->request->getPost('default_value'),
                'custom_class' => $this->request->getPost('custom_class'),
                'search_terms' => getCsvFromJsonList($this->request->getPost('search_terms')),
                'deletable' => $this->request->getPost('deletable') ?? 1,
                'created_by' => $this->request->getPost('created_by'),
                'updated_by' => $loggedInUserId
            ];

            $builder->where('config_id', $configId);
            $builder->update($data);

            // Record updated successfully. Redirect to dashboard
            $editSuccessMsg = str_replace('[Record]', 'Configuration', lang('App.edit_success_msg'));
            session()->setFlashdata('successAlert', $editSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::CONFIG_UPDATE, 'Config updated with id: ' . $configId, $actionUrl, get_class($configModel), $configId, json_encode($previousData), json_encode($data));

            return redirect()->to('/account/admin/configurations?dt-key='.$data["config_for"]);
        }
        else{
            $data['validation'] = $this->validator;
            $errorMsg = lang('App.missing_inputs_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_CONFIG_UPDATE, 'Failed to update config with id: ' . $configId, $actionUrl, get_class($configModel), $configId, json_encode($previousData), json_encode($data));

            return view('back-end/admin/configurations/edit-config', $data);
        }
    }
}
