<?php

namespace Meiko\Patchable\Tests;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Meiko\Patchable\Tests\Models\User;
use Meiko\Patchable\Tests\Models\Project;
use Meiko\Patchable\Tests\Models\Client;
use Laracasts\TestDummy\Factory;

class HasManyTest extends TestCase
{
    public function testHasManyRelationshipsArePatched()
    {
        $user = Factory::create(User::class);
        $project = Factory::create(Project::class);

        $patchData = [
            'id' => $user->id,
            'type' => 'user',
            'relationships' => [
                'projects' => [
                    'data' => [
                        [
                            'id' => $project->id,
                            'type' => 'project',
                        ]
                    ],
                ],
            ],
        ];

        $user->patch($patchData);

        $this->assertEquals($project->id, $user->projects()->first()->id);
    }

    public function testHasManyRelationshipsAreCleared()
    {
        $user = Factory::create(User::class);
        $projects = Factory::times(3)->create(Project::class);

        // set projects to user
        $user->projects()->saveMany($projects);

        $patchData = [
            'id' => $user->id,
            'type' => 'user',
            'relationships' => [
                'projects' => [
                    'data' => [],
                ],
            ],
        ];

        $user->patch($patchData);

        $this->assertEquals(0, $user->projects()->count());
    }
}
