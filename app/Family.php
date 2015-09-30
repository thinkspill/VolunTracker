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
 * @property string $surname
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Person[] $members
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereSurname($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Person[] $people
 * @property-read mixed $is_sortable
 * @method static \Illuminate\Database\Query\Builder|\App\Family sorted($field = false, $direction = false)
 * @property string $familyHash
 * @property string $familyHashId
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereFamilyHash($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Family whereFamilyHashId($value)
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
