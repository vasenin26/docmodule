<?php

namespace Tests\Unit\Services;

use App\Services\TaskDescriptionGenerator\OpenAIDescriptionGenerator;
use PHPUnit\Framework\Attributes\Skip;
use OpenAI\Testing\ClientFake;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\TestCase;

#[Skip]
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

    public function test_generator_includes_diff_output_in_prompt()
    {
        // Arrange
        $fakeClient = new ClientFake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Обновлена документация с добавлением новых разделов',
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
            'new_version_title' => 'Test Page',
            'is_new_page' => false,
            'diff_output' => "- Старый заголовок\n+ Новый заголовок\n- Старый контент\n+ Новый контент",
        ];

        // Act
        $generator = new OpenAIDescriptionGenerator();
        $description = $generator->generateDescription($differenceData);

        // Assert
        $this->assertEquals('Обновлена документация с добавлением новых разделов', $description);
    }

    public function test_generator_handles_new_page_with_diff_output()
    {
        // Arrange
        $fakeClient = new ClientFake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Создана новая страница документации',
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
            'new_version_title' => 'New Page',
            'is_new_page' => true,
            'diff_output' => "+ New Page\n+ Новый контент страницы",
        ];

        // Act
        $generator = new OpenAIDescriptionGenerator();
        $description = $generator->generateDescription($differenceData);

        // Assert
        $this->assertEquals('Создана новая страница документации', $description);
    }

    public function test_generator_falls_back_to_old_format_when_diff_output_not_available()
    {
        // Arrange
        $fakeClient = new ClientFake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Обновлен код с добавлением новых функций',
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
            'added_lines' => ['+ new function'],
            'removed_lines' => ['- old function'],
            'commit_message' => 'Update functions',
        ];

        // Act
        $generator = new OpenAIDescriptionGenerator();
        $description = $generator->generateDescription($differenceData);

        // Assert
        $this->assertEquals('Обновлен код с добавлением новых функций', $description);
    }
}
