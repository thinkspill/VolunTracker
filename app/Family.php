<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Family
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Person[] $people
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereUpdatedAt($value)
 */
class Family extends Model
{
    protected $fillable = ['surname'];

    public function people()
    {
        return $this->hasMany('App\Person');
    }

    public function hours()
    {
        return $this->hasManyThrough('App\TimeLog', 'App\Person');
    }
}
