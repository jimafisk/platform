<?php

declare(strict_types=1);

namespace Orchid\Tests\Unit\Platform;

use Orchid\Tests\TestUnitCase;
use Orchid\Platform\Models\User;
use Orchid\Platform\Models\Announcement;

class AnnouncementTest extends TestUnitCase
{
    public function testAuthor(): void
    {
        $user = factory(User::class)->create();

        $announcement = Announcement::create([
            'user_id' => $user->id,
            'content' => 'test',
            'active'  => false,
        ]);

        $author = $announcement->author()->first();

        $this->assertEquals($user->id, $author->id);
    }
}
