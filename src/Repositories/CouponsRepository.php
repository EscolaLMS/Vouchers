<?php

namespace EscolaLms\Vouchers\Repositories;

use EscolaLms\Core\Repositories\BaseRepository;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Repositories\Contracts\CouponsRepositoryContract;

class CouponsRepository extends BaseRepository implements CouponsRepositoryContract
{
    public function model()
    {
        return Coupon::class;
    }

    public function getFieldsSearchable()
    {
        return [
            'name',
            'code',
            'active_from',
            'active_to',
        ];
    }
}
