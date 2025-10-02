<?php

namespace Tests\Unit\DTO;

use App\Common\DTO\Page\PageDataDTO;
use Tests\TestCase;

class PageDataDTOTest extends TestCase
{
    public function test_constructor_sets_properties_correctly()
    {
        // Arrange & Act
        $dto = new PageDataDTO(
            title: 'Test Title',
            content: 'Test Content',
            files: ['test.pdf', 'image.jpg']
        );

        // Assert
        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('Test Content', $dto->content);
        $this->assertEquals(['test.pdf', 'image.jpg'], $dto->files);
    }

    public function test_constructor_uses_default_files_array()
    {
        // Arrange & Act
        $dto = new PageDataDTO(
            title: 'Test Title',
            content: 'Test Content'
        );

        // Assert
        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('Test Content', $dto->content);
        $this->assertEquals([], $dto->files);
    }

    public function test_from_array_creates_dto_correctly()
    {
        // Arrange
        $data = [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'files' => ['test.pdf']
        ];

        // Act
        $dto = PageDataDTO::fromArray($data);

        // Assert
        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('Test Content', $dto->content);
        $this->assertEquals(['test.pdf'], $dto->files);
    }

    public function test_from_array_handles_missing_fields()
    {
        // Arrange
        $data = [
            'title' => 'Test Title'
        ];

        // Act
        $dto = PageDataDTO::fromArray($data);

        // Assert
        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('', $dto->content);
        $this->assertEquals([], $dto->files);
    }

    public function test_from_array_handles_null_values()
    {
        // Arrange
        $data = [
            'title' => null,
            'content' => null,
            'files' => null
        ];

        // Act
        $dto = PageDataDTO::fromArray($data);

        // Assert
        $this->assertEquals('', $dto->title);
        $this->assertEquals('', $dto->content);
        $this->assertEquals([], $dto->files);
    }

    public function test_to_array_returns_correct_structure()
    {
        // Arrange
        $dto = new PageDataDTO(
            title: 'Test Title',
            content: 'Test Content',
            files: ['test.pdf']
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertEquals([
            'title' => 'Test Title',
            'content' => 'Test Content',
            'files' => ['test.pdf']
        ], $array);
    }

    public function test_properties_are_readonly()
    {
        // Arrange
        $dto = new PageDataDTO(
            title: 'Test Title',
            content: 'Test Content'
        );

        // Act & Assert
        $this->expectException(\Error::class);
        $dto->title = 'Modified Title';
    }
}
