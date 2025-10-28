<?php

namespace App\Services;

use App\Interfaces\HtmlToMdInterface;
use League\HTMLToMarkdown\HtmlConverter;

class HtmlToMdConvertor implements HtmlToMdInterface
{
    private HtmlConverter $convertor;

    public function __construct()
    {
        $this->convertor = new HtmlConverter();
    }

    public function toMd(string $html): string
    {
        return $this->convertor->convert($html);
    }
}
