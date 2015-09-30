<?php

use App\Family;
use App\Person;
use Illuminate\Database\Seeder;

class People extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rels_parent = [
            'mother', 'father', 'grandmother', 'grandfather', 'aunt', 'uncle', 'cousin', 'guardian'
        ];

        $rels_child = [
            'son', 'daughter',
        ];

        $faker = Faker\Factory::create();
        $families = Family::all();
        foreach ($families as $family) {
            $guardians = rand(1, 3);
            $children = rand(1, 4);
            while ($guardians > 0) {

                $rel = $rels_parent[array_rand($rels_parent)];

                $data = [
                    'name' => $faker->firstName,
                    'relationship' => $rels_parent[array_rand($rels_parent)],
                    'family_id' => $family->id
                ];
                $f = Person::create($data);
                $f->save();
                $guardians--;
            }
            while ($children > 0) {
                $data = [
                    'name' => $faker->firstName,
                    'relationship' => $rels_child[array_rand($rels_child)],
                    'family_id' => $family->id
                ];
                $f = Person::create($data);
                $f->save();
                $children--;
            }
        }
    }
}
