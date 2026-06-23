<?php

namespace App\DataObjects;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Parameter Object for the admin user create/update forms.
 *
 * Introduced to remove a Data Clump (smell #5): UsersController::addUser() and
 * UsersController::updateUser() each rebuilt the same group of user + social-link
 * fields — applying the same default rules (profile picture fallback,
 * password-change flag) — inline. Those fields always travel together, so they
 * are wrapped in this single object (Introduce Parameter Object).
 *
 * The duplicated field mapping and default logic now live in one place
 * (fromRequest()); each controller method asks the object for the exact array
 * shape it needs. toCreateArray()/toUpdateArray() reproduce the original key
 * order verbatim, so persisted data and activity-log JSON are unchanged.
 */
final class UserData
{
    public function __construct(
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $status,
        public readonly ?string $role,
        public readonly ?string $profilePicture,
        public readonly ?string $twitterLink,
        public readonly ?string $facebookLink,
        public readonly ?string $instagramLink,
        public readonly ?string $linkedinLink,
        public readonly ?string $aboutSummary,
        public readonly mixed $passwordChangeRequired,
        // Credentials — only populated when creating a user.
        public readonly ?string $username = null,
        public readonly ?string $email = null,
        public readonly ?string $rawPassword = null,
    ) {
    }

    /**
     * Build the object from the submitted form, applying the same default rules
     * the original controller methods used.
     *
     * @param bool $withCredentials Whether to read username/email/password
     *                              (true for creation, false for update).
     */
    public static function fromRequest(IncomingRequest $request, bool $withCredentials = false): self
    {
        return new self(
            firstName: $request->getPost('first_name'),
            lastName: $request->getPost('last_name'),
            status: $request->getPost('status'),
            role: $request->getPost('role'),
            profilePicture: $request->getPost('profile_picture') ?? getDefaultProfileImagePath(),
            twitterLink: $request->getPost('twitter_link'),
            facebookLink: $request->getPost('facebook_link'),
            instagramLink: $request->getPost('instagram_link'),
            linkedinLink: $request->getPost('linkedin_link'),
            aboutSummary: $request->getPost('about_summary'),
            passwordChangeRequired: $request->getPost('password_change_required') ?? false,
            username: $withCredentials ? $request->getPost('username') : null,
            email: $withCredentials ? $request->getPost('email') : null,
            rawPassword: $withCredentials ? $request->getPost('password') : null,
        );
    }

    /**
     * Array shape for CREATING a user. Matches the original addUser() field order
     * (credentials interleaved) with the password hashed.
     */
    public function toCreateArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'username' => $this->username,
            'email' => $this->email,
            'password' => password_hash($this->rawPassword, PASSWORD_DEFAULT),
            'status' => $this->status,
            'role' => $this->role,
            'profile_picture' => $this->profilePicture,
            'twitter_link' => $this->twitterLink,
            'facebook_link' => $this->facebookLink,
            'instagram_link' => $this->instagramLink,
            'linkedin_link' => $this->linkedinLink,
            'about_summary' => $this->aboutSummary,
            'password_change_required' => $this->passwordChangeRequired,
        ];
    }

    /**
     * Array shape for UPDATING a user. Matches the original updateUser() field
     * order; username/email/password are not editable here.
     */
    public function toUpdateArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'status' => $this->status,
            'role' => $this->role,
            'profile_picture' => $this->profilePicture,
            'twitter_link' => $this->twitterLink,
            'facebook_link' => $this->facebookLink,
            'instagram_link' => $this->instagramLink,
            'linkedin_link' => $this->linkedinLink,
            'about_summary' => $this->aboutSummary,
            'password_change_required' => $this->passwordChangeRequired,
        ];
    }
}
