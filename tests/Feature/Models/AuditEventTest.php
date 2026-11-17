<?php

namespace Tests\Feature\Models;

use App\Models\AuditEvent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditEventTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_saved_event_cannot_be_updated(): void
    {
        $event = AuditEvent::factory()->create();

        $this->expectException(LogicException::class);

        $event->update(['event' => 'changed.event']);
    }

    public function test_saved_event_cannot_be_deleted(): void
    {
        $event = AuditEvent::factory()->create();

        $this->expectException(LogicException::class);

        $event->delete();
    }
}
