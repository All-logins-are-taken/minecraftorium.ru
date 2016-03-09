<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Specie extends Model
{
    //

    public function genus(){
        return $this->belongsTo('\App\Genus');
    }
}
