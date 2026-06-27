<?php

namespace App\DataObjects;

/**
 * Parameter Object for an activity-log entry.
 *
 * Introduced to remove a Long Parameter List (Code Smell 2): the procedural
 * helper logActivity() declares eight positional parameters
 * (activityBy, activityType, activityDetails, url, auditableType,
 * auditableId, oldValues, newValues) that always travel together to describe
 * one logged action. They are grouped here (Introduce Parameter Object) so the
 * refactored sibling refLogActivity() takes a single, self-documenting object.
 *
 * The defaults mirror logActivity()'s optional-parameter defaults so the two
 * entry points behave identically; refLogActivity() simply unpacks this object
 * and delegates to the original logActivity(), preserving external behaviour.
 */
final class ActivityLogData
{
    public function __construct(
        public readonly string|int $activityBy,
        public readonly string $activityType,
        public readonly string $activityDetails = '',
        public readonly string $url = '',
        public readonly string $auditableType = '',
        public readonly string|int|null $auditableId = '',
        public readonly string $oldValues = '',
        public readonly string $newValues = '',
    ) {
    }
}
