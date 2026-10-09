<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PartCrossReference extends Model
{
    use SoftDeletes;

    //

    public function sourcePart() {
        return $this->belongsTo(Part::class, 'source_part_id');
    }
}

