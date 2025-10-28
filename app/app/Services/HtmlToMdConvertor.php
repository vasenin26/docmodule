<?php

namespace App\Services;

use App\Interfaces\HtmlToMdInterface;
use FastVolt\Helper\Markdown;
use League\HTMLToMarkdown\HtmlConverter;

class HtmlToMdConvertor implements HtmlToMdInterface
{

    public function toMd(string $html): string
    {
        $toHtml = new HtmlConverter();
        return $toHtml->convert($html);
    }

    public function toHtml(string $md): string
    {
        $markdown = new Markdown();
        $markdown->setContent($md);
        return $markdown->toHtml();
    }
}
