<?php

declare(strict_types=1);

namespace App\Tests\Tools\PHPStan;

use App\Tools\PHPStan\CatchReturnsDefaultRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * RUL-CODE-fail-fast: a catch block that returns a literal default hides the failure. No ready-made rule
 * covers it (change finish-init, group 6, design.md cards); this project rule does.
 *
 * @extends RuleTestCase<CatchReturnsDefaultRule>
 */
#[Group('internal')]
final class CatchReturnsDefaultRuleTest extends RuleTestCase
{
    public function testReportsCatchBlocksReturningALiteralAndNothingElse(): void
    {
        $this->analyse([__DIR__.'/Fixtures/CatchReturnsDefault.php'], [
            ['Catch block returns a default value (null); rethrow or let it fail (RUL-CODE-fail-fast).', 14],
            ['Catch block returns a default value (false); rethrow or let it fail (RUL-CODE-fail-fast).', 24],
            ['Catch block returns a default value ([]); rethrow or let it fail (RUL-CODE-fail-fast).', 26],
            ['Catch block returns a default value (0); rethrow or let it fail (RUL-CODE-fail-fast).', 28],
            ["Catch block returns a default value (''); rethrow or let it fail (RUL-CODE-fail-fast).", 30],
            ['Catch block returns a default value (null); rethrow or let it fail (RUL-CODE-fail-fast).', 41],
        ]);
    }

    protected function getRule(): Rule
    {
        return new CatchReturnsDefaultRule();
    }
}
