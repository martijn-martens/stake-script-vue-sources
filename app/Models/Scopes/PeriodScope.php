<?php

namespace App\Models\Scopes;

use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Builder;

trait PeriodScope
{
    public function scopePeriod(Builder $query, ?string $period): Builder
    {
        $column = $this->getTable() . '.created_at';

        return $query->when($period, function (Builder $query, ?string $period) use ($column) {
            $query->whereBetween($column, Utils::getDateRange($period));
        });
    }
}
