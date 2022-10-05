<?php

namespace Packages\Baccarat\Services;

use App\Helpers\Games\CardDeck;
use App\Models\Game;
use App\Models\User;
use App\Services\GameService as ParentGameService;
use Packages\Baccarat\Models\Baccarat;

class GameService extends ParentGameService
{
    protected $gameableClass = Baccarat::class;

    protected $deck;

    public function __construct(User $user)
    {
        parent::__construct($user);

        $this->deck = new CardDeck();
    }

    public function makeSecret(): string
    {
        return implode(',', $this->deck->toArray());
    }

    /**
     * Deal initial set of cards
     *
     * @param $params
     */
    public function play($params): GameService
    {
        $provablyFairGame = $this->getProvablyFairGame();

        // load initially shuffled deck
        $this->deck->set(explode(',', $provablyFairGame->secret));
        // cut the deck (provably fair)
        $this->deck->cut($provablyFairGame->shift_value % 52);

        $gameable = new $this->gameableClass();
        $gameable->deck = $this->deck->toArray();
        $gameable->bet_type = $params['bet_type']; // bet amount in credits
        $gameable->player_hand = [$this->deck->getCard(1), $this->deck->getCard(3)];
        $gameable->banker_hand = [$this->deck->getCard(2), $this->deck->getCard(4)];

//        TESTING
//        -------
//        TIE (2 CARDS)
//        $gameable->player_hand = ['S6','H2'];
//        $gameable->banker_hand = ['D5','D3'];
//
//        ---------------------------------------------------------------------

        $gameable->player_score = $this->calculateHandScore($gameable->player_hand);
        $gameable->banker_score = $this->calculateHandScore($gameable->banker_hand);

        // important to save a reference to gameable in the class property, so it can be used in the parent class
        $this->gameable = $gameable;

        //  If neither hand has eight or nine, the drawing rules are applied to determine whether the player should receive a third card.
        if ($gameable->player_score < 8 && $gameable->banker_score < 8) {
            // If the player has total of 5 or less, the player automatically hits
            if ($gameable->player_score <= 5) {
                $gameable->player_hand = array_merge($gameable->player_hand, [$this->deck->getCard(5)]);
            }

            // If the player stands, the banker hits on a total of 5 or less.
            if (($gameable->player_score == 6 || $gameable->player_score == 7) && $gameable->banker_score <= 5) {
                $gameable->banker_hand = array_merge($gameable->banker_hand, [$this->deck->getCard(5)]);
            // If the player gets the third card then the banker draws a third card according to the following rules:
            } elseif (count($gameable->player_hand) == 3) {
                // Banker has total of 0, 1, 2: Banker always draws a third card.
                if ($gameable->banker_score <= 2
                    // If the banker total is 3, then the banker draws a third card unless the player's third card was an 8.
                    || $gameable->banker_score == 3 && $gameable->player_hand[2][1] != 8
                    // If the banker total is 4, then the banker draws a third card if the player's third card was 2, 3, 4, 5, 6, 7.
                    || $gameable->banker_score == 4 && in_array((int)$gameable->player_hand[2][1], [2, 3, 4, 5, 6, 7])
                    // If the banker total is 5, then the banker draws a third card if the player's third card was 4, 5, 6, or 7.
                    || $gameable->banker_score == 5 && in_array((int)$gameable->player_hand[2][1], [4, 5, 6, 7])
                    // If the banker total is 6, then the banker draws a third card if the player's third card was a 6 or 7.
                    || $gameable->banker_score == 6 && in_array((int)$gameable->player_hand[2][1], [6, 7])) {
                    $gameable->banker_hand = array_merge($gameable->banker_hand, [$this->deck->getCard(6)]);
                }
            }
        }

        $gameable->player_score = $this->calculateHandScore($gameable->player_hand);
        $gameable->banker_score = $this->calculateHandScore($gameable->banker_hand);

        $bet = $params['bet'];
        $win = 0;

        if ($gameable->player_score > $gameable->banker_score && $params['bet_type'] == Baccarat::BET_TYPE_PLAYER) {
            $win = $bet * (float)config('baccarat.payouts.player');
        } elseif ($gameable->player_score < $gameable->banker_score && $params['bet_type'] == Baccarat::BET_TYPE_BANKER) {
            $win = $bet * (float)config('baccarat.payouts.banker');
        } elseif ($gameable->player_score == $gameable->banker_score && $params['bet_type'] == Baccarat::BET_TYPE_TIE) {
            $win = $bet * (float)config('baccarat.payouts.tie');
        }

        $this->save([
            'bet' => $bet,
            'win' => $win,
            'status' => Game::STATUS_COMPLETED
        ]);

        return $this;
    }

    /**
     * Calculate hand score
     *
     * @param array $hand
     * @return float
     */
    protected function calculateHandScore(array $hand): float
    {
        $score = 0;

        $getCardScore = function ($cardValue) {
            if (intval($cardValue) > 0)
                return intval($cardValue);
            // aces have score of 1
            elseif ($cardValue == 'A')
                return 1;
            //  jack, queen, and king are worth zero
            else
                return 0;
        };

        // loop through cards and calculate score
        foreach ($hand as $card) {
            $score += $getCardScore(substr($card, 1, 1));
        }

        // if score is >= 10 return only the right digit
        return $score < 10 ? $score : (int) substr($score, 1, 1);
    }

    public static function createRandomGame(User $user): void
    {
        $instance = new self($user);

        $instance->createProvablyFairGame(random_int(10000,99999));

        $minBet = intval(config('settings.bots.games.min_bet'));
        $maxBet = intval(config('settings.bots.games.max_bet'));

        $instance->play([
            'bet_type'  => random_int(0, 2),
            'bet'       => random_int($minBet ?: config('baccarat.min_bet'), $maxBet ?: config('baccarat.max_bet')),
        ]);
    }
}
