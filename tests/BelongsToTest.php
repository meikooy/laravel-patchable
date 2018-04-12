<?php

namespace Meiko\Patchable\Tests;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Meiko\Patchable\Tests\Models\User;
use Meiko\Patchable\Tests\Models\Project;
use Meiko\Patchable\Tests\Models\Client;
use Laracasts\TestDummy\Factory;

class BelongsToTest extends TestCase
{
    public function testBelongsToRelationshipIsPatched()
    {
        $user = Factory::create(User::class);
        $project = Factory::create(Project::class);

        $patchData = [
            'id' => $project->id,
            'type' => 'project',
            'relationships' => [
                'user' => [
                    'data' => [
                        'id' => $user->id,
                        'type' => 'user',
                    ],
                ],
            ],
        ];

        $project->patch($patchData);

        $this->assertEquals($user->id, $project->user->id);
    }

    public function testBelongsToRelationshipIsDeleted()
    {
        $project = Factory::create(Project::class);

        $patchData = [
            'id' => $project->id,
            'type' => 'project',
            'relationships' => [
                'user' => [
                    'data' => [],
                ],
            ],
        ];

        $project->patch($patchData);

        $this->assertNull($project->user);
    }
}
