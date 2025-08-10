<?php

namespace Tests\Unit\Services;

use App\Services\TaskDescriptionGenerator\OpenAIDescriptionGenerator;
use OpenAI\Testing\ClientFake;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\TestCase;

class OpenAIDescriptionGeneratorTest extends TestCase
{
    public function test_generates_description_with_openai_api()
    {
        // Arrange
        $fakeClient = new ClientFake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Добавлена новая функция авторизации пользователей',
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 50,
                    'completion_tokens' => 10,
                    'total_tokens' => 60,
                ],
            ]),
        ]);

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
        $generator = new OpenAIDescriptionGenerator();
        $description = $generator->generateDescription($differenceData);

        // Assert
        $this->assertEquals('Добавлена новая функция авторизации пользователей', $description);
    }

    public function test_handles_api_error_gracefully()
    {
        // Arrange
        $differenceData = [
            'added_lines' => ['+ new code'],
            'commit_message' => 'Test commit',
        ];

        // Act
        $generator = new OpenAIDescriptionGenerator();
        $description = $generator->generateDescription($differenceData);

        // Assert
        $this->assertStringContainsString('Задача создана из разницы версий', $description);
        $this->assertStringContainsString('Test commit', $description);
    }

    public function test_builds_prompt_correctly()
    {
        // Arrange
        $differenceData = [
            'added_lines' => [
                '+ public function test() {',
                '+     return true;',
            ],
            'removed_lines' => [
                '- old code',
            ],
            'modified_files' => ['test.php'],
            'commit_message' => 'Update test function',
        ];

        // Act
        $generator = new OpenAIDescriptionGenerator();
        $description = $generator->generateDescription($differenceData);

        // Assert
        $this->assertNotEmpty($description);
    }

    public function test_uses_configured_model()
    {
        // Arrange
        config(['services.openai.model' => 'gpt-4o']);

        $differenceData = [
            'commit_message' => 'Test',
        ];

        // Act
        $generator = new OpenAIDescriptionGenerator();
        $description = $generator->generateDescription($differenceData);

        // Assert
        $this->assertNotEmpty($description);
    }

    public function test_throws_exception_when_api_key_not_configured()
    {
        // Arrange
        config(['services.openai.api_key' => null]);
        putenv('OPENAI_API_KEY=');

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OpenAI API key is not configured');

        new OpenAIDescriptionGenerator();
    }
}
