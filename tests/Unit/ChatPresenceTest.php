<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\ChatPresence;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatPresenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.presence_store', 'array');
        Cache::store('array')->clear();
    }

    public function test_presence_uses_the_configured_ephemeral_store_and_a_recent_heartbeat(): void
    {
        $user = new User;
        $user->setAttribute('id', 42);
        $user->setAttribute('is_active', true);
        $presence = app(ChatPresence::class);

        $this->assertSame('array', $presence->storeName());
        $this->assertSame(['is_online' => false, 'label' => 'Sedang tidak aktif'], $presence->forUser($user));

        $presence->heartbeat($user);

        $this->assertSame(['is_online' => true, 'label' => 'Online'], $presence->forUser($user));

        Cache::store('array')->put($presence->keyFor($user), now()->subMinutes(5)->timestamp, now()->addHours(ChatPresence::RETENTION_HOURS));

        $this->assertSame(['is_online' => false, 'label' => 'Terakhir aktif beberapa menit lalu'], $presence->forUser($user));
    }
}
