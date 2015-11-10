<?php

namespace App;

use Gbrock\Table\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Family
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Person[] $people
 * @property-read mixed $is_sortable
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Family sorted($field = false, $direction = false)
 */
class Family extends Model
{
    use Sortable;

    protected $sortable = ['surname', 'id'];
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
