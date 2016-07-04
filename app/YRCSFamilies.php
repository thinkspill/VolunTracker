<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\YRCSFamilies.
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TimeLog[] $hours
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\YRCSGuardians[] $guardians
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\YRCSStudents[] $students
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSFamilies whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSFamilies whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSFamilies whereUpdatedAt($value)
 */
class YRCSFamilies extends Model
{
    protected $table = 'yrcs_families';
    protected $fillable = ['family_id'];

    public function hours()
    {
        return $this->hasMany('App\TimeLog', 'family_id');
    }

    public function guardians()
    {
        return $this->hasMany('App\YRCSGuardians', 'family_id');
    }

    public function students()
    {
        return $this->hasMany('App\YRCSStudents', 'family_id');
    }
}
