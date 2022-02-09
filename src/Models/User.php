<?php

namespace EscolaLms\Vouchers\Models;

use EscolaLms\Cart\Models\User as CartUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends CartUser
{
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class, 'user_id');
    }
}
