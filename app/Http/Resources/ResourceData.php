<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * What one record looks like on a page. A Resource extends this, lists its
 * fields in its constructor, and builds itself from one record for one viewer
 * in from().
 */
abstract class ResourceData extends ResourceShape
{
    abstract public static function from(Model $record, ?User $viewer): static;
}
