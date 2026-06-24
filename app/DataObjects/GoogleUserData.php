<?php

namespace App\DataObjects;

/**
 * Parameter Object for a Google-authenticated user's profile.
 *
 * Introduced to remove a Long Parameter List + Data Clump (smell #4):
 * GoogleAuthController::createGoogleUser() previously took five separate
 * parameters (email, first name, last name, Google ID, profile picture) that
 * are all attributes of one Google user and always travel together. They are
 * wrapped here (Introduce Parameter Object), reducing the method to a single
 * parameter. The factory fromGoogleUser() also encapsulates the field
 * extraction + first/last-name fallback that the controller performed inline.
 */
final class GoogleUserData
{
    public function __construct(
        public readonly ?string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $googleId,
        public readonly ?string $profilePicture,
    ) {
    }

    /**
     * Build from a Google Oauth2 userinfo object, applying the same
     * first/last-name fallback the controller used previously.
     *
     * @param object $googleUser Google\Service\Oauth2\Userinfo (or any object
     *                           exposing the same getter methods).
     */
    public static function fromGoogleUser($googleUser): self
    {
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();
        $firstName = $googleUser->getGivenName();
        $lastName = $googleUser->getFamilyName();
        $fullName = $googleUser->getName();
        $profilePicture = $googleUser->getPicture();

        // Split full name if first/last name not provided
        if (empty($firstName) && empty($lastName) && !empty($fullName)) {
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
        }

        return new self($email, $firstName, $lastName, $googleId, $profilePicture);
    }
}
