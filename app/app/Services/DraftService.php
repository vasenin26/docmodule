<?php

namespace App\Services;

use App\Interfaces\DraftServiceInterface;
use App\Models\Page;
use App\Models\PageVersion;
use App\Common\DTO\PageDataDTO;
use Illuminate\Support\Facades\Log;

class DraftService implements DraftServiceInterface
{
    public function createDraft(Page $page, PageDataDTO $data): PageVersion
    {
        Log::info('Creating draft for page', ['page_id' => $page->id]);
        return $page->createDraft($data->toArray());
    }

    public function getCurrentDraft(Page $page): ?PageVersion
    {
        return $page->getCurrentDraft();
    }

    public function deleteDraft(Page $page): bool
    {
        $draft = $this->getCurrentDraft($page);
        if ($draft) {
            $draft->delete();
            Log::info('Draft deleted', ['page_id' => $page->id, 'draft_id' => $draft->id]);
            return true;
        }
        return false;
    }

    public function hasActiveDraft(Page $page): bool
    {
        return $page->hasActiveDraft();
    }

    public function approveDraft(Page $page, PageVersion $draft): void
    {
        Log::info('Approving draft', ['page_id' => $page->id, 'draft_id' => $draft->id]);
        
        // Утверждаем черновик в странице
        $page->approveDraft($draft);
        
        // Устанавливаем is_draft = false для утвержденного черновика
        $draft->update(['is_draft' => false]);
    }
}
