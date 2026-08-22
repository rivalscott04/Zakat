<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Models\NotificationPreference;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;

/** PRD 16S §39 — preference milik user pada satu organisasi. */
class NotificationPreferenceService
{
    public function __construct(private readonly AuditService $audits) {}

    /** @return array<int, array<string, mixed>> */
    public function forCurrentUser(): array
    {
        return NotificationPreference::query()
            ->where('user_id', Auth::id())
            ->where('organization_id', OrganizationContext::requireId())
            ->orderBy('event_name')
            ->get()
            ->map(fn (NotificationPreference $preference) => [
                'event_name' => $preference->event_name,
                'channel' => $preference->channel->value,
                'enabled' => $preference->enabled,
            ])
            ->all();
    }

    /**
     * @param  array<int, array{event_name: string, channel: string, enabled: bool}>  $preferences
     * @return array<int, array<string, mixed>>
     */
    public function update(array $preferences): array
    {
        $userId = Auth::id();
        $organizationId = OrganizationContext::requireId();

        foreach ($preferences as $preference) {
            NotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'organization_id' => $organizationId,
                    'event_name' => $preference['event_name'],
                    'channel' => NotificationChannel::from($preference['channel']),
                ],
                ['enabled' => (bool) $preference['enabled']],
            );
        }

        $this->audits->record(
            'notification_preference_updated',
            context: ['count' => count($preferences)],
            organizationId: $organizationId,
        );

        return $this->forCurrentUser();
    }
}
