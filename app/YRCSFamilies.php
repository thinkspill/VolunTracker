<?php

namespace App;

use Gbrock\Table\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\YRCSFamilies
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $family_hash
 * @property string $family_hash_id
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSFamilies whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSFamilies whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSFamilies whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSFamilies whereFamilyHash($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSFamilies whereFamilyHashId($value)
 * @property-read mixed $is_sortable
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSFamilies sorted($field = false, $direction = false)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TimeLog[] $hours
 */
class YRCSFamilies extends Model
{
    use Sortable;
    protected $table = 'yrcs_families';
    protected $fillable = ['family_hash_id', 'family_hash_id'];
    protected $sortable = ['family_hash_id'];

    public function hours()
    {
        return $this->hasMany('App\TimeLog', 'family_hash_id', 'family_hash_id');
    }

    public function guardians()
    {
        return $this->hasMany('App\YRCSGuardians', 'family_hash_id', 'family_hash_id');
    }

    public function students()
    {
        return $this->hasMany('App\YRCSStudents', 'family_hash_id', 'family_hash_id');
    }
}
