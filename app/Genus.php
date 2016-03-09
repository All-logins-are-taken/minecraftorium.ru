<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Genus extends Model
{
    //

    public function species(){
        return $this->hasMany('\App\Specie');
    }

    public function children()
    {
        return $this->hasMany('\App\Genus', 'parent_id');
    }

    public function getAncestors($id)
    {
        $location = Genus::find($id);
        $genera = $location->children->count();
        $ancestors = [];

        foreach($location->children as $genus)
        {
            $ancestors[] = $genus->species->count();
        }

        return 'Число родОв семейства: '.$genera.'<br />Количество видов: '.array_sum($ancestors);
    }
}
