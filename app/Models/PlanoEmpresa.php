<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanoEmpresa extends Model
{
    protected $table = 'plano_empresas'; // caso sua tabela não seja no singular

    protected $fillable = [
        'empresa_id',
        'plano_id',
        'data_expiracao',
        'valor',
    ];
}
