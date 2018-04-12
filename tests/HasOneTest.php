<?php

namespace Meiko\Patchable\Tests;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Meiko\Patchable\Tests\Models\User;
use Meiko\Patchable\Tests\Models\Project;
use Meiko\Patchable\Tests\Models\Client;
use Laracasts\TestDummy\Factory;

class HasOneTest extends TestCase
{
    public function testHasOneRelationshipsArePatched()
    {
        $user = Factory::create(User::class);
        $client = Factory::create(Client::class);

        $patchData = [
            'id' => $user->id,
            'type' => 'user',
            'relationships' => [
                'client' => [
                    'data' => [
                        'id' => $client->id,
                        'type' => 'client',
                    ],
                ],
            ],
        ];

        $user->patch($patchData)->refresh();

        $this->assertEquals($client->id, $user->client->id);
    }

    public function testHasOneRelationshipsAreCleared()
    {
        $user = Factory::create(User::class);
        $client = Factory::create(Client::class);

        // set client to user
        $user->client()->save($client);

        $patchData = [
            'id' => $user->id,
            'type' => 'user',
            'relationships' => [
                'client' => [
                    'data' => [],
                ],
            ],
        ];

        $user->patch($patchData)->refresh();

        $this->assertNull($user->client);
    }
}
