<?php

namespace App;

use Gbrock\Table\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\TimeLog
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $person_id
 * @property string $date
 * @property boolean $hours
 * @property-read \App\Person $person
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog wherePersonId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereHours($value)
 * @property integer $family_id
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereFamilyId($value)
 * @property string $family_hash_id
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereFamilyHashId($value)
 * @property-read mixed $is_sortable
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog sorted($field = false, $direction = false)
 */
class TimeLog extends Model
{
    use Sortable;
    protected $fillable = ['person_id', 'date', 'hours', 'family_id', 'family_hash_id'];
    protected $sortable = ['person_id', 'date', 'hours', 'family_id', 'family_hash_id'];

    public function person()
    {
        return $this->hasOne('App\Person');
    }
}
