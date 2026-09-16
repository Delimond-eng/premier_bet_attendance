<?php

namespace Tests\Feature;

use App\Models\MobileDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaceListResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_face_list_response_is_stored(): void
    {
        MobileDevice::create([
            'imei' => '354123456789012',
            'firebase_token' => 'token-123',
            'platform' => 'android',
            'device_name' => 'Terminal test',
            'last_seen_at' => now(),
        ]);

        $response = $this->postJson('/api/devices/response', [
            'request_id' => 'req-face-001',
            'imei' => '354123456789012',
            'command' => 'FACE_LIST',
            'data' => [
                'matricules' => ['A001', 'A002', 'A003'],
                'count' => 3,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 3);

        $this->assertDatabaseHas('device_face_lists', [
            'request_id' => 'req-face-001',
            'device_imei' => '354123456789012',
            'command' => 'FACE_LIST',
        ]);
    }
}
