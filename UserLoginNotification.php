<?php
/**
 * code: UserLoginNotification
 */

use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameter;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use WHMCS\Database\Capsule;


final class UserLoginNotification extends AbstractNotification
{
    public function __construct()
    {
        parent::__construct(
            'UserLoginNotification',
            NotificationReportCategory::CLIENT,
            Hooks::USER_LOGIN,
            new NotificationParameterCollection([
                new NotificationParameter(
                    'client_id',
                    lkn_hn_lang('client id'),
                    fn(): int => (int) $this->getClientId($this->whmcsHookParams['user']->id)
                ),
                new NotificationParameter(
                    'client_first_name',
                    lkn_hn_lang('client first name'),
                    fn(): string => getClientFirstNameByClientId($this->getClientId($this->whmcsHookParams['user']->id))
                ),
                new NotificationParameter(
                    'client_last_name',
                    lkn_hn_lang('client last name'),
                    fn(): string => getClientLastNameByClientId($this->getClientId($this->whmcsHookParams['user']->id))
                ),
                new NotificationParameter(
                    'client_full_name',
                    lkn_hn_lang('client full name'),
                    fn(): string => getClientFullNameByClientId($this->getClientId($this->whmcsHookParams['user']->id))
                )
            ]),
            fn(): int => (int) $this->getClientId($this->whmcsHookParams['user']->id)
        );
    }

    protected function getClientId(int $userId)
    {
        return Capsule::table('tblusers_clients')->where('auth_user_id', $userId)->value('client_id');
    }
}
