<?php

namespace Meiko\Patchable\Tests;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Meiko\Patchable\Tests\Models\User;
use Meiko\Patchable\Tests\Models\Project;
use Meiko\Patchable\Tests\Models\Client;
use Laracasts\TestDummy\Factory;

class BelongsToManyTest extends TestCase
{
    public function testBelongsToManyRelationshipsArePatched()
    {
        $users = Factory::times(3)->create(User::class);
        $project = Factory::create(Project::class);

        $patchData = [
            'id' => $project->id,
            'type' => 'project',
            'relationships' => [
                'users' => [
                    'data' => [],
                ],
            ],
        ];

        foreach ($users as $user) {
            $patchData['relationships']['users']['data'][] = [
                'id' => $user->id,
                'type' => 'user',
            ];
        }

        $project->patch($patchData);

        $this->assertTrue($project->users()->count() === count($users));
    }

    public function testBelongsToManyRelationshipsAreCleared()
    {
        $users = Factory::times(3)->create(User::class);
        $project = Factory::create(Project::class);

        // set project users
        $userIds = [];
        foreach ($users as $user) {
            $userIds[] = $user->id;
        }
        $project->users()->sync($userIds);

        $patchData = [
            'id' => $project->id,
            'type' => 'project',
            'relationships' => [
                'users' => [
                    'data' => [],
                ],
            ],
        ];

        $project->patch($patchData);

        $this->assertEquals(0, $project->users()->count());
    }
}
