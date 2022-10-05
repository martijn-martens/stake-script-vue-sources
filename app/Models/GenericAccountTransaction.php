<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenericAccountTransaction extends Model
{
    use StandardDateFormat;

    const TYPE_DEBIT = 1;
    const TYPE_CREDIT = 2;
    const TYPE_AFFILIATE_COMMISSION = 3;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'title'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction()
    {
        return $this->morphOne(AccountTransaction::class, 'transactionable');
    }

    public function getTitleAttribute()
    {
        if ($this->type == self::TYPE_DEBIT) {
            return __('Debit');
        } elseif ($this->type == self::TYPE_CREDIT) {
            return __('Credit');
        } elseif ($this->type == self::TYPE_AFFILIATE_COMMISSION) {
            return __('Affiliate commission');
        }
    }
}
