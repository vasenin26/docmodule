<?php

namespace App\Interfaces;

interface HtmlToMdInterface
{

    public function toMd(string $html): string;

    public function toHtml(string $md): string;
}
