<?php

namespace Tests\Unit\Services;

use App\Services\DiffGenerator\DiffGeneratorService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
}
