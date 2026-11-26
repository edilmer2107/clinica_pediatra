<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $guarded = ['id'];

    public const DOMINIOS = [
        'motricidad_gruesa' => 'Motricidad gruesa',
        'motricidad_fina' => 'Motricidad fina',
        'lenguaje' => 'Lenguaje',
        'social' => 'Social',
    ];
}
