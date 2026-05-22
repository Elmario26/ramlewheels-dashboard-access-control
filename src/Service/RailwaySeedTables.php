<?php

namespace App\Service;

/** Table copy order for full local → Railway seed (parents before children). */
final class RailwaySeedTables
{
    public const ORDER = [
        'users',
        'user_verifications',
        'customer',
        'cars',
        'doctrine_migration_versions',
        'sales',
        'services',
        'documents',
        'document_activity_logs',
        'activity_logs',
        'test_drive_booking',
        'messenger_messages',
    ];
}
