<?php

namespace App;

use Gbrock\Table\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\YRCSStudents
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $first
 * @property string $last
 * @property string $family_hash_id
 * @property-read mixed $is_sortable
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereFirst($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereLast($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents whereFamilyHashId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSStudents sorted($field = false, $direction = false)
 */
class YRCSStudents extends Model
{
    use Sortable;
    protected $table = 'yrcs_students';
    protected $fillable = ['first', 'last', 'family_hash_id'];
    protected $sortable = ['first', 'last', 'family_hash_id'];

    public function family()
    {
        return $this->hasOne('App\YRCSFamilies', 'family_hash_id', 'family_hash_id');
    }
}
