<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Persistence\Repository\GameSettingRepository;

final class GameInformationService
{
    /** @var array<string, array<int, array{key: string, default: int}>> */
    private const SETTING_GROUP = [
        'game' => [
            ['key' => 'pvp', 'default' => 1],
            ['key' => 'pvpday', 'default' => 3],
            ['key' => 'pvpimmunity', 'default' => 5],
            ['key' => 'pvpminexp', 'default' => 1500],
            ['key' => 'soap', 'default' => 1],
            ['key' => 'newplayerstartgold', 'default' => 50],
        ],
        'new-day' => [
            ['key' => 'fightsforinterest', 'default' => 1],
            ['key' => 'maxinterest', 'default' => 10],
            ['key' => 'mininterest', 'default' => 1],
            ['key' => 'daysperday', 'default' => 4],
            ['key' => 'specialtybonus', 'default' => 1],
        ],
        'bank' => [
            ['key' => 'borrowperlevel', 'default' => 20],
            ['key' => 'transferperlevel', 'default' => 100],
            ['key' => 'mintransferlev', 'default' => 2],
            ['key' => 'transferreceive', 'default' => 3],
            ['key' => 'maxtransferout', 'default' => 100],
        ],
        'bounty' => [
            ['key' => 'bountymin', 'default' => 50],
            ['key' => 'bountymax', 'default' => 400],
            ['key' => 'bountylevel', 'default' => 3],
            ['key' => 'bountyfee', 'default' => 10],
            ['key' => 'maxbounties', 'default' => 5],
        ],
        'forest' => [
            ['key' => 'turns', 'default' => 10],
            ['key' => 'dropmingold', 'default' => 1],
            ['key' => 'lowslumlevel', 'default' => 2],
        ],
        'mail' => [
            ['key' => 'mailsizelimit', 'default' => 1024],
            ['key' => 'inboxlimit', 'default' => 50],
            ['key' => 'oldmail', 'default' => 14],
        ],
        'expiration' => [
            ['key' => 'expirecontent', 'default' => 180],
            ['key' => 'expiretrashacct', 'default' => 1],
            ['key' => 'expirenewacct', 'default' => 14],
            ['key' => 'expireoldacct', 'default' => 45],
            ['key' => 'LOGINTIMEOUT', 'default' => 900],
        ],
    ];

    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly GameClock $gameClock,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(): array
    {
        $settingGroupMap = [];

        foreach (self::SETTING_GROUP as $groupCode => $entryList) {
            $settingGroupMap[$groupCode] = \array_map(
                fn (array $entry): array => [
                    'setting_key' => $entry['key'],
                    'setting_value' => $this->gameSettingRepository->getInt(
                        $entry['key'],
                        $entry['default'],
                    ),
                ],
                $entryList,
            );
        }

        $daysPerDay = \max(1, $this->gameClock->daysPerCalendarDay());

        return [
            'license_code' => 'GPL-2.0-only',
            'original_author' => 'Eric Stevens',
            'porter_name' => 'GarnetRapture',
            'days_per_calendar_day' => $daysPerDay,
            'day_duration_hour' => (int) \round(24 / $daysPerDay),
            'server_time' => \date('Y-m-d H:i:s'),
            'game_time' => $this->gameClock->formatGameTime(),
            'game_date' => $this->gameClock->gameDateString(),
            'real_seconds_until_next_game_day' => $this->gameClock->realSecondsUntilNextGameDay(),
            'setting_group_map' => $settingGroupMap,
        ];
    }
}
