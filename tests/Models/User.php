<?php

namespace Meiko\Patchable\Tests\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Meiko\Patchable\Patchable;

class User extends EloquentModel
{
    use Patchable;

    protected $fillable = [
        'name',
        'email',
    ];

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
