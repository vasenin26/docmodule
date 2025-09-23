<?php

namespace Tests\Unit\Services;

use App\Services\DiffGenerator\DiffGeneratorService;
use App\Models\PageVersion;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DiffGeneratorServiceTest extends TestCase
{
    private DiffGeneratorService $diffGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->diffGenerator = new DiffGeneratorService();
    }

    #[Test]
    public function it_generates_diff_for_title_changes()
    {
        $oldTitle = 'Old Title';
        $newTitle = 'New Title';

        $result = $this->diffGenerator->generateDiff($oldTitle, $newTitle, 'title');

        $expected = "- Old Title\n+ New Title";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_generates_diff_for_title_removal()
    {
        $oldTitle = 'Old Title';
        $newTitle = '';

        $result = $this->diffGenerator->generateDiff($oldTitle, $newTitle, 'title');

        $expected = "- Old Title";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_generates_diff_for_title_addition()
    {
        $oldTitle = '';
        $newTitle = 'New Title';

        $result = $this->diffGenerator->generateDiff($oldTitle, $newTitle, 'title');

        $expected = "+ New Title";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_generates_diff_for_content_changes()
    {
        $oldContent = "Line 1\nLine 2\nLine 3";
        $newContent = "Line 1\nLine 2\nLine 4";

        $result = $this->diffGenerator->generateDiff($oldContent, $newContent, 'content');

        $expected = "- Line 3\n+ Line 4";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_generates_diff_for_content_with_empty_lines()
    {
        $oldContent = "Line 1\n\nLine 2";
        $newContent = "Line 1\nLine 3";

        $result = $this->diffGenerator->generateDiff($oldContent, $newContent, 'content');

        $expected = "- Line 2\n+ Line 3";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_generates_diff_for_new_content()
    {
        $oldContent = '';
        $newContent = "Line 1\nLine 2";

        $result = $this->diffGenerator->generateDiff($oldContent, $newContent, 'content');

        $expected = "+ Line 1\n+ Line 2";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_generates_diff_for_deleted_content()
    {
        $oldContent = "Line 1\nLine 2";
        $newContent = '';

        $result = $this->diffGenerator->generateDiff($oldContent, $newContent, 'content');

        $expected = "- Line 1\n- Line 2";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_returns_empty_string_for_identical_content()
    {
        $content = "Line 1\nLine 2";

        $result = $this->diffGenerator->generateDiff($content, $content, 'content');

        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_returns_empty_string_for_identical_title()
    {
        $title = 'Same Title';

        $result = $this->diffGenerator->generateDiff($title, $title, 'title');

        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_handles_null_old_content()
    {
        $oldContent = null;
        $newContent = "Line 1\nLine 2";

        $result = $this->diffGenerator->generateDiff($oldContent, $newContent, 'content');

        $expected = "+ Line 1\n+ Line 2";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_handles_null_new_content()
    {
        $oldContent = "Line 1\nLine 2";
        $newContent = null;

        $result = $this->diffGenerator->generateDiff($oldContent, $newContent, 'content');

        $expected = "- Line 1\n- Line 2";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_handles_both_null_content()
    {
        $oldContent = null;
        $newContent = null;

        $result = $this->diffGenerator->generateDiff($oldContent, $newContent, 'content');

        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_handles_null_old_title()
    {
        $oldTitle = null;
        $newTitle = 'New Title';

        $result = $this->diffGenerator->generateDiff($oldTitle, $newTitle, 'title');

        $expected = "+ New Title";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_handles_null_new_title()
    {
        $oldTitle = 'Old Title';
        $newTitle = null;

        $result = $this->diffGenerator->generateDiff($oldTitle, $newTitle, 'title');

        $expected = "- Old Title";
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_creates_dto_for_new_page()
    {
        $current = new PageVersion(['title' => 'Title', 'content' => "Line 1\nLine 2"]);
        $current->id = 10;

        $dto = $this->diffGenerator->createDifferenceDataDTO($current);

        $this->assertTrue($dto->isNewPage);
        $this->assertFalse($dto->titleChanged);
        $this->assertFalse($dto->contentChanged);
        $this->assertNull($dto->diffOutput);
        $this->assertSame(10, $dto->newVersionId);
        $this->assertSame('Title', $dto->newVersionTitle);
        $this->assertSame("Line 1\nLine 2", $dto->newVersionContent);
        $this->assertNull($dto->previousVersionId);
        $this->assertNull($dto->previousVersionTitle);
        $this->assertNull($dto->previousVersionContent);
    }

    #[Test]
    public function it_creates_dto_for_updated_page_title_only()
    {
        $prev = new PageVersion(['title' => 'Old', 'content' => "A\nB"]);
        $prev->id = 1;

        $current = new PageVersion(['title' => 'New', 'content' => "A\nB"]);
        $current->id = 2;
        $current->previous_version_id = 1;
        $current->setRelation('previousVersion', $prev);

        $dto = $this->diffGenerator->createDifferenceDataDTO($current);

        $this->assertFalse($dto->isNewPage);
        $this->assertTrue($dto->titleChanged);
        $this->assertFalse($dto->contentChanged);
        // Title-only change still attempts content diff, which is empty string
        $this->assertSame('', $dto->diffOutput);
        $this->assertSame(2, $dto->newVersionId);
        $this->assertSame('New', $dto->newVersionTitle);
        $this->assertSame('A' . "\n" . 'B', $dto->newVersionContent);
        $this->assertSame(1, $dto->previousVersionId);
        $this->assertSame('Old', $dto->previousVersionTitle);
        $this->assertSame('A' . "\n" . 'B', $dto->previousVersionContent);
    }

    #[Test]
    public function it_creates_dto_for_updated_page_content_change()
    {
        $prev = new PageVersion(['title' => 'T', 'content' => "X\nY"]);
        $prev->id = 11;

        $current = new PageVersion(['title' => 'T', 'content' => "X\nZ"]);
        $current->id = 12;
        $current->previous_version_id = 11;
        $current->setRelation('previousVersion', $prev);

        $dto = $this->diffGenerator->createDifferenceDataDTO($current);

        $this->assertFalse($dto->isNewPage);
        $this->assertFalse($dto->titleChanged);
        $this->assertTrue($dto->contentChanged);
        $this->assertSame("- Y\n+ Z", $dto->diffOutput);
        $this->assertSame(12, $dto->newVersionId);
        $this->assertSame('T', $dto->newVersionTitle);
        $this->assertSame("X\nZ", $dto->newVersionContent);
        $this->assertSame(11, $dto->previousVersionId);
        $this->assertSame('T', $dto->previousVersionTitle);
        $this->assertSame("X\nY", $dto->previousVersionContent);
    }

    #[Test]
    public function it_creates_dto_when_only_one_middle_line_changes()
    {
        $prev = new PageVersion(['title' => 'T', 'content' => "A\nB\nC"]);
        $prev->id = 51;

        $current = new PageVersion(['title' => 'T', 'content' => "A\nX\nC"]);
        $current->id = 52;
        $current->previous_version_id = 51;
        $current->setRelation('previousVersion', $prev);

        $dto = $this->diffGenerator->createDifferenceDataDTO($current);

        $this->assertFalse($dto->isNewPage);
        $this->assertFalse($dto->titleChanged);
        $this->assertTrue($dto->contentChanged);
        $this->assertSame("- B\n+ X", $dto->diffOutput);
        $this->assertSame(52, $dto->newVersionId);
        $this->assertSame('T', $dto->newVersionTitle);
        $this->assertSame("A\nX\nC", $dto->newVersionContent);
        $this->assertSame(51, $dto->previousVersionId);
        $this->assertSame('T', $dto->previousVersionTitle);
        $this->assertSame("A\nB\nC", $dto->previousVersionContent);
    }

    #[Test]
    public function it_creates_dto_for_updated_page_no_changes()
    {
        $prev = new PageVersion(['title' => 'T', 'content' => "X\nY"]);
        $prev->id = 21;

        $current = new PageVersion(['title' => 'T', 'content' => "X\nY"]);
        $current->id = 22;
        $current->previous_version_id = 21;
        $current->setRelation('previousVersion', $prev);

        $dto = $this->diffGenerator->createDifferenceDataDTO($current);

        $this->assertFalse($dto->isNewPage);
        $this->assertFalse($dto->titleChanged);
        $this->assertFalse($dto->contentChanged);
        $this->assertNull($dto->diffOutput);
        $this->assertSame(22, $dto->newVersionId);
        $this->assertSame('T', $dto->newVersionTitle);
        $this->assertSame("X\nY", $dto->newVersionContent);
        $this->assertSame(21, $dto->previousVersionId);
        $this->assertSame('T', $dto->previousVersionTitle);
        $this->assertSame("X\nY", $dto->previousVersionContent);
    }

    #[Test]
    public function it_creates_dto_for_content_added_from_empty()
    {
        $prev = new PageVersion(['title' => 'T', 'content' => null]);
        $prev->id = 31;

        $current = new PageVersion(['title' => 'T', 'content' => "L1\nL2"]);
        $current->id = 32;
        $current->previous_version_id = 31;
        $current->setRelation('previousVersion', $prev);

        $dto = $this->diffGenerator->createDifferenceDataDTO($current);

        $this->assertFalse($dto->isNewPage);
        $this->assertFalse($dto->titleChanged);
        $this->assertTrue($dto->contentChanged);
        $this->assertSame("+ L1\n+ L2", $dto->diffOutput);
        $this->assertSame(32, $dto->newVersionId);
        $this->assertSame('T', $dto->newVersionTitle);
        $this->assertSame("L1\nL2", $dto->newVersionContent);
        $this->assertSame(31, $dto->previousVersionId);
        $this->assertSame('T', $dto->previousVersionTitle);
        $this->assertNull($dto->previousVersionContent);
    }

    #[Test]
    public function it_creates_dto_for_content_removed_to_empty()
    {
        $prev = new PageVersion(['title' => 'T', 'content' => "A\nB"]);
        $prev->id = 41;

        $current = new PageVersion(['title' => 'T', 'content' => null]);
        $current->id = 42;
        $current->previous_version_id = 41;
        $current->setRelation('previousVersion', $prev);

        $dto = $this->diffGenerator->createDifferenceDataDTO($current);

        $this->assertFalse($dto->isNewPage);
        $this->assertFalse($dto->titleChanged);
        $this->assertTrue($dto->contentChanged);
        $this->assertSame("- A\n- B", $dto->diffOutput);
        $this->assertSame(42, $dto->newVersionId);
        $this->assertSame('T', $dto->newVersionTitle);
        $this->assertNull($dto->newVersionContent);
        $this->assertSame(41, $dto->previousVersionId);
        $this->assertSame('T', $dto->previousVersionTitle);
        $this->assertSame("A\nB", $dto->previousVersionContent);
    }
}
