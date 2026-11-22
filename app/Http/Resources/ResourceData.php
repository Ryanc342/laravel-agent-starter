<?php

namespace App\Http\Resources;

/**
 * What one record looks like on a page. A Resource extends this, lists its
 * fields in its constructor, and builds itself from one record for one viewer
 * in a static from(<its model> $record, ?User $viewer).
 */
abstract class ResourceData extends ResourceShape {}
