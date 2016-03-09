<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpeciePhoto extends Model
{
    //

    protected $table = 'species_photos';


    public function specie(){
        return $this->belongsTo('\App\Specie');
    }
}
