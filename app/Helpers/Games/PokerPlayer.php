<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Helpers\Games;

use Illuminate\Support\Collection;

class PokerPlayer
{
    protected $pocketCards;
    protected $communityCards;

    protected $hand;

    public function __construct()
    {
        $this->pocketCards = collect();
        $this->communityCards = collect();

        return $this;
    }

    public function getPocketCards(): Collection
    {
        return $this->pocketCards;
    }

    public function setPocketCards(Collection $pocketCards): PokerPlayer
    {
        $this->pocketCards = $pocketCards;

        return $this;
    }

    public function getCommunityCards(): Collection
    {
        return $this->communityCards;
    }

    public function setCommunityCards(Collection $communityCards): PokerPlayer
    {
        $this->communityCards = $communityCards;

        return $this;
    }

    public function getHand(): PokerHand
    {
        return $this->hand;
    }

    public function setHand(PokerHand $hand): PokerPlayer
    {
        $this->hand = $hand;

        return $this;
    }

    public function play(): PokerPlayer
    {
        $this->hand = new PokerHand($this->getPocketCards(), $this->getCommunityCards());

        return $this;
    }
}
