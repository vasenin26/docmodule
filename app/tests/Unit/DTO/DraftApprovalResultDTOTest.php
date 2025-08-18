<?php

namespace Tests\Unit\DTO;

use App\Common\DTO\DraftApprovalResultDTO;
use Tests\TestCase;

class DraftApprovalResultDTOTest extends TestCase
{
    public function test_constructor_sets_properties_correctly()
    {
        // Arrange & Act
        $dto = new DraftApprovalResultDTO(
            success: true,
            message: 'Test message',
            taskCreated: true,
            taskId: 123,
            taskError: 'Test error'
        );

        // Assert
        $this->assertTrue($dto->success);
        $this->assertEquals('Test message', $dto->message);
        $this->assertTrue($dto->taskCreated);
        $this->assertEquals(123, $dto->taskId);
        $this->assertEquals('Test error', $dto->taskError);
    }

    public function test_constructor_uses_default_values()
    {
        // Arrange & Act
        $dto = new DraftApprovalResultDTO(
            success: true,
            message: 'Test message'
        );

        // Assert
        $this->assertTrue($dto->success);
        $this->assertEquals('Test message', $dto->message);
        $this->assertFalse($dto->taskCreated);
        $this->assertNull($dto->taskId);
        $this->assertNull($dto->taskError);
    }

    public function test_success_static_method()
    {
        // Arrange & Act
        $dto = DraftApprovalResultDTO::success('Success message');

        // Assert
        $this->assertTrue($dto->success);
        $this->assertEquals('Success message', $dto->message);
        $this->assertFalse($dto->taskCreated);
        $this->assertNull($dto->taskId);
        $this->assertNull($dto->taskError);
    }

    public function test_with_task_static_method()
    {
        // Arrange & Act
        $dto = DraftApprovalResultDTO::withTask('Task created', 456);

        // Assert
        $this->assertTrue($dto->success);
        $this->assertEquals('Task created', $dto->message);
        $this->assertTrue($dto->taskCreated);
        $this->assertEquals(456, $dto->taskId);
        $this->assertNull($dto->taskError);
    }

    public function test_with_task_error_static_method()
    {
        // Arrange & Act
        $dto = DraftApprovalResultDTO::withTaskError('Task failed', 'Error details');

        // Assert
        $this->assertTrue($dto->success);
        $this->assertEquals('Task failed', $dto->message);
        $this->assertFalse($dto->taskCreated);
        $this->assertNull($dto->taskId);
        $this->assertEquals('Error details', $dto->taskError);
    }

    public function test_to_array_returns_correct_structure()
    {
        // Arrange
        $dto = new DraftApprovalResultDTO(
            success: true,
            message: 'Test message',
            taskCreated: true,
            taskId: 789,
            taskError: 'Test error'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertEquals([
            'success' => true,
            'message' => 'Test message',
            'task_created' => true,
            'task_id' => 789,
            'task_error' => 'Test error'
        ], $array);
    }

    public function test_to_array_with_default_values()
    {
        // Arrange
        $dto = new DraftApprovalResultDTO(
            success: false,
            message: 'Test message'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertEquals([
            'success' => false,
            'message' => 'Test message',
            'task_created' => false,
            'task_id' => null,
            'task_error' => null
        ], $array);
    }

    public function test_properties_are_readonly()
    {
        // Arrange
        $dto = new DraftApprovalResultDTO(
            success: true,
            message: 'Test message'
        );

        // Act & Assert
        $this->expectException(\Error::class);
        $dto->success = false;
    }
}
