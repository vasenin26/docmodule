<?php

namespace App\Console\Commands;

use App\Interfaces\HtmlToMdInterface;
use App\Models\PageVersion;
use Illuminate\Console\Command;

class PageContentToMarkdown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:page-content-to-markdown';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert html page content to markdown';

    /**
     * Execute the console command.
     */
    public function handle(
        HtmlToMdInterface $converter
    )
    {
        PageVersion::all()->each(function (PageVersion $pageVersion) use ($converter) {
            // Конвертируем только контент, похожий на HTML, чтобы не испортить уже сохранённый Markdown
            if ($pageVersion->content && preg_match('/<\/?(p|br|h[1-6]|ul|ol|li|div|span|strong|em|a|pre|code|table|blockquote)\b[^>]*>/i', $pageVersion->content)) {
                $pageVersion->content = $converter->toMd($pageVersion->content);
                $pageVersion->save();
            }
        });
    }
}
