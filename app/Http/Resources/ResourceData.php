<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The Resource part: what one record looks like on a page. A
 * Resource extends this, lists its fields in its constructor, and builds
 * itself from one record for one viewer in from(). The listing part, the
 * record part and the shared data call from(); nothing else turns a record
 * into page data.
 */
abstract class ResourceData extends ResourceShape
{
    abstract public static function from(Model $record, ?User $viewer): static;
}
