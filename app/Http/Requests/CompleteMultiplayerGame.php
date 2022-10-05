<?php

namespace App\Http\Requests;

use App\Models\MultiplayerGame;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class CompleteMultiplayerGame extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->multiplayerGame instanceof MultiplayerGame && $this->multiplayerGame->end_time->lt(Carbon::now());
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
}
