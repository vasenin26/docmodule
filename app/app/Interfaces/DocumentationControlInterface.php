<?php

namespace App\Interfaces;

use App\Common\DTO\PageVersionDTO;
use App\Models\Page;
use App\Models\PageVersion;
use App\Common\DTO\PageDataDTO;
use App\Common\DTO\DraftApprovalResultDTO;
use App\Common\DTO\PageAggregateDTO;
use App\Common\DTO\PageListDTO;
use App\Common\DTO\PageDetailDTO;
use App\Common\DTO\PageEditDTO;
use App\Common\DTO\DraftDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface DocumentationControlInterface
{
    public function updatePageWithDraftLogic(Page $page, PageDataDTO $data): PageVersion;
    public function createDraftFromCurrentVersion(Page $page, PageDataDTO $data): PageVersion;
    public function approveDraftWithTask(Page $page, bool $createTask = false): DraftApprovalResultDTO;
    public function getCurrentPageAggregate(Page $page): PageAggregateDTO;

    // Новые методы для DTO
    public function getPageListDTO(LengthAwarePaginator $paginator, array $filters = []): PageListDTO;
    public function getPageDetailDTO(Page $page): PageDetailDTO;
    public function getPageEditDTO(Page $page): PageEditDTO;
    public function getDraftDTO(PageVersion $draft): DraftDTO;


    public function getCurrentVersion(Page $page): PageVersionDTO;
    public function getPageVersion(Page $page, int $id): PageVersionDTO;
}
