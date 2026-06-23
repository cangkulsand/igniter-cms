<?php

namespace App\Controllers\Admin;

use App\Constants\ActivityTypes;
use App\Controllers\BaseController;
use App\Models\UsersModel;

/**
 * Handles the admin "Users" domain (list / create / edit / view).
 *
 * Extracted from the former God Class AdminController (Extract Class, smell #1).
 * Methods were moved verbatim; URLs are unchanged (see app/Config/Routes.php).
 */
class UsersController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    public function users()
    {
        $tableName = 'users';
        $usersModel = new UsersModel();

        // Set data to pass in view
        $data = [
            'users' => $usersModel->orderBy('first_name', 'ASC')->findAll(),
            'total_users' => getTotalRecords($tableName)
        ];

        return view('back-end/admin/users/index', $data);
    }

    public function newUser()
    {
        return view('back-end/admin/users/new-user');
    }

    public function addUser()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        // Load the UsersModel
        $usersModel = new UsersModel();

        // Validation rules from the model
        $validationRules = $usersModel->getValidationRules();

        // Validate the incoming data
        if (!$this->validate($validationRules)) {
            // If validation fails, return validation errors
            $data['validation'] = $this->validator;
            return view('back-end/admin/users/new-user');
        }

        $actionUrl = $this->request->getUri()->getPath();
        $previousData = null;
        // If validation passes, create the user
        $userData = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name' => $this->request->getPost('last_name'),
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'status' => $this->request->getPost('status'),
            'role' => $this->request->getPost('role'),
            'profile_picture' => $this->request->getPost('profile_picture') ?? getDefaultProfileImagePath(),
            'twitter_link' => $this->request->getPost('twitter_link'),
            'facebook_link' => $this->request->getPost('facebook_link'),
            'instagram_link' => $this->request->getPost('instagram_link'),
            'linkedin_link' => $this->request->getPost('linkedin_link'),
            'about_summary' => $this->request->getPost('about_summary'),
            'password_change_required' => $this->request->getPost('password_change_required') ?? false,
        ];
        $cleanedUserData = $userData;
        unset($cleanedUserData['password']);

        // Call createUser method from the UsersModel
        if ($usersModel->createUser($userData)) {
            //inserted user_id
            $insertedId = $usersModel->getInsertID();

            // Record created successfully. Redirect to dashboard
            $createSuccessMsg = str_replace('[Record]', 'User', lang('App.create_success_msg'));
            session()->setFlashdata('successAlert', $createSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::USER_CREATION, 'User created with id: ' . $insertedId, $actionUrl, get_class($usersModel), $insertedId, json_encode($previousData), json_encode($cleanedUserData));

            return redirect()->to('/account/admin/users');
        } else {
            // Failed to create record. Redirect to dashboard
            $errorMsg = lang('App.error_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_USER_CREATION, 'Failed to create user with email: ' . $this->request->getPost('email'), $actionUrl, get_class($usersModel), null, json_encode($cleanedUserData));

            return view('back-end/admin/users/new-user');
        }
    }

    public function editUser($userId)
    {
        $usersModel = new UsersModel();

        // Fetch the data based on the id
        $user = $usersModel->where('user_id', $userId)->first();

        if (!$user) {
            $errorMsg = lang('App.not_found_msg');
            session()->setFlashdata('errorAlert', $errorMsg);
            return redirect()->to('/account/admin/users');
        }

        // Set data to pass in view
        $data = [
            'user_data' => $user
        ];

        return view('back-end/admin/users/edit-user', $data);
    }

    public function updateUser()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        $usersModel = new UsersModel();

        // Custom validation rules
        $rules = [
            'user_id' => 'required',
            'first_name' => 'required|max_length[50]|min_length[2]',
            'last_name' => 'required|max_length[50]|min_length[2]',
            'status' => 'required',
            'role' => 'required',
        ];


        $userId = $this->request->getPost('user_id');
        $data['user_data'] = $usersModel->where('user_id', $userId)->first();
        $actionUrl = $this->request->getUri()->getPath() . '/' . $userId;
        $previousData = $usersModel->find($userId);
        $cleanedPreviousData = $previousData;
        unset($cleanedPreviousData['password']);

        if($this->validate($rules)){

            $db = \Config\Database::connect();
            $builder = $db->table('users');
            $data = [
                'first_name' => $this->request->getPost('first_name'),
                'last_name'  => $this->request->getPost('last_name'),
                'status'  => $this->request->getPost('status'),
                'role'  => $this->request->getPost('role'),
                'profile_picture' => $this->request->getPost('profile_picture') ?? getDefaultProfileImagePath(),
                'twitter_link' => $this->request->getPost('twitter_link'),
                'facebook_link' => $this->request->getPost('facebook_link'),
                'instagram_link' => $this->request->getPost('instagram_link'),
                'linkedin_link' => $this->request->getPost('linkedin_link'),
                'about_summary' => $this->request->getPost('about_summary'),
                'password_change_required' => $this->request->getPost('password_change_required') ?? false,
            ];

            $builder->where('user_id', $userId);
            $builder->update($data);

            // Record updated successfully. Redirect to dashboard
            $createSuccessMsg = str_replace('[Record]', 'User', lang('App.edit_success_msg'));
            session()->setFlashdata('successAlert', $createSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::USER_UPDATE, 'User updated with id: ' . $userId, $actionUrl, get_class($usersModel), $userId, json_encode($cleanedPreviousData), json_encode($data));

            return redirect()->to('/account/admin/users');
        }
        else{
            $data['validation'] = $this->validator;
            $errorMsg = lang('App.missing_inputs_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_USER_UPDATE, 'Failed to update user with id: ' . $userId, $actionUrl, get_class($usersModel), $userId, json_encode($cleanedPreviousData), json_encode($data));

            return view('back-end/admin/users/edit-user', $data);
        }
    }

    public function viewUser($userId)
    {
        $usersModel = new UsersModel();

        // Fetch the data based on the id
        $user = $usersModel->where('user_id', $userId)->first();

        if (!$user) {
            $errorMsg = lang('App.not_found_msg');
            session()->setFlashdata('errorAlert', $errorMsg);
            return redirect()->to('/account/admin/users');
        }

        // Set data to pass in view
        $data = [
            'user_data' => $user
        ];

        return view('back-end/admin/users/view-user', $data);
    }
}
