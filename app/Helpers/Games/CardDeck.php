<?php

namespace App\Helpers\Games;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class CardDeck implements Arrayable
{
    protected $deck;

    public function __construct($deck = NULL)
    {
        if ($deck) {
            $this->set($deck instanceof Collection ? $deck->toArray() : $deck);
        } else {
            $this->deck = collect();

            CardSuit::collection()->each(function ($suit) {
                CardValue::collection()->each(function ($value) use ($suit) {
                    $this->deck->push(new Card($suit . $value));
                });
            });

            $this->shuffle();
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->deck;
    }

    /**
     * Set card deck to a given deck
     *
     * @param array $deck
     * @return CardDeck
     */
    public function set(array $deck): CardDeck
    {
        $this->deck = collect(array_map(function ($code) {
            return new Card($code);
        }, $deck));

        return $this;
    }

    /**
     * Replace some cards in the deck (useful for testing)
     *
     * @param array $cards
     * @return CardDeck
     */
    public function replace(array $cards): CardDeck
    {
        $this->deck = $this->deck
            ->replace(collect($cards)->map(function ($card) {
                return new Card($card);
            }));

        return $this;
    }

    /**
     * Shuffle card deck
     *
     * @return CardDeck
     */
    public function shuffle(): CardDeck
    {
        $this->deck = $this->deck->shuffle(random_int(0, 999999));

        return $this;
    }

    /**
     * Cut N cards from the deck and append to the end
     *
     * @param $count
     * @return CardDeck
     */
    public function cut($count): CardDeck
    {
        // cards under cut index
        $cards = $this->deck->splice($count);

        $this->deck = $cards->concat($this->deck);

        return $this;
    }

    /**
     * Deal $n-th card from the deck
     *
     * @param $n
     * @return Card
     */
    public function dealCard($n): Card
    {
        return $this->deck->get($n - 1);
    }

    /**
     * Deal $count cards from the deck starting from $n-th card
     *
     * @param int $count
     * @param int $n
     * @return Collection
     */
    public function dealCards(int $count, int $n = 1): Collection
    {
        return $this->deck
            ->slice($n - 1, $count)
            ->values(); // it's important to call values(), because slice preserves the keys
    }

    /**
     * Get n-th card from the deck
     *
     * @param $n
     * @return mixed
     */
    public function getCard($n): string
    {
        return $this->dealCard($n)->code;
    }

    /**
     * Get count cards from the deck starting from n-th card
     *
     * @param int $count
     * @param int $n
     * @return array
     */
    public function getCards(int $count, int $n = 1): array
    {

        return $this->dealCards($count, $n)->map->code->all();
    }

    public function toArray()
    {
        return $this->deck->map->code->all();
    }
}
