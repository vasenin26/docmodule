<?php

namespace Tests\Unit\DTO;

use App\Common\DTO\PageListDTO;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PageListDTOTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_list_dto_creation()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        $paginator = new LengthAwarePaginator(
            collect([$page]),
            1,
            20,
            1
        );

        $filters = ['search' => 'test'];
        $dto = PageListDTO::fromPaginator($paginator, $filters);

        $this->assertInstanceOf(PageListDTO::class, $dto);
        $this->assertCount(1, $dto->pages);
        $this->assertEquals($filters, $dto->filters);
    }

    public function test_page_list_dto_to_array()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        $paginator = new LengthAwarePaginator(
            collect([$page]),
            1,
            20,
            1
        );

        $dto = PageListDTO::fromPaginator($paginator);
        $array = $dto->toArray();

        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('links', $array);
        $this->assertCount(1, $array['data']);
    }
}
