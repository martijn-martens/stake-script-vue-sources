<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Helpers\Games;

use Illuminate\Support\Collection;

class Poker
{
    protected $deck;
    protected $players;
    protected $communityCards;

    public function __construct(CardDeck $deck = NULL)
    {
        $this->deck = $deck ?: new CardDeck();
        $this->players = collect();
        $this->communityCards = collect();

        return $this;
    }

    /**
     * Add specific number of players to the game
     *
     * @param int $numberOfPlayers
     * @return Poker
     */
    public function addPlayers(int $numberOfPlayers): Poker
    {
        if ($this->cardsAreDealt()) {
            throw new \Exception('You can not add more players after cards are dealt.');
        }

        // init players
        $this->players = $this->players->merge(Collection::times($numberOfPlayers, function ($i) {
            return new PokerPlayer();
        }));

        return $this;
    }

    /**
     * Add a new player to the game, useful when PokerPlayer class is extended by a sub-class
     *
     * @param PokerPlayer $player
     * @return Poker
     */
    public function addPlayer(PokerPlayer $player): Poker
    {
        if ($this->cardsAreDealt()) {
            throw new \Exception('You can not add more players after cards are dealt.');
        }

        $this->players->push($player);

        return $this;
    }

    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function getPlayer(int $n): PokerPlayer
    {
        return $this->getPlayers()->get($n - 1);
    }

    public function getDeck(): CardDeck
    {
        return $this->deck;
    }

    public function getCommunityCards(): Collection
    {
        return $this->communityCards;
    }

    public function dealPocketCards(int $count = 2): Poker
    {
        $this->getPlayers()->each(function (PokerPlayer $player, $i) use ($count) {
            $n = $i + 1;

            $player->setPocketCards(Collection::times($count, function ($k) use ($n, $count) { // $k starts with 1
                return $this->getDeck()->dealCard($n + $count * ($k - 1));
            }));
        });

        return $this;
    }

    public function dealCommunityCards(int $count = 3): Poker
    {
        $this->communityCards = $this->getDeck()->dealCards($count, $this->getPocketCardsCount() + 1);

        $this->getPlayers()->each(function (PokerPlayer $player) {
            $player->setCommunityCards($this->communityCards);
        });

        return $this;
    }

    public function deal(int $pocketCardsCount, int $communityCardsCount): Poker
    {
        $this->dealPocketCards($pocketCardsCount);
        $this->dealCommunityCards($communityCardsCount);

        return $this;
    }

    public function play(): Poker
    {
        $this->getPlayers()->each(function (PokerPlayer $player) {
            $player->play();
        });

        return $this;
    }

    /**
     * calculate total number of pocket cards for all players
     *
     * @return int
     */
    protected function getPocketCardsCount(): int
    {
        return $this->getPlayers()->sum(function (PokerPlayer $player) {
            return $player->getPocketCards()->count();
        });
    }

    /**
     * Count community cards
     *
     * @return int
     */
    protected function getCommunityCardsCount(): int
    {
        return $this->getCommunityCards()->count();
    }

    protected function cardsAreDealt(): bool
    {
        return $this->getPocketCardsCount() > 0 || $this->getCommunityCardsCount() > 0;
    }
}
