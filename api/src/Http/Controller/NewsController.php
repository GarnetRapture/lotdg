<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\AdministrationService;
use Lotdg\Domain\Social\MailService;
use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\NewsService;
use Lotdg\Domain\World\GameClock;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class NewsController implements ControllerInterface
{
    private readonly NewsService $newsService;

    private readonly AdministrationService $administrationService;

    private readonly MailService $mailService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->newsService = new NewsService(
            $connection,
            $gameSettingRepository,
            new GameClock($gameSettingRepository),
        );
        $this->administrationService = new AdministrationService($connection, $gameSettingRepository);
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
        unset($parameterMap);

        if (($_POST['action'] ?? '') === 'remove') {
            return $this->remove();
        }

        return $this->newsService->listByDayOffset(
            (int) ($_GET['day_offset'] ?? 0),
            (int) ($_GET['page'] ?? 1),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function remove(): array
    {
        $characterId = (int) ($_POST['character_id'] ?? 0);

        if ($characterId <= 0) {
            throw new LocalizedException('system-message', 'error.invalid-character-id');
        }

        $this->administrationService->requireLevel(
            $this->mailService->requireAccountId($characterId),
            AdministrationService::LEVEL_USER_ADMIN,
        );

        $newsId = (int) ($_POST['news_id'] ?? 0);

        return ['removed' => $newsId > 0 && $this->newsService->remove($newsId)];
    }
}
