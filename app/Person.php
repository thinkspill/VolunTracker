<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Person
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $family_id
 * @property string $name
 * @property string $relationship
 * @property-read \App\Family $family
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TimeLog[] $hours
 * @method static \Illuminate\Database\Query\Builder|\App\Person whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Person whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Person whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Person whereFamilyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Person whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Person whereRelationship($value)
 */
class Person extends Model
{
    protected $fillable = ['name', 'family_id', 'relationship'];
    public function family()
    {
        return $this->hasOne('App\Family');
    }

    public function hours()
    {
        return $this->hasMany('App\TimeLog');
    }
}
