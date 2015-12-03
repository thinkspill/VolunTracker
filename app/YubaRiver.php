<?php

namespace App;

use Gbrock\Table\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\YubaRiver
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $student_last
 * @property string $student_first
 * @property string $parent_last
 * @property string $parent_first
 * @property string $grade
 * @property string $relationship
 * @property string $city
 * @property-read mixed $is_sortable
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereStudentLast($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereStudentFirst($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereParentLast($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereParentFirst($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereGrade($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereRelationship($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver whereCity($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YubaRiver sorted($field = false, $direction = false)
 */
class YubaRiver extends Model
{
    use Sortable;
    protected $table = 'yuba_river';
    protected $fillable = ['student_first', 'student_last', 'parent_first', 'parent_last', 'grade', 'relationship', 'child_lives_with', 'city', 'state', 'zip', 'address'];
    protected $sortable = ['student_first', 'student_last', 'parent_first', 'parent_last', 'grade', 'relationship'];
}
