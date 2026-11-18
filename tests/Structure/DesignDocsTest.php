<?php

/*
| The design spec is read by the AI before it builds a page (DESIGN.md,
| docs/design/; docs/golden-principles.md, principle 10), so its index and its
| words are checked like code. The checks are in DesignDocChecks.php.
*/

use Tests\Structure\DesignDocChecks;

test('every pattern file in docs/design/patterns is in the DESIGN.md index and has its Sample or Demo section', function () {
    expect(DesignDocChecks::patternIndex())->toBe([]);
});

test('every file a pattern file names (a sample, a demo) exists: fix the path or add the file', function () {
    expect(DesignDocChecks::patternFiles())->toBe([]);
});

test('the design spec names tokens and component sizes, never a pixel value or a Tailwind size class: name the token or the size variant', function () {
    expect(DesignDocChecks::designValues())->toBe([]);
});
