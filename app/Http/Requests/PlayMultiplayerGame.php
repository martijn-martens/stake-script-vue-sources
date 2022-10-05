<?php

namespace App\Http\Requests;

use App\Models\MultiplayerGame;
use App\Models\User;
use App\Rules\BalanceIsSufficient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class PlayMultiplayerGame extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->multiplayerGame instanceof MultiplayerGame
            && $this->multiplayerGame->start_time->lte(Carbon::now())
            && $this->multiplayerGame->end_time->gte(Carbon::now());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    protected function validateBalance($validator, User $user, int $bet)
    {
        $validator->after(function ($validator) use ($user, $bet) {
            $rule = new BalanceIsSufficient($user, $bet);

            if (!$rule->passes()) {
                $validator->errors()->add('balance', $rule->message());
            }
        });
    }
}
