<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class BankService
{
    private const int DEPOSIT_LIMIT_PER_DRAGON_KILL = 20000;

    private const float TRANSFER_FEE_RATE = 0.05;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId): array
    {
        $row = $this->fetchBankRow($characterId);

        return [
            'gold' => (int) $row['gold'],
            'gold_in_bank' => (int) $row['gold_in_bank'],
            'deposit_limit' => $this->depositLimit((int) $row['dragon_kill_count']),
            'borrow_limit' => $this->borrowLimit((int) $row['level']),
            'transfer_out_limit' => (int) $row['level']
                * $this->gameSettingRepository->getInt('maxtransferout', 100),
            'transferred_today' => (int) $row['transferred_today'],
            'transfer_allowed' => $this->gameSettingRepository->getBool('allowgoldtransfer', true)
                && ((int) $row['level'] >= $this->gameSettingRepository->getInt('mintransferlev', 3)
                    || (int) $row['dragon_kill_count'] > 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deposit(int $characterId, int $requestedAmount): array
    {
        $row = $this->fetchBankRow($characterId);
        $gold = (int) $row['gold'];
        $amount = $requestedAmount === 0 ? $gold : \abs($requestedAmount);

        if ($amount > $gold) {
            return ['succeeded' => false, 'message_key' => 'bank.error.not-enough-gold-in-hand'];
        }

        $this->applyBalanceChange($characterId, -$amount, $amount);

        $updated = $this->fetchBankRow($characterId);

        return [
            'succeeded' => true,
            'deposited' => $amount,
            'gold' => (int) $updated['gold'],
            'gold_in_bank' => (int) $updated['gold_in_bank'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function withdraw(int $characterId, int $requestedAmount, bool $allowsBorrowing): array
    {
        $row = $this->fetchBankRow($characterId);
        $goldInBank = (int) $row['gold_in_bank'];
        $amount = $requestedAmount === 0 ? \abs($goldInBank) : \abs($requestedAmount);

        if ($amount > $goldInBank && !$allowsBorrowing) {
            return ['succeeded' => false, 'message_key' => 'bank.error.not-enough-gold-in-bank'];
        }

        if ($amount > $goldInBank) {
            $borrowLimit = $this->borrowLimit((int) $row['level']);
            $borrowAmount = $amount - \max(0, $goldInBank);

            if ($borrowAmount > $borrowLimit) {
                return [
                    'succeeded' => false,
                    'message_key' => 'bank.error.borrow-limit-exceeded',
                    'borrow_limit' => $borrowLimit,
                ];
            }
        }

        $this->applyBalanceChange($characterId, $amount, -$amount);

        $updated = $this->fetchBankRow($characterId);

        return [
            'succeeded' => true,
            'withdrawn' => $amount,
            'gold' => (int) $updated['gold'],
            'gold_in_bank' => (int) $updated['gold_in_bank'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transfer(int $senderCharacterId, string $recipientLoginName, int $requestedAmount): array
    {
        $senderRow = $this->fetchBankRow($senderCharacterId);

        if (!$this->gameSettingRepository->getBool('allowgoldtransfer', true)) {
            return ['succeeded' => false, 'message_key' => 'bank.error.transfer-disabled'];
        }

        if ((int) $senderRow['gold_in_bank'] < 0) {
            return ['succeeded' => false, 'message_key' => 'bank.error.transfer-while-in-debt'];
        }

        $grossAmount = \abs($requestedAmount);
        $netAmount = (int) \abs($grossAmount * (1 - self::TRANSFER_FEE_RATE));

        if ($netAmount < (int) $senderRow['level']) {
            return ['succeeded' => false, 'message_key' => 'bank.error.transfer-amount-too-small'];
        }

        $recipientRow = $this->fetchRecipientRow($recipientLoginName);

        if ($recipientRow === null) {
            return ['succeeded' => false, 'message_key' => 'bank.error.recipient-not-found'];
        }

        if ((int) $recipientRow['character_id'] === $senderCharacterId) {
            return ['succeeded' => false, 'message_key' => 'bank.error.transfer-to-self'];
        }

        $transferOutLimit = (int) $senderRow['level']
            * $this->gameSettingRepository->getInt('maxtransferout', 100);

        if ((int) $senderRow['transferred_today'] + $netAmount > $transferOutLimit) {
            return [
                'succeeded' => false,
                'message_key' => 'bank.error.transfer-out-limit-exceeded',
                'transfer_out_limit' => $transferOutLimit,
            ];
        }

        $receiveLimit = (int) $recipientRow['level']
            * $this->gameSettingRepository->getInt('transferperlevel', 100);

        if ($netAmount > $receiveLimit) {
            return [
                'succeeded' => false,
                'message_key' => 'bank.error.recipient-limit-exceeded',
                'receive_limit' => $receiveLimit,
            ];
        }

        if ((int) $recipientRow['received_today'] >= $this->gameSettingRepository->getInt('transferreceive', 3)) {
            return ['succeeded' => false, 'message_key' => 'bank.error.recipient-received-too-many'];
        }

        if ((int) $senderRow['gold'] + (int) $senderRow['gold_in_bank'] < $netAmount) {
            return ['succeeded' => false, 'message_key' => 'bank.error.not-enough-total-gold'];
        }

        $this->connection->beginTransaction();

        try {
            $goldOnHand = (int) $senderRow['gold'];
            $goldFromHand = \min($goldOnHand, $netAmount);
            $goldFromBank = $netAmount - $goldFromHand;

            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold              = gold - :gold_from_hand,
                            gold_in_bank      = gold_in_bank - :gold_from_bank,
                            transferred_today = transferred_today + :net_amount
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'gold_from_hand' => $goldFromHand,
                    'gold_from_bank' => $goldFromBank,
                    'net_amount' => $netAmount,
                    'character_id' => $senderCharacterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold_in_bank   = gold_in_bank + :net_amount,
                            received_today = received_today + 1
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'net_amount' => $netAmount,
                    'character_id' => (int) $recipientRow['character_id'],
                ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'succeeded' => true,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'fee' => $grossAmount - $netAmount,
            'recipient_character_id' => (int) $recipientRow['character_id'],
            'recipient_display_name' => (string) $recipientRow['display_name'],
        ];
    }

    private function depositLimit(int $dragonKillCount): int
    {
        return ($dragonKillCount + 1) * self::DEPOSIT_LIMIT_PER_DRAGON_KILL;
    }

    private function borrowLimit(int $level): int
    {
        return $level * $this->gameSettingRepository->getInt('borrowperlevel', 20);
    }

    private function applyBalanceChange(int $characterId, int $goldDelta, int $bankDelta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gold         = gold + :gold_delta,
                        gold_in_bank = gold_in_bank + :bank_delta
                  WHERE character_id = :character_id',
            )
            ->execute([
                'gold_delta' => $goldDelta,
                'bank_delta' => $bankDelta,
                'character_id' => $characterId,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchBankRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.character_id,
                    game_character.level,
                    character_wealth.gold,
                    character_wealth.gold_in_bank,
                    character_wealth.transferred_today,
                    character_wealth.received_today,
                    character_progression.dragon_kill_count
               FROM game_character
               JOIN character_wealth      ON character_wealth.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
              WHERE game_character.character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchRecipientRow(string $recipientLoginName): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.character_id,
                    game_character.display_name,
                    game_character.level,
                    character_wealth.received_today
               FROM account
               JOIN game_character   ON game_character.account_id = account.account_id
               JOIN character_wealth ON character_wealth.character_id = game_character.character_id
              WHERE account.login_name = :login_name',
        );
        $statement->execute(['login_name' => $recipientLoginName]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
