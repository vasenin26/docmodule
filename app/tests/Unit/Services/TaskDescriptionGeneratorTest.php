<?php

namespace Tests\Unit\Services;

use App\Common\DTO\DifferenceDataDTO;
use App\Services\TaskDescriptionGenerator\StubDescriptionGenerator;
use Tests\TestCase;

class TaskDescriptionGeneratorTest extends TestCase
{
    public function test_stub_generator_returns_basic_description()
    {
        $generator = new StubDescriptionGenerator();

        $differenceData = new DifferenceDataDTO(
            newVersionTitle: 'Test Page',
            isNewPage: true
        );

        $result = $generator->generateDescription($differenceData);

        $this->assertStringContainsString('Task created from version difference', $result->result);
        $this->assertStringContainsString('Test Page', $result->result);
    }

    public function test_stub_generator_handles_empty_data()
    {
        $generator = new StubDescriptionGenerator();

        $differenceData = new DifferenceDataDTO();

        $result = $generator->generateDescription($differenceData);

        $this->assertStringContainsString('Task created from version difference', $result->result);
        $this->assertStringContainsString('Type: Page updated', $result->result);
    }

    public function test_stub_generator_handles_complex_data()
    {
        $generator = new StubDescriptionGenerator();

        $differenceData = new DifferenceDataDTO(
            newVersionTitle: 'Updated Page',
            isNewPage: false,
            titleChanged: true,
            contentChanged: true
        );

        $result = $generator->generateDescription($differenceData);

        $this->assertStringContainsString('Task created from version difference', $result->result);
        $this->assertStringContainsString('Updated Page', $result->result);
        $this->assertStringContainsString('Type: Page updated', $result->result);
    }

    public function test_stub_generator_implements_interface()
    {
        $generator = new StubDescriptionGenerator();

        $this->assertInstanceOf(
            \App\Interfaces\ContentGenerator\TaskDescriptionGeneratorInterface::class,
            $generator
        );
    }

    public function test_dto_from_array_conversion()
    {
        $arrayData = [
            'diff_output' => 'test diff',
            'new_version_title' => 'Test Page',
            'is_new_page' => true,
            'added_lines' => ['line1', 'line2'],
            'removed_lines' => ['old_line'],
            'title_changed' => true,
            'content_changed' => false
        ];

        $dto = DifferenceDataDTO::fromArray($arrayData);

        $this->assertEquals('test diff', $dto->diffOutput);
        $this->assertEquals('Test Page', $dto->newVersionTitle);
        $this->assertTrue($dto->isNewPage);
        $this->assertEquals(['line1', 'line2'], $dto->addedLines);
        $this->assertEquals(['old_line'], $dto->removedLines);
        $this->assertTrue($dto->titleChanged);
        $this->assertFalse($dto->contentChanged);
    }

    public function test_dto_to_array_conversion()
    {
        $dto = new DifferenceDataDTO(
            diffOutput: 'test diff',
            newVersionTitle: 'Test Page',
            isNewPage: true,
            addedLines: ['line1', 'line2'],
            removedLines: ['old_line'],
            titleChanged: true,
            contentChanged: false
        );

        $arrayData = $dto->toArray();

        $this->assertEquals('test diff', $arrayData['diff_output']);
        $this->assertEquals('Test Page', $arrayData['new_version_title']);
        $this->assertTrue($arrayData['is_new_page']);
        $this->assertEquals(['line1', 'line2'], $arrayData['added_lines']);
        $this->assertEquals(['old_line'], $arrayData['removed_lines']);
        $this->assertTrue($arrayData['title_changed']);
        $this->assertFalse($arrayData['content_changed']);
    }
}
