<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servico extends Model {
    public function itens(){
        return $this->hasMany(ItemNotaServico::class, 'servico_id');
    }
}
