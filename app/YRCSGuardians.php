<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\YRCSGuardians.
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $family_id
 * @property string $first
 * @property string $last
 * @property string $relationship
 * @property-read \App\YRCSFamilies $family
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\YRCSStudents[] $students
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereFamilyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereFirst($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereLast($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereRelationship($value)
 */
class YRCSGuardians extends Model
{
    protected $table = 'yrcs_guardians';
    protected $fillable = ['first', 'last', 'relationship', 'family_id'];

    public function family()
    {
        return $this->belongsTo('App\YRCSFamilies', 'family_id');
    }

    public function students()
    {
        return $this->belongsToMany('App\YRCSStudents', 'yrcs_students_to_guardians', 'guardian_id', 'student_id');
    }
}
