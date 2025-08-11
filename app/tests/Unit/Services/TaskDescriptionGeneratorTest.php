<?php

namespace Tests\Unit\Services;

use App\Services\TaskDescriptionGenerator\StubDescriptionGenerator;
use Tests\TestCase;

class TaskDescriptionGeneratorTest extends TestCase
{
    public function test_stub_generator_returns_basic_description()
    {
        $generator = new StubDescriptionGenerator();

        $differenceData = [
            'new_version_id' => 1,
            'new_version_title' => 'Test Page',
            'is_new_page' => true,
        ];

        $description = $generator->generateDescription($differenceData);

        $this->assertStringContainsString('Task created from version difference', $description);
        $this->assertStringContainsString('Test Page', $description);
    }

    public function test_stub_generator_handles_empty_data()
    {
        $generator = new StubDescriptionGenerator();

        $differenceData = [];

        $description = $generator->generateDescription($differenceData);

        $this->assertStringContainsString('Task created from version difference', $description);
        $this->assertStringContainsString('Type: Page updated', $description);
    }

    public function test_stub_generator_handles_complex_data()
    {
        $generator = new StubDescriptionGenerator();

        $differenceData = [
            'new_version_id' => 2,
            'new_version_title' => 'Updated Page',
            'new_version_content' => 'New content',
            'old_version_id' => 1,
            'old_version_title' => 'Old Page',
            'old_version_content' => 'Old content',
            'title_changed' => true,
            'content_changed' => true,
            'is_new_page' => false,
        ];

        $description = $generator->generateDescription($differenceData);

        $this->assertStringContainsString('Task created from version difference', $description);
        $this->assertStringContainsString('Updated Page', $description);
        $this->assertStringContainsString('Type: Page updated', $description);
    }

    public function test_stub_generator_implements_interface()
    {
        $generator = new StubDescriptionGenerator();

        $this->assertInstanceOf(
            \App\Interfaces\TaskDescriptionGeneratorInterface::class,
            $generator
        );
    }
}
