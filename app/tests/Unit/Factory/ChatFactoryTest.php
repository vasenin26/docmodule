<?php

namespace Tests\Unit\Factory;

use App\Factory\ChatFactory;
use App\Interfaces\ContentGenerator\DiffGeneratorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ChatFactoryTest extends TestCase
{
    #[Test]
    public function it_constructs_with_required_dependencies(): void
    {
        $diffGenerator = $this->createMock(DiffGeneratorInterface::class);

        $factory = new ChatFactory($diffGenerator);

        $this->assertInstanceOf(ChatFactory::class, $factory);
    }
}


