<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportJob;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'full_name' => 'User 1',
            'email'     => 'example@mail.net',
            'password'  => bcrypt('password'),
        ], $overrides));
    }

    private function loginAndGetToken(string $email = 'example@mail.net', string $password = 'password'): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => $email,
            'password' => $password,
        ]);

        return $response->json('data.token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    // =========================================================================
    // 1. POST /api/auth/login
    // =========================================================================

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'example@mail.net',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 200,
                'error'   => false,
                'message' => 'Berhasil login.',
            ])
            ->assertJsonStructure([
                'data' => ['token'],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'example@mail.net',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 401,
                'error'  => true,
            ]);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // =========================================================================
    // 2. GET /api/auth/profile
    // =========================================================================

    public function test_user_can_get_own_profile(): void
    {
        $user = $this->createUser();
        $token = $this->loginAndGetToken();

        $response = $this->getJson('/api/auth/profile', $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 200,
                'error'   => false,
                'message' => 'Berhasil login.',
                'data'    => [
                    'full_name' => 'User 1',
                    'email'     => 'example@mail.net',
                ],
            ]);
    }

    public function test_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/profile');

        $response->assertStatus(401);
    }

    // =========================================================================
    // 3. POST /api/pockets
    // =========================================================================

    public function test_user_can_create_pocket(): void
    {
        $this->createUser();
        $token = $this->loginAndGetToken();

        $response = $this->postJson('/api/pockets', [
            'name'            => 'Pocket 1',
            'initial_balance' => 2000000,
        ], $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 200,
                'error'   => false,
                'message' => 'Berhasil membuat pocket baru.',
            ])
            ->assertJsonStructure([
                'data' => ['id'],
            ]);

        $this->assertDatabaseHas('user_pockets', [
            'name'    => 'Pocket 1',
            'balance' => 2000000,
        ]);
    }

    public function test_create_pocket_validates_required_fields(): void
    {
        $this->createUser();
        $token = $this->loginAndGetToken();

        $response = $this->postJson('/api/pockets', [], $this->authHeader($token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'initial_balance']);
    }

    public function test_create_pocket_requires_authentication(): void
    {
        $response = $this->postJson('/api/pockets', [
            'name'            => 'Pocket 1',
            'initial_balance' => 2000000,
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // 4. GET /api/pockets
    // =========================================================================

    public function test_user_can_list_own_pockets(): void
    {
        $user = $this->createUser();
        $token = $this->loginAndGetToken();

        Pocket::create(['user_id' => $user->id, 'name' => 'Pocket A', 'balance' => 1000000]);
        Pocket::create(['user_id' => $user->id, 'name' => 'Pocket B', 'balance' => 500000]);

        $response = $this->getJson('/api/pockets', $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 200,
                'error'   => false,
                'message' => 'Berhasil.',
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'current_balance'],
                ],
            ]);
    }

    public function test_list_pockets_does_not_include_other_users_pockets(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser(['email' => 'other@mail.net']);

        $token = $this->loginAndGetToken();

        Pocket::create(['user_id' => $user->id, 'name' => 'My Pocket', 'balance' => 100]);
        Pocket::create(['user_id' => $otherUser->id, 'name' => 'Other Pocket', 'balance' => 200]);

        $response = $this->getJson('/api/pockets', $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // =========================================================================
    // 5. POST /api/incomes
    // =========================================================================

    public function test_user_can_create_income_and_balance_increases(): void
    {
        $user = $this->createUser();
        $token = $this->loginAndGetToken();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name'    => 'Pocket 1',
            'balance' => 2000000,
        ]);

        $response = $this->postJson('/api/incomes', [
            'pocket_id' => $pocket->id,
            'amount'    => 300000,
            'notes'     => 'Menemukan uang di jalan',
        ], $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 200,
                'error'   => false,
                'message' => 'Berhasil menambahkan income.',
                'data'    => [
                    'pocket_id'       => $pocket->id,
                    'current_balance' => 2300000,
                ],
            ])
            ->assertJsonStructure([
                'data' => ['id', 'pocket_id', 'current_balance'],
            ]);

        $this->assertDatabaseHas('user_pockets', [
            'id'      => $pocket->id,
            'balance' => 2300000,
        ]);
    }

    public function test_create_income_validates_required_fields(): void
    {
        $this->createUser();
        $token = $this->loginAndGetToken();

        $response = $this->postJson('/api/incomes', [], $this->authHeader($token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pocket_id', 'amount']);
    }

    public function test_create_income_requires_authentication(): void
    {
        $response = $this->postJson('/api/incomes', [
            'pocket_id' => 'some-uuid',
            'amount'    => 100,
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // 6. POST /api/expenses
    // =========================================================================

    public function test_user_can_create_expense_and_balance_decreases(): void
    {
        $user = $this->createUser();
        $token = $this->loginAndGetToken();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name'    => 'Pocket 1',
            'balance' => 2300000,
        ]);

        $response = $this->postJson('/api/expenses', [
            'pocket_id' => $pocket->id,
            'amount'    => 2000000,
            'notes'     => 'Ganti lecet mobil orang',
        ], $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 200,
                'error'   => false,
                'message' => 'Berhasil menambahkan expense.',
                'data'    => [
                    'pocket_id'       => $pocket->id,
                    'current_balance' => 300000,
                ],
            ])
            ->assertJsonStructure([
                'data' => ['id', 'pocket_id', 'current_balance'],
            ]);

        $this->assertDatabaseHas('user_pockets', [
            'id'      => $pocket->id,
            'balance' => 300000,
        ]);
    }

    public function test_create_expense_validates_required_fields(): void
    {
        $this->createUser();
        $token = $this->loginAndGetToken();

        $response = $this->postJson('/api/expenses', [], $this->authHeader($token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pocket_id', 'amount']);
    }

    public function test_create_expense_requires_authentication(): void
    {
        $response = $this->postJson('/api/expenses', [
            'pocket_id' => 'some-uuid',
            'amount'    => 100,
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // 7. GET /api/pockets/total-balance
    // =========================================================================

    public function test_user_can_get_total_balance_across_all_pockets(): void
    {
        $user = $this->createUser();
        $token = $this->loginAndGetToken();

        Pocket::create(['user_id' => $user->id, 'name' => 'Pocket A', 'balance' => 300000]);
        Pocket::create(['user_id' => $user->id, 'name' => 'Pocket B', 'balance' => 700000]);

        $response = $this->getJson('/api/pockets/total-balance', $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'error'  => false,
                'data'   => [
                    'total' => 1000000,
                ],
            ]);
    }

    public function test_total_balance_is_zero_when_no_pockets(): void
    {
        $this->createUser();
        $token = $this->loginAndGetToken();

        $response = $this->getJson('/api/pockets/total-balance', $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['total' => 0],
            ]);
    }

    public function test_total_balance_only_counts_own_pockets(): void
    {
        $user = $this->createUser();
        $other = $this->createUser(['email' => 'other@mail.net']);
        $token = $this->loginAndGetToken();

        Pocket::create(['user_id' => $user->id,  'name' => 'Mine',  'balance' => 100000]);
        Pocket::create(['user_id' => $other->id, 'name' => 'Theirs', 'balance' => 999999]);

        $response = $this->getJson('/api/pockets/total-balance', $this->authHeader($token));

        $response->assertJson(['data' => ['total' => 100000]]);
    }

    // =========================================================================
    // 8. POST /api/pockets/:id/create-report
    // =========================================================================

    public function test_user_can_create_report_and_job_is_dispatched(): void
    {
        Queue::fake();

        $user = $this->createUser();
        $token = $this->loginAndGetToken();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name'    => 'Pocket 1',
            'balance' => 300000,
        ]);

        $response = $this->postJson("/api/pockets/{$pocket->id}/create-report", [
            'type' => 'INCOME',
            'date' => '2026-01-01',
        ], $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 200,
                'error'   => false,
                'message' => 'Report sedang dibuat. Silahkan check berkala pada link berikut.',
            ])
            ->assertJsonStructure([
                'data' => ['link'],
            ]);

        $this->assertStringContainsString('reports/', $response->json('data.link'));

        Queue::assertPushed(GenerateReportJob::class);
    }

    public function test_create_report_validates_type_and_date(): void
    {
        $user = $this->createUser();
        $token = $this->loginAndGetToken();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name'    => 'Pocket 1',
            'balance' => 300000,
        ]);

        $response = $this->postJson("/api/pockets/{$pocket->id}/create-report", [
            'type' => 'INVALID',
            'date' => 'not-a-date',
        ], $this->authHeader($token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'date']);
    }

    public function test_create_report_returns_404_for_another_users_pocket(): void
    {
        Queue::fake();

        $this->createUser();
        $other = $this->createUser(['email' => 'other@mail.net']);

        $token = $this->loginAndGetToken();

        $otherPocket = Pocket::create([
            'user_id' => $other->id,
            'name'    => 'Other Pocket',
            'balance' => 100,
        ]);

        $response = $this->postJson("/api/pockets/{$otherPocket->id}/create-report", [
            'type' => 'INCOME',
            'date' => '2026-01-01',
        ], $this->authHeader($token));

        $response->assertStatus(404);
    }

    // =========================================================================
    // 9. GET /reports/:id  (stream report)
    // =========================================================================

    public function test_report_stream_downloads_xlsx_file(): void
    {
        $reportId = 'test-report-' . time();
        $dir      = storage_path('app/reports');
        $path     = "{$dir}/{$reportId}.xlsx";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create a minimal dummy XLSX file (PKZip magic bytes)
        file_put_contents($path, "PK\x03\x04");

        try {
            $response = $this->get("/reports/{$reportId}");

            $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } finally {
            @unlink($path);
        }
    }

    public function test_report_stream_returns_404_when_file_missing(): void
    {
        $response = $this->get('/reports/nonexistent-report-id');

        $response->assertStatus(404);
    }

    // =========================================================================
    // Full end-to-end flow (mirrors the README order exactly)
    // =========================================================================

    public function test_full_api_flow(): void
    {
        Queue::fake();

        // 1. Login
        $user = $this->createUser();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => 'example@mail.net',
            'password' => 'password',
        ]);
        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        $headers = $this->authHeader($token);

        // 2. Get profile
        $this->getJson('/api/auth/profile', $headers)
            ->assertStatus(200)
            ->assertJson(['data' => ['full_name' => 'User 1', 'email' => 'example@mail.net']]);

        // 3. Add new pocket
        $createPocketResponse = $this->postJson('/api/pockets', [
            'name'            => 'Pocket 1',
            'initial_balance' => 2000000,
        ], $headers)->assertStatus(200);

        $pocketId = $createPocketResponse->json('data.id');
        $this->assertNotEmpty($pocketId);

        // 4. List pockets
        $this->getJson('/api/pockets', $headers)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['current_balance' => 2000000]);

        // 5. Create income (+300,000 → 2,300,000)
        $incomeResponse = $this->postJson('/api/incomes', [
            'pocket_id' => $pocketId,
            'amount'    => 300000,
            'notes'     => 'Menemukan uang di jalan',
        ], $headers)->assertStatus(200);

        $this->assertEquals(2300000, $incomeResponse->json('data.current_balance'));

        // 6. Create expense (-2,000,000 → 300,000)
        $expenseResponse = $this->postJson('/api/expenses', [
            'pocket_id' => $pocketId,
            'amount'    => 2000000,
            'notes'     => 'Ganti lecet mobil orang',
        ], $headers)->assertStatus(200);

        $this->assertEquals(300000, $expenseResponse->json('data.current_balance'));

        // 7. Total balance
        $this->getJson('/api/pockets/total-balance', $headers)
            ->assertStatus(200)
            ->assertJson(['data' => ['total' => 300000]]);

        // 8. Create report
        $reportResponse = $this->postJson("/api/pockets/{$pocketId}/create-report", [
            'type' => 'INCOME',
            'date' => '2026-01-01',
        ], $headers)->assertStatus(200);

        $this->assertStringContainsString('reports/', $reportResponse->json('data.link'));
        Queue::assertPushed(GenerateReportJob::class);
    }
}
