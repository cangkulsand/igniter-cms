<?php

use App\DataObjects\GoogleUserData;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Behaviour-preservation tests for the GoogleUserData parameter object (smell #4).
 *
 * These lock the extraction + first/last-name fallback that
 * GoogleAuthController::processGoogleUser() previously performed inline, so the
 * Introduce Parameter Object refactoring is provably behaviour-preserving.
 *
 * @internal
 */
final class GoogleUserDataTest extends CIUnitTestCase
{
    /**
     * Minimal stand-in for Google\Service\Oauth2\Userinfo — exposes the same
     * getter methods the factory reads.
     */
    private function googleUser(array $values): object
    {
        return new class ($values) {
            public function __construct(private array $v)
            {
            }

            public function getEmail()
            {
                return $this->v['email'] ?? null;
            }

            public function getId()
            {
                return $this->v['id'] ?? null;
            }

            public function getGivenName()
            {
                return $this->v['givenName'] ?? null;
            }

            public function getFamilyName()
            {
                return $this->v['familyName'] ?? null;
            }

            public function getName()
            {
                return $this->v['name'] ?? null;
            }

            public function getPicture()
            {
                return $this->v['picture'] ?? null;
            }
        };
    }

    public function testCarriesFieldsThroughWhenNamesProvided(): void
    {
        $data = GoogleUserData::fromGoogleUser($this->googleUser([
            'email' => 'jane@example.com',
            'id' => 'gid-123',
            'givenName' => 'Jane',
            'familyName' => 'Doe',
            'name' => 'Jane Doe',
            'picture' => 'https://pic/jane.png',
        ]));

        $this->assertSame('jane@example.com', $data->email);
        $this->assertSame('gid-123', $data->googleId);
        $this->assertSame('Jane', $data->firstName);
        $this->assertSame('Doe', $data->lastName);
        $this->assertSame('https://pic/jane.png', $data->profilePicture);
    }

    public function testSplitsFullNameWhenFirstLastMissing(): void
    {
        // Original fallback: empty given/family + a full name -> split on first space.
        $data = GoogleUserData::fromGoogleUser($this->googleUser([
            'email' => 'amir@example.com',
            'id' => 'gid-9',
            'givenName' => '',
            'familyName' => '',
            'name' => 'Amir bin Hassan',
        ]));

        $this->assertSame('Amir', $data->firstName);
        $this->assertSame('bin Hassan', $data->lastName);
    }

    public function testSingleWordFullNameGivesEmptyLastName(): void
    {
        $data = GoogleUserData::fromGoogleUser($this->googleUser([
            'email' => 'cher@example.com',
            'id' => 'gid-1',
            'givenName' => '',
            'familyName' => '',
            'name' => 'Cher',
        ]));

        $this->assertSame('Cher', $data->firstName);
        $this->assertSame('', $data->lastName);
    }
}
