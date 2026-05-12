<?php

namespace Lkn\HookNotification\Core\AdminUI\Http\Controllers;

use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Lkn\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Lkn\HookNotification\Core\Shared\Infrastructure\View\View;
use WHMCS\Database\Capsule;

final class RenewalNotificationController extends BaseController
{
    public function __construct(View $view)
    {
        parent::__construct($view);
    }

    public function viewRenewalSettings(array $request): void
    {
        // Handle form submission
        if (isset($request['save_renewal_settings'])) {
            $this->saveSettings($request);
            header('Location: ?module=lknhooknotification&page=renewal-notifications&saved=1');
            exit;
        }

        $config = lkn_hn_config(Settings::INVOICE_RENEWAL_CONFIG) ?? [];

        $defaults = [
            'enabled' => false,
            'frequency' => 'daily',
            'weekdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'day_of_month' => 1,
            'excluded_client_ids' => [],
        ];

        $config = array_merge($defaults, is_array($config) ? $config : []);

        $this->view->view(
            'pages/renewal-notifications',
            [
                'config' => $config,
                'saved' => isset($request['saved']),
                'all_weekdays' => [
                    'monday' => lkn_hn_lang('Monday'),
                    'tuesday' => lkn_hn_lang('Tuesday'),
                    'wednesday' => lkn_hn_lang('Wednesday'),
                    'thursday' => lkn_hn_lang('Thursday'),
                    'friday' => lkn_hn_lang('Friday'),
                    'saturday' => lkn_hn_lang('Saturday'),
                    'sunday' => lkn_hn_lang('Sunday'),
                ],
            ],
        );
    }

    private function saveSettings(array $request): void
    {
        $enabled = !empty($request['enabled']);
        $frequency = in_array($request['frequency'] ?? 'daily', ['daily', 'weekly', 'monthly'], true)
            ? $request['frequency']
            : 'daily';

        $weekdaysInput = $request['weekdays'] ?? [];
        $validWeekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $weekdays = is_array($weekdaysInput)
            ? array_values(array_intersect($validWeekdays, $weekdaysInput))
            : [];

        $dayOfMonth = (int) ($request['day_of_month'] ?? 1);
        if ($dayOfMonth < 1 || $dayOfMonth > 28) {
            $dayOfMonth = 1;
        }

        // Parse excluded client IDs (comma or newline separated)
        $excludedRaw = trim($request['excluded_client_ids'] ?? '');
        $excludedClientIds = [];
        if ($excludedRaw !== '') {
            $parts = preg_split('/[\s,;]+/', $excludedRaw);
            foreach ($parts as $part) {
                $id = (int) trim($part);
                if ($id > 0) {
                    $excludedClientIds[] = $id;
                }
            }
            $excludedClientIds = array_values(array_unique($excludedClientIds));
        }

        $config = [
            'enabled' => $enabled,
            'frequency' => $frequency,
            'weekdays' => $weekdays,
            'day_of_month' => $dayOfMonth,
            'excluded_client_ids' => $excludedClientIds,
        ];

        Capsule::table('mod_lkn_hook_notification_configs')
            ->updateOrInsert(
                [
                    'setting' => Settings::INVOICE_RENEWAL_CONFIG->value,
                    'platform' => Platforms::MODULE->value,
                ],
                [
                    'value' => json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]
            );
    }
}
