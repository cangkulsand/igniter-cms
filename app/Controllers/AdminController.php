<?php

namespace App\Controllers;

use App\Controllers\BaseController;

/**
 * Admin dashboard entry point.
 *
 * This was formerly a God Class (~1,270 lines, 41 methods across ~10 unrelated
 * admin domains). It has been split via the Extract Class refactoring (smell #1)
 * into focused controllers under app/Controllers/Admin/:
 *   - Admin\UsersController          (users)
 *   - Admin\ApiKeysController        (API keys)
 *   - Admin\ConfigurationsController (configurations)
 *   - Admin\CodesController          (codes)
 *   - Admin\ActivityController       (activity logs, log files, site stats)
 *   - Admin\IpAccessController       (blocked + whitelisted IPs)
 *   - Admin\BackupsController        (backups)
 *
 * Only the dashboard landing page remains here. URLs are unchanged — see
 * app/Config/Routes.php where each route now points at the relevant controller.
 */
class AdminController extends BaseController
{
    public function index()
    {
        return view('back-end/admin/index');
    }
}
