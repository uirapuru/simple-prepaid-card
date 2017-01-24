<?php

declare(strict_types=1);

namespace SimplePrepaidCard\CreditCard\Application\Query;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table
 */
final class StatementView
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="guid")
     */
    public $creditCardId;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime")
     */
    public $date;

    /**
     * @var string
     *
     * @ORM\Column(type="guid")
     */
    public $description;

    /**
     * @var string
     *
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(type="integer")
     */
    public $availableBalance;

    /**
     * @var string
     *
     * @ORM\Column(type="integer")
     */
    public $balance;

    public function __construct(int $id = null, UuidInterface $creditCardId, \DateTime $date, string $description, int $amount, int $availableBalance, int $balance)
    {
        $this->id               = $id;
        $this->creditCardId     = $creditCardId;
        $this->date             = $date;
        $this->description      = $description;
        $this->amount           = $amount;
        $this->availableBalance = $availableBalance;
        $this->balance          = $balance;
    }
}
