<?php

$factory('Meiko\Patchable\Tests\Models\User', [
    'name' => $faker->name,
    'email' => $faker->email,
]);

$factory('Meiko\Patchable\Tests\Models\Project', [
    'title' => $faker->company,
    'client_id' => 'factory:Meiko\Patchable\Tests\Models\Client',
    'user_id' => 'factory:Meiko\Patchable\Tests\Models\User',
]);

$factory('Meiko\Patchable\Tests\Models\Client', [
    'title' => $faker->company,
    'user_id' => 'factory:Meiko\Patchable\Tests\Models\User',
]);
