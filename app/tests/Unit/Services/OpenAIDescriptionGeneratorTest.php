<?php

namespace Tests\Unit\Services;

use App\Services\TaskDescriptionGenerator\LLMDescriptionGenerator;
use PHPUnit\Framework\Attributes\Skip;
use OpenAI\Testing\ClientFake;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\TestCase;
use App\Services\LLMGenerator\LMStudioGenerator;

class OpenAIDescriptionGeneratorTest extends TestCase
{
    public function test_generates_description_with_openai_api()
    {
        // Arrange
        $llmGenerator = new LMStudioGenerator();

        $differenceData = [
            'added_lines' => [
                '+ public function login() {',
                '+     return $this->auth->attempt($credentials);',
                '+ }',
            ],
            'removed_lines' => [],
            'modified_files' => ['app/Http/Controllers/AuthController.php'],
            'commit_message' => 'Add user authentication',
        ];

        // Act
        $generator = new LLMDescriptionGenerator($llmGenerator);
        $description = $generator->generateDescription($differenceData);

        // Assert
        $this->assertEquals('Добавлена новая функция авторизации пользователей', $description);
    }
}
