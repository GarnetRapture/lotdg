<?php

declare(strict_types=1);

namespace Lotdg\Http;

use Lotdg\Http\Controller\AdministrationController;
use Lotdg\Http\Controller\AuthenticationController;
use Lotdg\Http\Controller\BankController;
use Lotdg\Http\Controller\BiographyController;
use Lotdg\Http\Controller\BountyController;
use Lotdg\Http\Controller\CharacterController;
use Lotdg\Http\Controller\CommentaryController;
use Lotdg\Http\Controller\CreatureEditorController;
use Lotdg\Http\Controller\DragonController;
use Lotdg\Http\Controller\EquipmentEditorController;
use Lotdg\Http\Controller\EquipmentShopController;
use Lotdg\Http\Controller\GameInformationController;
use Lotdg\Http\Controller\ForestController;
use Lotdg\Http\Controller\GemTraderController;
use Lotdg\Http\Controller\GraveyardController;
use Lotdg\Http\Controller\GypsySeerController;
use Lotdg\Http\Controller\HallOfFameController;
use Lotdg\Http\Controller\HealerController;
use Lotdg\Http\Controller\InnController;
use Lotdg\Http\Controller\MountStableController;
use Lotdg\Http\Controller\LocaleController;
use Lotdg\Http\Controller\MailController;
use Lotdg\Http\Controller\MessageOfTheDayController;
use Lotdg\Http\Controller\NewDayController;
use Lotdg\Http\Controller\NewsController;
use Lotdg\Http\Controller\OuthouseController;
use Lotdg\Http\Controller\PetitionController;
use Lotdg\Http\Controller\PlayerVersusPlayerController;
use Lotdg\Http\Controller\PreferenceController;
use Lotdg\Http\Controller\ReferralController;
use Lotdg\Http\Controller\RegistrationController;
use Lotdg\Http\Controller\ShadeRealmController;
use Lotdg\Http\Controller\SocialVenueController;
use Lotdg\Http\Controller\SpecialEventController;
use Lotdg\Http\Controller\TauntEditorController;
use Lotdg\Http\Controller\TrainingController;
use Lotdg\Http\Controller\VillageController;
use Lotdg\Http\Controller\WarriorListController;
use Lotdg\Http\Controller\WebVoteController;

