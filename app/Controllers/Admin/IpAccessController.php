<?php

namespace App\Controllers\Admin;

use App\Constants\ActivityTypes;
use App\Controllers\BaseController;
use App\Models\BlockedIPsModel;
use App\Models\WhitelistedIPsModel;

/**
 * Handles the admin "Blocked IPs" and "Whitelisted IPs" domains.
 *
 * Extracted from the former God Class AdminController (Extract Class, smell #1).
 * Methods were moved verbatim; URLs are unchanged (see app/Config/Routes.php).
 */
class IpAccessController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    //############################//
    //       Blocked IPS          //
    //############################//
    public function blockedIps()
    {
        $tableName = 'blocked_ips';
        $blockedIPsModel = new BlockedIPsModel();

        // Set data to pass in view
        $data = [
            'blocked_ips' => $blockedIPsModel->orderBy('created_at', 'DESC')->paginate(intval(env('QUERY_LIMIT_VERY_HIGH', 100))),
            'pager' => $blockedIPsModel->pager,
            'total_blocked_ips' => $blockedIPsModel->pager->getTotal()
        ];

        return view('back-end/admin/blocked-ips/index', $data);
    }

    public function newBlockedIP()
    {
        return view('back-end/admin/blocked-ips/new-blocked-ip');
    }

    public function addBlockedIP()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        // Load the BlockedIPsModel
        $blockedIPsModel = new BlockedIPsModel();

        // Validation rules from the model
        $validationRules = $blockedIPsModel->getValidationRules();

        // Validate the incoming data
        if (!$this->validate($validationRules)) {
            // If validation fails, return validation errors
            $data['validation'] = $this->validator;
            return view('back-end/admin/blocked-ips/new-blocked-ip');
        }

        $actionUrl = $this->request->getUri()->getPath();
        $previousData = null;

        // If validation passes, create the user
        $blockedIPData = [
            'ip_address' => $this->request->getPost('ip_address'),
            'country' => $this->request->getPost('country'),
            'block_start_time' => $this->request->getPost('block_start_time'),
            'block_end_time' => $this->request->getPost('block_end_time'),
            'reason' => $this->request->getPost('reason'),
            'notes' => $this->request->getPost('notes'),
            'page_visited_url' => $this->request->getPost('page_visited_url')
        ];

        // Call createBlockedIP method from the BlockedIPsModel
        if ($blockedIPsModel->createBlockedIP($blockedIPData)) {
            //inserted user_id
            $insertedId = $blockedIPsModel->getInsertID();

            // Record created successfully. Redirect to dashboard
            $createSuccessMsg = str_replace('[Record]', 'Blocked IP', lang('App.create_success_msg'));
            session()->setFlashdata('successAlert', $createSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::BLOCKED_IP_CREATION, 'Blocked IP added with id: ' . $insertedId, $actionUrl, get_class($blockedIPsModel), $insertedId, json_encode($previousData), json_encode($blockedIPData));

            return redirect()->to('/account/admin/blocked-ips');
        } else {
            // Failed to create record. Redirect to dashboard
            $errorMsg = lang('App.error_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_BLOCKED_IP_CREATION, 'Failed to add blocked IP with IP: ' . $this->request->getPost('ip_address'), $actionUrl, get_class($blockedIPsModel), null, json_encode($previousData), json_encode($blockedIPData));

            return view('back-end/admin/blocked-ips/new-blocked-ip');
        }
    }

    //############################//
    //      Whitelisted IPS       //
    //############################//
    public function whitelistedIps()
    {
        $tableName = 'whitelisted_ips';
        $whitelistedIPsModel = new WhitelistedIPsModel();

        // Set data to pass in view
        $data = [
            'whitelisted_ips' => $whitelistedIPsModel->orderBy('created_at', 'DESC')->paginate(intval(env('QUERY_LIMIT_VERY_HIGH', 100))),
            'pager' => $whitelistedIPsModel->pager,
            'total_whitelisted_ips' => $whitelistedIPsModel->pager->getTotal()
        ];

        return view('back-end/admin/whitelisted-ips/index', $data);
    }

    public function newWhitelistedIP()
    {
        return view('back-end/admin/whitelisted-ips/new-whitelisted-ip');
    }

    public function addWhitelistedIP()
    {
        //get logged-in user id
        $loggedInUserId = $this->session->get('user_id');

        // Load the WhitelistedIPsModel
        $whitelistedIPsModel = new WhitelistedIPsModel();

        // Validation rules from the model
        $validationRules = $whitelistedIPsModel->getValidationRules();

        // Validate the incoming data
        if (!$this->validate($validationRules)) {
            // If validation fails, return validation errors
            $data['validation'] = $this->validator;
            return view('back-end/admin/whitelisted-ips/new-whitelisted-ip');
        }

        // If validation passes, create the user
        $whitelistedIPData = [
            'ip_address' => $this->request->getPost('ip_address'),
            'reason' => $this->request->getPost('reason'),
        ];

        // Call createWhitelistedIP method from the WhitelistedIPsModel
        if ($whitelistedIPsModel->createWhitelistedIP($whitelistedIPData)) {
            //inserted user_id
            $insertedId = $whitelistedIPsModel->getInsertID();

            // Record created successfully. Redirect to dashboard
            $createSuccessMsg = str_replace('[Record]', 'Whitelisted IP', lang('App.create_success_msg'));
            session()->setFlashdata('successAlert', $createSuccessMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::WHITELISTED_IP_CREATION, 'Whitelisted IP added with id: ' . $insertedId);

            return redirect()->to('/account/admin/whitelisted-ips');
        } else {
            // Failed to create record. Redirect to dashboard
            $errorMsg = lang('App.error_msg');
            session()->setFlashdata('errorAlert', $errorMsg);

            //log activity
            logActivity($loggedInUserId, ActivityTypes::FAILED_WHITELISTED_IP_CREATION, 'Failed to add whitelisted IP with IP: ' . $this->request->getPost('ip_address'));

            return view('back-end/admin/whitelisted-ips/new-whitelisted-ip');
        }
    }
}
