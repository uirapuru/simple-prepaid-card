<?php

declare(strict_types=1);

namespace SimplePrepaidCard\CreditCard\Model;

use Money\Money;
use Ramsey\Uuid\UuidInterface;
use SimpleBus\Message\Recorder\ContainsRecordedMessages;
use SimpleBus\Message\Recorder\PrivateMessageRecorderCapabilities;
use SimplePrepaidCard\Common\Model\Aggregate;

final class CreditCard implements ContainsRecordedMessages, Aggregate
{
    use PrivateMessageRecorderCapabilities;

    /** @var int */
    private $id;

    /** @var string */
    private $creditCardId;

    /** @var string */
    private $holderId;

    /** @var CreditCardData */
    private $creditCardData;

    /** @var Money */
    private $balance;

    /** @var Money */
    private $availableBalance;

    /** @var string */
    private $createdAt;

    private function __construct(UuidInterface $creditCardId, UuidInterface $holderId, CreditCardData $creditCardData)
    {
        $this->creditCardId   = $creditCardId;
        $this->holderId       = $holderId;
        $this->creditCardData = $creditCardData;

        $balance = Money::GBP(0);

        $this->balance          = $balance;
        $this->availableBalance = $balance;
        $this->createdAt        = $createdAt        = new \DateTime();

        $this->record(
            new CreditCardWasCreated(
                $creditCardId,
                $holderId,
                $this->creditCardData(),
                $balance,
                $balance,
                $createdAt
            )
        );
    }

    public static function create(UuidInterface $creditCardId, UuidInterface $holderId, CreditCardData $creditCardData): self
    {
        return new self($creditCardId, $holderId, $creditCardData);
    }

    public function creditCardId(): UuidInterface
    {
        return $this->creditCardId;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function availableBalance(): Money
    {
        return $this->availableBalance;
    }

    public function loadFunds(Money $amount, string $reason)
    {
        $this->guardAgainstNegativeFunds($amount);

        $this->availableBalance = $this->availableBalance()->add($amount);
        $this->balance          = $this->balance()->add($amount);

        $this->record(
            new FundsWereLoaded(
                $this->creditCardId(),
                $this->holderId(),
                $amount,
                $reason,
                $this->balance(),
                $this->availableBalance(),
                new \DateTime()
            )
        );
    }

    public function blockFunds(Money $amount)
    {
        $this->guardAgainstNegativeFunds($amount);
        $this->guardAgainstNegativeBalance($amount);

        $this->availableBalance = $this->availableBalance()->subtract($amount);

        $this->record(
            new FundsWereBlocked(
                $this->creditCardId(),
                $this->holderId(),
                $amount,
                $this->balance(),
                $this->availableBalance(),
                new \DateTime()
            )
        );
    }

    public function unblockFunds(Money $unblocked)
    {
        $this->guardAgainstNegativeFunds($unblocked);

        if ($this->availableBalance()->greaterThanOrEqual($this->balance())) {
            return;
        }

        if ($unblocked->add($this->availableBalance())->greaterThan($this->balance())) {
            $unblocked = $this->balance()->subtract($this->availableBalance());
        }

        $this->availableBalance = $this->availableBalance()->add($unblocked);

        $this->record(
            new FundsWereUnblocked(
                $this->creditCardId(),
                $this->holderId(),
                $unblocked,
                $this->balance(),
                $this->availableBalance(),
                new \DateTime()
            )
        );
    }

    public function chargeFunds(Money $amount, string $reason)
    {
        $this->guardAgainstNegativeFunds($amount);
        $this->guardAgainstChargeMoreFundsThanBlocked($amount);

        $this->balance = $this->balance()->subtract($amount);

        $this->record(
            new FundsWereCharged(
                $this->creditCardId(),
                $this->holderId(),
                $amount,
                $reason,
                $this->balance(),
                $this->availableBalance(),
                new \DateTime()
            )
        );
    }

    public function holderId(): UuidInterface
    {
        return $this->holderId;
    }

    public function creditCardData(): CreditCardData
    {
        return $this->creditCardData;
    }

    private function guardAgainstNegativeFunds(Money $amount)
    {
        if ($amount->isNegative()) {
            throw CannotUseNegativeFunds::with($this->creditCardId());
        }
    }

    private function guardAgainstNegativeBalance(Money $amount)
    {
        if ($this->availableBalance()->subtract($amount)->isNegative()) {
            throw CannotBlockMoreThanAvailableFunds::with($this->creditCardId());
        }
    }

    private function guardAgainstChargeMoreFundsThanBlocked(Money $amount)
    {
        $blocked = $this->balance()->subtract($this->availableBalance());

        if ($blocked->subtract($amount)->isNegative()) {
            throw CannotChargeMoreFundsThanBlocked::with($this->creditCardId());
        }
    }
}
