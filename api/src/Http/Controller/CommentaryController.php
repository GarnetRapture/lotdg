<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\CommentaryService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class CommentaryController implements ControllerInterface
{
    private const int DEFAULT_LIMIT = 25;

    private readonly CommentaryService $commentaryService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->commentaryService = new CommentaryService(
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
        $sectionCode = $parameterMap['section_code'] ?? '';

        if ($characterId <= 0 || $sectionCode === '') {
            throw new LocalizedException('system-message', 'error.invalid-parameter');
        }

        return match ($parameterMap['action'] ?? 'list') {
            'list' => $this->listWithCursor(
                $characterId,
                $sectionCode,
                self::DEFAULT_LIMIT,
                (int) ($_GET['page'] ?? 0),
            ),
            'post' => $this->commentaryService->post(
                $characterId,
                $sectionCode,
                \is_string($_POST['comment_text'] ?? null) ? $_POST['comment_text'] : '',
                self::DEFAULT_LIMIT,
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function listWithCursor(
        int $characterId,
        string $sectionCode,
        int $limit,
        int $page,
    ): array {
        $beforeCommentaryId = isset($_GET['before_commentary_id'])
            ? (int) $_GET['before_commentary_id']
            : null;

        return $this->commentaryService->listBySection(
            $characterId,
            $sectionCode,
            $limit,
            $page,
            $beforeCommentaryId,
        );
    }
}
