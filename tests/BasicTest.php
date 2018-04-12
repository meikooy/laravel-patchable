<?php

namespace Meiko\Patchable\Tests;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Meiko\Patchable\Tests\Models\User;
use Meiko\Patchable\Tests\Models\Project;
use Meiko\Patchable\Tests\Models\Client;
use Laracasts\TestDummy\Factory;

class BasicTest extends TestCase
{
    public function testAttributesArePatched()
    {
        $user = Factory::create(User::class, [
            'name' => 'Jane Doe',
        ]);

        $patchData = [
            'id' => $user->id,
            'type' => 'user',
            'attributes' => [
                'name' => 'John Doe',
            ],
        ];

        $user->patch($patchData);

        $this->assertEquals('John Doe', $user->name);
    }

    public function testInvalidTypeIsCatched()
    {
        $this->expectException(ConflictHttpException::class);

        $user = Factory::create(User::class);

        $patchData = [
            'id' => $user->id,
            'type' => 'helicopter',
        ];

        $user->patch($patchData);
    }
}
