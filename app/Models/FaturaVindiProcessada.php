<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaturaVindiProcessada extends Model
{
    protected $table = 'faturas_vindi_processadas';

    protected $fillable = [
        'fatura_id',
    ];
}
