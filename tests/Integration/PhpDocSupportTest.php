<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PHPDoc attribute support in generated TypeScript
 */
class PhpDocSupportTest extends TestCase
{
    private PhpToTsGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PhpToTsGenerator();
    }

    public function testVarTagPreservedInTSDoc(): void
    {
        // Given a class with @var PHPDoc
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithVarDoc {
    /**
     * @var string The user's email address
     */
    public string $email;

    /**
     * User's age in years
     * @var int
     */
    public int $age;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithVarDoc');

        // Then @var docs should be preserved as TSDoc comments
        $this->assertStringContainsString('The user\'s email address', $typescript);
        $this->assertStringContainsString('User\'s age in years', $typescript);
    }

    public function testDeprecatedTagPreserved(): void
    {
        // Given a class with @deprecated properties
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithDeprecated {
    /**
     * @deprecated Use newField instead
     */
    public string $oldField;

    public string $newField;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithDeprecated');

        // Then @deprecated should be preserved as TSDoc
        $this->assertStringContainsString('@deprecated', $typescript);
        $this->assertStringContainsString('Use newField instead', $typescript);
    }

    public function testSeeTagPreserved(): void
    {
        // Given a class with @see references
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithSee {
    /**
     * @see https://example.com/docs
     */
    public string $documentedField;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithSee');

        // Then @see should be preserved
        $this->assertStringContainsString('@see', $typescript);
        $this->assertStringContainsString('https://example.com/docs', $typescript);
    }

    public function testLinkTagPreserved(): void
    {
        // Given a class with @link
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithLink {
    /**
     * Configuration URL
     * @link https://config.example.com
     */
    public string $configUrl;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithLink');

        // Then @link should be preserved
        $this->assertStringContainsString('Configuration URL', $typescript);
        $this->assertStringContainsString('@link', $typescript);
    }

    public function testExampleTagPreserved(): void
    {
        // Given a class with @example
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithExample {
    /**
     * User status code
     * @example "active"
     */
    public string $status;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithExample');

        // Then @example should be preserved
        $this->assertStringContainsString('User status code', $typescript);
        $this->assertStringContainsString('@example', $typescript);
    }

    public function testMultipleTagsPreserved(): void
    {
        // Given a class with multiple PHPDoc tags
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithMultipleTags {
    /**
     * Legacy user identifier
     * @var int
     * @deprecated Use userId instead
     * @see UserDTO::$userId
     */
    public int $legacyId;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithMultipleTags');

        // Then all relevant tags should be preserved
        $this->assertStringContainsString('Legacy user identifier', $typescript);
        $this->assertStringContainsString('@deprecated', $typescript);
        $this->assertStringContainsString('@see', $typescript);
    }

    public function testArrayVarTagWithDescription(): void
    {
        // Given a class with @var array tags with descriptions
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithArrayDoc {
    /**
     * List of user email addresses
     * @var string[]
     */
    public array $emails;

    /**
     * @var int[] User IDs from external system
     */
    public array $externalIds;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithArrayDoc');

        // Then descriptions should be preserved
        $this->assertStringContainsString('List of user email addresses', $typescript);
        $this->assertStringContainsString('User IDs from external system', $typescript);

        // And array types should be correct
        $this->assertStringContainsString('emails: string[]', $typescript);
        $this->assertStringContainsString('externalIds: number[]', $typescript);
    }

    public function testAuthorTagNotPreserved(): void
    {
        // Given a class with @author tag (not relevant for TypeScript)
        $php = <<<'PHP'
<?php
namespace Test;
/**
 * @author John Doe
 */
class DataWithAuthor {
    public string $field;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithAuthor');

        // Then @author should NOT appear in generated code
        $this->assertStringNotContainsString('@author', $typescript);
        $this->assertStringNotContainsString('John Doe', $typescript);
    }
}
