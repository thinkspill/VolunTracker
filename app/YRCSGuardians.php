<?php

namespace App;

use Gbrock\Table\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\YRCSGuardians
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $first
 * @property string $last
 * @property string $relationship
 * @property string $family_hash_id
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereFirst($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereLast($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereRelationship($value)
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians whereFamilyHashId($value)
 * @property-read mixed $is_sortable
 * @method static \Illuminate\Database\Query\Builder|\App\YRCSGuardians sorted($field = false, $direction = false)
 */
class YRCSGuardians extends Model
{
    use Sortable;
    protected $table = 'yrcs_guardians';
    protected $fillable = ['first', 'last', 'relationship', 'family_hash_id'];
    protected $sortable = ['first', 'last', 'relationship', 'family_hash_id'];

    public function family()
    {
        return $this->hasOne('App\YRCSFamilies', 'family_hash_id', 'family_hash_id');
    }

}
