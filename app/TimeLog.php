<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TimeLog
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $family_id
 * @property string $date
 * @property integer $hours
 * @property-read \App\YRCSFamilies $family
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereFamilyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\TimeLog whereHours($value)
 */
class TimeLog extends Model
{
    protected $fillable = ['person_id', 'date', 'hours', 'family_id'];

    public function family()
    {
        return $this->belongsTo('App\YRCSFamilies', 'family_id');
    }
}
