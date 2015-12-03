<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\YRCSStudents
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $family_id
 * @property string $first
 * @property string $last
 * @property-read \App\YRCSFamilies $family
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\YRCSGuardians[] $guardians
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereFamilyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereFirst($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereLast($value)
 */
class YRCSStudents extends Model
{
    protected $table = 'yrcs_students';
    protected $fillable = ['first', 'last'];

    public function family()
    {
        return $this->belongsTo('App\YRCSFamilies', 'family_id');
    }

    public function guardians()
    {
        return $this->belongsToMany('App\YRCSGuardians', 'yrcs_students_to_guardians', 'student_id', 'guardian_id');
    }
}
