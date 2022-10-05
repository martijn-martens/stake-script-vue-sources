<?php

namespace Packages\Baccarat\Http\Requests;

use App\Http\Requests\PlayGame;
use Illuminate\Validation\Rule;
use Packages\Baccarat\Models\Baccarat;

class Play extends PlayGame
{
    protected $gamePackageId = 'baccarat';
    protected $gameableClass = Baccarat::class;

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                'bet_type' => [
                    'required',
                    'integer',
                    Rule::in(array_keys(Baccarat::getBetTypes()))
                ]
            ]
        );
    }
}
