<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\CommentaryService;
use Lotdg\Domain\Social\NewsService;
use Lotdg\Domain\World\GameClock;
use Lotdg\Domain\World\Special\CrazyAudreyEvent;
use Lotdg\Domain\World\Special\DarkHorseTavernEvent;
use Lotdg\Domain\World\Special\DistressEvent;
use Lotdg\Domain\World\Special\FairyEvent;
use Lotdg\Domain\World\Special\GlowingStreamEvent;
use Lotdg\Domain\World\Special\GoldMineEvent;
use Lotdg\Domain\World\Special\GrassyFieldEvent;
use Lotdg\Domain\World\Special\InstantRewardEvent;
use Lotdg\Domain\World\Special\NecromancerEvent;
use Lotdg\Domain\World\Special\OldManBetEvent;
use Lotdg\Domain\World\Special\OldManTownEvent;
use Lotdg\Domain\World\Special\RiddleEvent;
use Lotdg\Domain\World\Special\SkillMasterEvent;
use Lotdg\Domain\World\Special\SpecialEventState;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class SpecialEventController implements ControllerInterface
{
    private readonly OldManBetEvent $oldManBetEvent;

    private readonly InstantRewardEvent $instantRewardEvent;

    private readonly GlowingStreamEvent $glowingStreamEvent;

    private readonly FairyEvent $fairyEvent;

    private readonly GrassyFieldEvent $grassyFieldEvent;

    private readonly RiddleEvent $riddleEvent;

    private readonly CrazyAudreyEvent $crazyAudreyEvent;

    private readonly GoldMineEvent $goldMineEvent;

    private readonly SkillMasterEvent $skillMasterEvent;

    private readonly DistressEvent $distressEvent;

    private readonly NecromancerEvent $necromancerEvent;

    private readonly OldManTownEvent $oldManTownEvent;

    private readonly DarkHorseTavernEvent $darkHorseTavernEvent;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);
        $eventState = new SpecialEventState($connection);
        $newsService = new NewsService(
            $connection,
            $gameSettingRepository,
            new GameClock($gameSettingRepository),
        );
        $commentaryService = new CommentaryService(
            $connection,
            $gameSettingRepository,
            new BadWordFilter($connection, $gameSettingRepository),
        );

        $this->oldManBetEvent = new OldManBetEvent($connection);
        $this->instantRewardEvent = new InstantRewardEvent($connection);
        $this->glowingStreamEvent = new GlowingStreamEvent($connection, $eventState, $newsService);
        $this->fairyEvent = new FairyEvent($connection, $eventState);
        $this->grassyFieldEvent = new GrassyFieldEvent($connection, $eventState, $commentaryService);
        $this->riddleEvent = new RiddleEvent($connection, $eventState);
        $this->crazyAudreyEvent = new CrazyAudreyEvent($connection, $eventState);
        $this->goldMineEvent = new GoldMineEvent($connection, $eventState, $newsService);
        $this->skillMasterEvent = new SkillMasterEvent($connection, $eventState);
        $this->distressEvent = new DistressEvent($connection, $eventState, $newsService);
        $this->necromancerEvent = new NecromancerEvent($connection, $eventState, $newsService);
        $this->oldManTownEvent = new OldManTownEvent($connection, $eventState);
        $this->darkHorseTavernEvent = new DarkHorseTavernEvent(
            $connection,
            $eventState,
            $commentaryService,
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
        $eventCode = $parameterMap['event_code'] ?? '';
        $action = $parameterMap['action'] ?? 'start';

        if ($characterId <= 0 || $eventCode === '') {
            throw new LocalizedException('system-message', 'error.invalid-parameter');
        }

        if (\in_array($eventCode, InstantRewardEvent::EVENT_CODE_LIST, true)) {
            return $this->instantRewardEvent->trigger($characterId, $eventCode);
        }

        return match ($eventCode) {
            OldManBetEvent::EVENT_CODE => $this->handleOldManBet($characterId, $action),
            GlowingStreamEvent::EVENT_CODE => $this->handleGlowingStream($characterId, $action),
            FairyEvent::EVENT_CODE => $this->handleFairy($characterId, $action),
            GrassyFieldEvent::EVENT_CODE => $this->handleGrassyField($characterId, $action),
            RiddleEvent::EVENT_CODE => $this->handleRiddle($characterId, $action),
            CrazyAudreyEvent::EVENT_CODE => $this->handleCrazyAudrey($characterId, $action),
            GoldMineEvent::EVENT_CODE => $this->handleGoldMine($characterId, $action),
            SkillMasterEvent::EVENT_CODE => $this->handleSkillMaster($characterId, $action),
            DistressEvent::EVENT_CODE => $this->handleDistress($characterId, $action),
            NecromancerEvent::EVENT_CODE => $this->handleNecromancer($characterId, $action),
            OldManTownEvent::EVENT_CODE => $this->handleOldManTown($characterId, $action),
            DarkHorseTavernEvent::EVENT_CODE => $this->handleDarkHorse($characterId, $action),
            default => throw new LocalizedException('system-message', 'error.unknown-special-event'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleOldManBet(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->oldManBetEvent->start($characterId),
            'decline' => $this->oldManBetEvent->decline($characterId),
            'bet' => $this->oldManBetEvent->placeBet($characterId, (int) ($_POST['bet'] ?? 0)),
            'guess' => $this->oldManBetEvent->guess($characterId, (int) ($_POST['guess'] ?? 0)),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleGlowingStream(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->glowingStreamEvent->start($characterId),
            'decline' => $this->glowingStreamEvent->decline($characterId),
            'drink' => $this->glowingStreamEvent->drink($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleFairy(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->fairyEvent->start($characterId),
            'give' => $this->fairyEvent->give($characterId),
            'refuse' => $this->fairyEvent->refuse($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleGrassyField(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->grassyFieldEvent->start($characterId),
            'post' => $this->grassyFieldEvent->post(
                $characterId,
                \is_string($_POST['comment_text'] ?? null) ? $_POST['comment_text'] : '',
            ),
            'leave' => $this->grassyFieldEvent->leave($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleRiddle(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->riddleEvent->start($characterId),
            'accept' => $this->riddleEvent->accept($characterId),
            'decline' => $this->riddleEvent->decline($characterId),
            'answer' => $this->riddleEvent->answer(
                $characterId,
                \is_string($_POST['answer'] ?? null) ? $_POST['answer'] : '',
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleCrazyAudrey(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->crazyAudreyEvent->start($characterId),
            'play' => $this->crazyAudreyEvent->play($characterId),
            'run' => $this->crazyAudreyEvent->runAway($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleGoldMine(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->goldMineEvent->start($characterId),
            'mine' => $this->goldMineEvent->mine($characterId),
            'decline' => $this->goldMineEvent->decline($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleSkillMaster(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->skillMasterEvent->start($characterId),
            'give' => $this->skillMasterEvent->give($characterId),
            'refuse' => $this->skillMasterEvent->refuse($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleDistress(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->distressEvent->start($characterId),
            'visit' => $this->distressEvent->visit($characterId, (int) ($_POST['location_code'] ?? 0)),
            'ignore' => $this->distressEvent->ignore($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleNecromancer(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->necromancerEvent->start($characterId),
            'approach' => $this->necromancerEvent->approach($characterId),
            'leave' => $this->necromancerEvent->leave($characterId),
            'give-gem' => $this->necromancerEvent->giveGem($characterId),
            'keep-gem' => $this->necromancerEvent->keepGem($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleOldManTown(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->oldManTownEvent->start($characterId),
            'escort' => $this->oldManTownEvent->escort($characterId),
            'decline' => $this->oldManTownEvent->decline($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function handleDarkHorse(int $characterId, string $action): array
    {
        return match ($action) {
            'start' => $this->darkHorseTavernEvent->start($characterId),
            'enter' => $this->darkHorseTavernEvent->enter($characterId),
            'leave' => $this->darkHorseTavernEvent->leave($characterId),
            'etching' => $this->darkHorseTavernEvent->viewEtching($characterId),
            'etching-post' => $this->darkHorseTavernEvent->postEtching(
                $characterId,
                \is_string($_POST['comment_text'] ?? null) ? $_POST['comment_text'] : '',
            ),
            'enemy-search' => $this->darkHorseTavernEvent->searchEnemy(
                $characterId,
                \is_string($_GET['search_term'] ?? null) ? \trim($_GET['search_term']) : '',
            ),
            'enemy-inspect' => $this->darkHorseTavernEvent->inspectEnemy(
                $characterId,
                \is_string($_POST['target_login_name'] ?? null)
                    ? \trim($_POST['target_login_name'])
                    : '',
            ),
            'stone-start' => $this->darkHorseTavernEvent->startStoneGame(
                $characterId,
                \is_string($_POST['side'] ?? null) ? $_POST['side'] : '',
                (int) ($_POST['bet'] ?? 0),
            ),
            'stone-draw' => $this->darkHorseTavernEvent->drawStone($characterId),
            'dice-start' => $this->darkHorseTavernEvent->startDiceGame(
                $characterId,
                (int) ($_POST['bet'] ?? 0),
            ),
            'dice-reroll' => $this->darkHorseTavernEvent->rerollDice($characterId),
            'dice-keep' => $this->darkHorseTavernEvent->keepDice($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
