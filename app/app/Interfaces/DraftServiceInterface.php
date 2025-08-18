<?php

namespace App\Interfaces;

use App\Models\Page;
use App\Models\PageVersion;
use App\Common\DTO\PageDataDTO;

interface DraftServiceInterface
{
    public function createDraft(Page $page, PageDataDTO $data): PageVersion;
    public function getCurrentDraft(Page $page): ?PageVersion;
    public function deleteDraft(Page $page): bool;
    public function hasActiveDraft(Page $page): bool;
    public function approveDraft(Page $page, PageVersion $draft): void;
}
