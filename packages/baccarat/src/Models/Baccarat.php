<?php

namespace Packages\Baccarat\Models;

use App\Models\Gameable;
use App\Models\ProvableGame;
use Illuminate\Database\Eloquent\Model;

class Baccarat extends Model implements ProvableGame
{
    use Gameable;

    const BET_TYPE_PLAYER   = 0;
    const BET_TYPE_TIE      = 1;
    const BET_TYPE_BANKER   = 2;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'games_baccarat';

    /**
     * This format will be used when the model is serialized to an array or JSON.
     *
     * @var array
     */
    protected $casts = [
        'deck' => 'array',
        'player_hand' => 'array',
        'banker_hand' => 'array',
        'player_score' => 'integer',
        'banker_score' => 'integer',
    ];

    protected $appends = ['player_result', 'banker_result', 'bet_type_title', 'win_type', 'win_type_title' ];

    /**
     * Getter for player_result attribute
     *
     * @return string
     */
    public function getPlayerResultAttribute(): string
    {
        if ($this->player_score > $this->banker_score) {
            return __('Win');
        } else if ($this->player_score < $this->banker_score) {
            return __('Lose');
        } else {
            return __('Tie');
        }
    }

    /**
     * Getter for banker_result attribute
     *
     * @return string
     */
    public function getBankerResultAttribute(): string
    {
        if ($this->player_score < $this->banker_score) {
            return __('Win');
        } else if ($this->player_score > $this->banker_score) {
            return __('Lose');
        } else {
            return __('Tie');
        }
    }

    /**
     * Getter for win_type attribute
     *
     * @return int
     */
    public function getWinTypeAttribute(): int
    {
        if ($this->player_score > $this->banker_score) {
            return self::BET_TYPE_PLAYER;
        } else if ($this->player_score < $this->banker_score) {
            return self::BET_TYPE_BANKER;
        } else {
            return self::BET_TYPE_TIE;
        }
    }

    /**
     * Getter for bet_type_title attribute
     *
     * @return string
     */
    public function getBetTypeTitleAttribute(): string
    {
        return self::getBetTypes()[$this->bet_type];
    }

    /**
     * Getter for win_type_title attribute
     *
     * @return string
     */
    public function getWinTypeTitleAttribute(): string
    {
        return self::getBetTypes()[$this->win_type];
    }

    public static function getBetTypes(): array
    {
        return [
            self::BET_TYPE_PLAYER   => __('Player'),
            self::BET_TYPE_TIE      => __('Tie'),
            self::BET_TYPE_BANKER   => __('Banker'),
        ];
    }

    /**
     * Getter for secret_attribute attribute
     *
     * @return string
     */
    public function getSecretAttributeAttribute(): string
    {
        return 'deck';
    }

    /**
     * Getter for secret_attribute_hint attribute
     *
     * @return string
     */
    public function getSecretAttributeHintAttribute(): string
    {
        return __('The first N cards are removed from the top of the deck and placed under the remaining cards. N is the remainder of dividing the Shift value by 52.');
    }
}
