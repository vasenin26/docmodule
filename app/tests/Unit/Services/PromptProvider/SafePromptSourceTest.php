<?php

namespace Tests\Unit\Services\PromptProvider;

use App\Common\Enums\PromptType;
use App\Services\PromptProvider\Interface\PromptSourceInterface;
use App\Services\PromptProvider\Sources\SafePromptSource;
use PHPUnit\Framework\TestCase;

class SafePromptSourceTest extends TestCase
{
    public function test_returns_primary_source_prompt_when_available(): void
    {
        $primarySource = $this->createMock(PromptSourceInterface::class);
        $fallbackSource = $this->createMock(PromptSourceInterface::class);
        
        $primarySource->expects($this->once())
            ->method('getPrompt')
            ->with(PromptType::TASK_MANAGER)
            ->willReturn('Primary prompt');
        
        $fallbackSource->expects($this->never())
            ->method('getPrompt');
        
        $safeSource = new SafePromptSource($primarySource, $fallbackSource);
        $result = $safeSource->getPrompt(PromptType::TASK_MANAGER);
        
        $this->assertEquals('Primary prompt', $result);
    }

    public function test_returns_fallback_source_prompt_when_primary_is_null(): void
    {
        $primarySource = $this->createMock(PromptSourceInterface::class);
        $fallbackSource = $this->createMock(PromptSourceInterface::class);
        
        $primarySource->expects($this->once())
            ->method('getPrompt')
            ->with(PromptType::TASK_MANAGER)
            ->willReturn(null);
        
        $fallbackSource->expects($this->once())
            ->method('getPrompt')
            ->with(PromptType::TASK_MANAGER)
            ->willReturn('Fallback prompt');
        
        $safeSource = new SafePromptSource($primarySource, $fallbackSource);
        $result = $safeSource->getPrompt(PromptType::TASK_MANAGER);
        
        $this->assertEquals('Fallback prompt', $result);
    }

    public function test_returns_fallback_source_prompt_when_primary_is_empty(): void
    {
        $primarySource = $this->createMock(PromptSourceInterface::class);
        $fallbackSource = $this->createMock(PromptSourceInterface::class);
        
        $primarySource->expects($this->once())
            ->method('getPrompt')
            ->with(PromptType::TASK_MANAGER)
            ->willReturn('   '); // Пустая строка с пробелами
        
        $fallbackSource->expects($this->once())
            ->method('getPrompt')
            ->with(PromptType::TASK_MANAGER)
            ->willReturn('Fallback prompt');
        
        $safeSource = new SafePromptSource($primarySource, $fallbackSource);
        $result = $safeSource->getPrompt(PromptType::TASK_MANAGER);
        
        $this->assertEquals('Fallback prompt', $result);
    }
}
