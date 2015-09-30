<?php

use App\Family;
use Illuminate\Database\Seeder;

class Families extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $i = 250;
        while ($i > 0) {
            $f = Family::create(['surname' => $faker->lastName]);
            $f->save();
            $i--;
        }
    }
}
