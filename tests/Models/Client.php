<?php

namespace Meiko\Patchable\Tests\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Meiko\Patchable\Patchable;

class Client extends EloquentModel
{
    use Patchable;

    protected $fillable = [
        'title',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }
}