final class RouteRegistry
{
    /** @var array<string, array<string, class-string<ControllerInterface>>> */
    private const array ROUTE_TABLE = [
        'GET' => [
            '/api/locale' => LocaleController::class,
            '/api/locale/{locale_code}' => LocaleController::class,
            '/api/character/{character_id}' => CharacterController::class,
            '/api/village/{character_id}' => VillageController::class,
            '/api/training/{character_id}/{action}' => TrainingController::class,
            '/api/bank/{character_id}/{action}' => BankController::class,
            '/api/shop/{shop_type}/{character_id}/{action}' => EquipmentShopController::class,
            '/api/graveyard/{character_id}/{action}' => GraveyardController::class,
            '/api/pvp/{character_id}/{action}' => PlayerVersusPlayerController::class,
            '/api/inn/{character_id}/{action}' => InnController::class,
            '/api/healer/{character_id}/{action}' => HealerController::class,
            '/api/stable/{character_id}/{action}' => MountStableController::class,
            '/api/gem-trader/{character_id}/{action}' => GemTraderController::class,
            '/api/gypsy/{character_id}/{action}' => GypsySeerController::class,
            '/api/hall-of-fame' => HallOfFameController::class,
            '/api/warrior-list' => WarriorListController::class,
            '/api/biography/{character_id}' => BiographyController::class,
            '/api/preference/{character_id}/{action}' => PreferenceController::class,
            '/api/venue/{venue_code}/{character_id}/{action}' => SocialVenueController::class,
            '/api/outhouse/{character_id}/{action}' => OuthouseController::class,
            '/api/bounty/{character_id}/{action}' => BountyController::class,
            '/api/mail/{character_id}/{action}' => MailController::class,
            '/api/news' => NewsController::class,
            '/api/motd/{character_id}/{action}' => MessageOfTheDayController::class,
            '/api/commentary/{section_code}/{character_id}/{action}' => CommentaryController::class,
            '/api/petition/{action}' => PetitionController::class,
            '/api/taunt/{character_id}/{action}' => TauntEditorController::class,
            '/api/referral/{character_id}/{action}' => ReferralController::class,
            '/api/shade/{character_id}/{action}' => ShadeRealmController::class,
            '/api/creature/{character_id}/{action}' => CreatureEditorController::class,
            '/api/special/{event_code}/{character_id}/{action}' => SpecialEventController::class,
            '/api/equipment-editor/{shop_type}/{character_id}/{action}' => EquipmentEditorController::class,
            '/api/web-vote/{character_id}/{action}' => WebVoteController::class,
            '/api/game-information' => GameInformationController::class,
            '/api/administration/{character_id}/{action}' => AdministrationController::class,
        ],
        'POST' => [
            '/api/news' => NewsController::class,
            '/api/authentication/login' => AuthenticationController::class,
            '/api/authentication/register' => RegistrationController::class,
            '/api/forest/{character_id}/{action}' => ForestController::class,
            '/api/training/{character_id}/{action}' => TrainingController::class,
            '/api/bank/{character_id}/{action}' => BankController::class,
            '/api/shop/{shop_type}/{character_id}/{action}' => EquipmentShopController::class,
            '/api/graveyard/{character_id}/{action}' => GraveyardController::class,
            '/api/dragon/{character_id}/{action}' => DragonController::class,
            '/api/pvp/{character_id}/{action}' => PlayerVersusPlayerController::class,
            '/api/newday/{character_id}' => NewDayController::class,
            '/api/inn/{character_id}/{action}' => InnController::class,
            '/api/healer/{character_id}/{action}' => HealerController::class,
            '/api/stable/{character_id}/{action}' => MountStableController::class,
            '/api/gem-trader/{character_id}/{action}' => GemTraderController::class,
            '/api/gypsy/{character_id}/{action}' => GypsySeerController::class,
            '/api/hall-of-fame' => HallOfFameController::class,
            '/api/warrior-list' => WarriorListController::class,
            '/api/biography/{character_id}' => BiographyController::class,
            '/api/preference/{character_id}/{action}' => PreferenceController::class,
            '/api/venue/{venue_code}/{character_id}/{action}' => SocialVenueController::class,
            '/api/outhouse/{character_id}/{action}' => OuthouseController::class,
            '/api/bounty/{character_id}/{action}' => BountyController::class,
            '/api/mail/{character_id}/{action}' => MailController::class,
            '/api/motd/{character_id}/{action}' => MessageOfTheDayController::class,
            '/api/commentary/{section_code}/{character_id}/{action}' => CommentaryController::class,
            '/api/petition/{action}' => PetitionController::class,
            '/api/taunt/{character_id}/{action}' => TauntEditorController::class,
            '/api/referral/{character_id}/{action}' => ReferralController::class,
            '/api/shade/{character_id}/{action}' => ShadeRealmController::class,
            '/api/creature/{character_id}/{action}' => CreatureEditorController::class,
            '/api/special/{event_code}/{character_id}/{action}' => SpecialEventController::class,
            '/api/equipment-editor/{shop_type}/{character_id}/{action}' => EquipmentEditorController::class,
            '/api/web-vote/{character_id}/{action}' => WebVoteController::class,
            '/api/game-information' => GameInformationController::class,
            '/api/administration/{character_id}/{action}' => AdministrationController::class,
        ],
    ];

    public function match(string $requestMethod, string $requestPath): ?MatchedRoute
    {
        $methodRouteMap = self::ROUTE_TABLE[\strtoupper($requestMethod)] ?? [];

        foreach ($methodRouteMap as $routePattern => $controllerClassName) {
            $parameterMap = $this->extractParameterMap($routePattern, $requestPath);

            if ($parameterMap === null) {
                continue;
            }

            return new MatchedRoute($controllerClassName, $parameterMap);
        }

        return null;
    }

    /**
     * @return array<string, string>|null 매칭 실패 시 null.
     */
    private function extractParameterMap(string $routePattern, string $requestPath): ?array
    {
        $patternSegmentList = \explode('/', \trim($routePattern, '/'));
        $pathSegmentList = \explode('/', \trim($requestPath, '/'));

        if (\count($patternSegmentList) !== \count($pathSegmentList)) {
            return null;
        }

        $parameterMap = [];

        foreach ($patternSegmentList as $segmentIndex => $patternSegment) {
            $pathSegment = $pathSegmentList[$segmentIndex];

            if (\str_starts_with($patternSegment, '{') && \str_ends_with($patternSegment, '}')) {
                if ($pathSegment === '') {
                    return null;
                }

                $parameterMap[\trim($patternSegment, '{}')] = \rawurldecode($pathSegment);

                continue;
            }

            if ($patternSegment !== $pathSegment) {
                return null;
            }
        }

        return $parameterMap;
    }
}
