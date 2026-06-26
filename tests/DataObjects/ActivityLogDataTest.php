<?php

use App\Constants\ActivityTypes;
use App\DataObjects\ActivityLogData;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Behaviour-preservation tests for the ActivityLogData parameter object (Code Smell 2).
 *
 * These lock the field-carrying and the eight-parameter defaults so the
 * Introduce Parameter Object refactoring is provably behaviour-preserving:
 * refLogActivity() unpacks an ActivityLogData in field order and delegates to
 * the original logActivity(), so an object built here produces exactly the same
 * argument list the legacy positional call would have.
 *
 * @internal
 */
final class ActivityLogDataTest extends CIUnitTestCase
{
    public function testCarriesAllEightFieldsThrough(): void
    {
        $data = new ActivityLogData(
            'user@example.com',
            ActivityTypes::USER_LOGIN,
            'from admin panel',
            'https://site/admin',
            'users',
            42,
            '{"old":1}',
            '{"new":2}'
        );

        $this->assertSame('user@example.com', $data->activityBy);
        $this->assertSame(ActivityTypes::USER_LOGIN, $data->activityType);
        $this->assertSame('from admin panel', $data->activityDetails);
        $this->assertSame('https://site/admin', $data->url);
        $this->assertSame('users', $data->auditableType);
        $this->assertSame(42, $data->auditableId);
        $this->assertSame('{"old":1}', $data->oldValues);
        $this->assertSame('{"new":2}', $data->newValues);
    }

    public function testAppliesSameDefaultsAsLogActivitySignature(): void
    {
        // logActivity() defaulted every optional parameter to '' — the DTO must match,
        // so refLogActivity(new ActivityLogData($by, $type)) === logActivity($by, $type).
        $data = new ActivityLogData('user@example.com', ActivityTypes::USER_LOGOUT);

        $this->assertSame('', $data->activityDetails);
        $this->assertSame('', $data->url);
        $this->assertSame('', $data->auditableType);
        $this->assertSame('', $data->auditableId);
        $this->assertSame('', $data->oldValues);
        $this->assertSame('', $data->newValues);
    }
}
