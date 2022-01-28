<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DocumentFolder extends Model
{
    protected $guarded = [];

    public function documents(){
        return $this->hasMany('App\DocumentUpload');
    }
}
