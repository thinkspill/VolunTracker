<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\YubaRiver.
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $student_last
 * @property string $student_first
 * @property string $parent_last
 * @property string $parent_first
 * @property string $grade
 * @property string $relationship
 * @property string $email
 * @property string $phone
 * @property string $child_lives_with
 * @property string $city
 * @property string $state
 * @property string $address
 * @property string $zip
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereStudentLast($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereStudentFirst($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereParentLast($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereParentFirst($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereGrade($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereRelationship($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver wherePhone($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereChildLivesWith($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereCity($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereState($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereAddress($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereZip($value)
 */
class YubaRiver extends Model
{
    protected $table = 'yuba_river';
    protected $fillable = ['student_first', 'student_last', 'parent_first', 'parent_last', 'grade', 'relationship', 'child_lives_with', 'city', 'state', 'zip', 'address'];
}
