<?php

namespace App\Controllers\Admin;

use App\Constants\ActivityTypes;
use App\Controllers\BaseController;
use App\Models\CodesModel;

/**
 * Handles the admin "Codes" domain (list / create / edit).
 *
 * Extracted from the former God Class AdminController (Extract Class, smell #1).
 * Methods were moved verbatim; URLs are unchanged (see app/Config/Routes.php).
 */
class CodesController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    public function codes()
    {
        $tableName = 'codes';
        $codesModel = new CodesModel();

        // Set data to pass in view
        $data = [
            'codes' => $codesModel->orderBy('code_for', 'ASC')->findAll(),
            'total_codes' => getTotalRecords($tableName)
        ];

        return view('back-end/admin/codes/index', $data);
    }

    public function newCode()
    {
        return view('back-end/admin/codes/new-code');
    }

    public function addCode()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        // Load the CodesModel
        $codesModel = new CodesModel();

        // Validation rules from the model
        $validationRules = $codesModel->getValidationRules();

        // Validate the incoming data
        if (!$this->validate($validationRules)) {
            // If validation fails, return validation errors
            $data['validation'] = $this->validator;
            return view('back-end/admin/codes/new-code');
        }

        $actionUrl = $this->request->getUri()->getPath();
        $previousData = null;
        // If validation passes, create the code
        $codeData = [
            'code_for' => $this->request->getPost('code_for'),
            'code' => $this->request->getPost('code'),
            'deletable' => 1,
            'created_by' => $loggedInUserId,
            'updated_by' => ""
        ];

        // Call createCode method from the CodeModel
        if ($codesModel->createCode($codeData)) {
            //inserted user_id
            $insertedId = $codesModel->getInsertID();

            // Record created successfully. Redirect to dashboard
            $createSuccessMsg = str_replace('[Record]', 'Code', lang('App.create_success_msg'));
            session()->setFlashdata('successAlert', $createSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::CODE_CREATION, 'Code created with id: ' . $insertedId, $actionUrl, get_class($codesModel), $insertedId, json_encode($previousData), json_encode($codeData));

            return redirect()->to('/account/admin/codes');
        } else {
            // Failed to create record. Redirect to dashboard
            $errorMsg = lang('App.error_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_CODE_CREATION, 'Failed to create code with code_for: ' .$this->request->getPost('code_for'), $actionUrl, get_class($codesModel), null, json_encode($previousData), json_encode($codeData));

            return view('back-end/admin/codes/new-code');
        }
    }

    public function editCode($codeId)
    {
        $codesModel = new CodesModel();

        // Fetch the data based on the id
        $codeuration = $codesModel->where('code_id', $codeId)->first();

        if (!$codeuration) {
            $errorMsg = lang('App.not_found_msg');
            session()->setFlashdata('errorAlert', $errorMsg);
            return redirect()->to('/account/admin/codes');
        }

        // Set data to pass in view
        $data = [
            'code_data' => $codeuration
        ];

        return view('back-end/admin/codes/edit-code', $data);
    }

    public function updateCode()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        $codesModel = new CodesModel();

        // Custom validation rules
        $rules = [
            'code_id' => 'required',
            'code_for' => 'required',
            'code' => 'required',
        ];

        $codeId = $this->request->getPost('code_id');
        $data['code_data'] = $codesModel->where('code_id', $codeId)->first();
        $actionUrl = $this->request->getUri()->getPath() . '/' . $codeId;
        $previousData = $codesModel->find($codeId);

        if($this->validate($rules)){
            $db = \Config\Database::connect();
            $builder = $db->table('codes');
            $data = [
                'code_for' => $this->request->getPost('code_for'),
                'code'  => $this->request->getPost('code'),
                'deletable' => $this->request->getPost('deletable'),
            ];

            $builder->where('code_id', $codeId);
            $builder->update($data);

            // Record updated successfully. Redirect to dashboard
            $editSuccessMsg = str_replace('[Record]', 'Code', lang('App.edit_success_msg'));
            session()->setFlashdata('successAlert', $editSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::CODE_UPDATE, 'Code updated with id: ' . $codeId, $actionUrl, get_class($codesModel), $codeId, json_encode($previousData), json_encode($data));

            return redirect()->to('/account/admin/codes');
        }
        else{
            $data['validation'] = $this->validator;
            $errorMsg = lang('App.missing_inputs_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_CODE_UPDATE, 'Failed to update code with id: ' . $codeId, $actionUrl, get_class($codesModel), $codeId, json_encode($previousData), json_encode($data));

            return view('back-end/admin/codes/edit-code', $data);
        }
    }
}
