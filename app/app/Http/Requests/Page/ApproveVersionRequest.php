<?php

namespace App\Http\Requests\Page;

use Illuminate\Foundation\Http\FormRequest;

class ApproveVersionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $page = $this->route('page');

        // Проверяем, что страница существует
        if (!$page) {
            return false;
        }

        // Проверяем, что пользователь аутентифицирован
        if (!auth()->check()) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'required|string|url',
            'createTask' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Название страницы обязательно для заполнения.',
            'title.max' => 'Название страницы не может быть длиннее 255 символов.',
            'files.array' => 'Поле файлы должно быть массивом.',
            'files.*.required' => 'Ссылка на файл обязательна.',
            'files.*.url' => 'Ссылка на файл должна быть корректным URL.',
            'createTask.boolean' => 'Поле создания задачи должно быть булевым значением.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Дополнительная валидация файлов - должны быть ссылками на git репозитории
        if ($this->has('files') && $this->files) {
            foreach ($this->files as $file) {
                if (!is_string($file) || !filter_var($file, FILTER_VALIDATE_URL)) {
                    continue;
                }

                // Проверяем, что это ссылка на git репозиторий
                if (!$this->isGitRepositoryFileUrl($file)) {
                    $this->merge(['validation_errors' => ['files' => 'Ссылки должны вести на файлы в git репозиториях (GitHub, GitLab, Bitbucket)']]);
                }
            }
        }
    }

    /**
     * Проверить, является ли URL ссылкой на файл в git репозитории
     */
    private function isGitRepositoryFileUrl(string $url): bool
    {
        $gitHosts = ['github.com', 'gitlab.com', 'bitbucket.org'];

        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['host'])) {
            return false;
        }

        foreach ($gitHosts as $host) {
            if (str_contains($parsedUrl['host'], $host)) {
                return true;
            }
        }

        return false;
    }
}
