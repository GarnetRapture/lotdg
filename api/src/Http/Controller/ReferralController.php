<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\ReferralService;
use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\MailService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class ReferralController implements ControllerInterface
{
    private readonly ReferralService $referralService;

    private readonly MailService $mailService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->referralService = new ReferralService($connection);
        $this->mailService = new MailService(
            $connection,
            $gameSettingRepository,
            new BadWordFilter($connection, $gameSettingRepository),
        );
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        $characterId = (int) ($parameterMap['character_id'] ?? 0);

        if ($characterId <= 0) {
            throw new LocalizedException('system-message', 'error.invalid-character-id');
        }

        $accountId = $this->mailService->requireAccountId($characterId);

        return match ($parameterMap['action'] ?? 'inspect') {
            'inspect' => $this->referralService->inspect($accountId),
            'register' => $this->referralService->registerReferrer(
                $accountId,
                \is_string($_POST['referrer_login_name'] ?? null)
                    ? \trim($_POST['referrer_login_name'])
                    : '',
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
