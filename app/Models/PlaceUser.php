<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Lien entre un utilisateur et un lieu qu'il suit.
 */
class PlaceUser extends Pivot
{
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'send_forecast' => 'boolean',
        ];
    }
}
