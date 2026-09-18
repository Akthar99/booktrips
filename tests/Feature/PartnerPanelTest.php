<?php

use App\Enums\InvoiceStatus;
use App\Enums\ReceiptStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Receipt;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    Notification::fake();

    $this->partner = User::factory()->partner()->create();
    $this->business = Business::factory()->for($this->partner, 'user')->create(['approved' => true]);
});

it('keeps pending partners out of the dashboard', function () {
    $pending = User::factory()->partner()->create();
    Business::factory()->for($pending, 'user')->pending()->create();

    $this->actingAs($pending)
        ->get('/partners/dashboard')
        ->assertRedirect(route('partner.pending'));

    $this->actingAs($pending)->get('/partners/pending')->assertOk();
});

it('sends travellers to the partner application instead of a dead end', function () {
    $this->actingAs(User::factory()->create())
        ->get('/partners/dashboard')
        ->assertRedirect(route('partner.apply'));

    $this->actingAs(User::factory()->create())
        ->get('/partners/bookings')
        ->assertRedirect(route('partner.apply'));
});

it('sends admins to their own console from partner areas', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/partners/dashboard')
        ->assertRedirect(route('admin.overview'));
});

it('creates, updates and hides a package', function () {
    $create = $this->actingAs($this->partner)->post('/partners/packages', [
        'title' => 'Ella Camp 2026',
        'category' => 'camping',
        'description' => 'A night on the ridge with dinner and sunrise.',
        'highlight' => 'Sunrise above Ella Gap',
        'location' => 'Ella',
        'district' => 'Badulla',
        'lat' => 6.8667,
        'lng' => 81.0466,
        'schedule_type' => 'always',
        'weekdays' => [0, 1, 2, 3, 4, 5, 6],
        'duration_days' => 2,
        'duration_nights' => 1,
        'price_lkr' => 12500,
        'price_type' => 'per_person',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'discount_enabled' => true,
        'min_guests' => 1,
        'max_guests' => 12,
        'included' => ['Dinner', 'Guide'],
        'excluded' => ['Transport'],
        'images' => [],
        'active' => true,
    ]);

    $create->assertRedirect(route('partner.dashboard'));

    $package = Package::query()->firstOrFail();

    expect($package->slug)->toBe('ella-camp-2026')
        ->and($package->business_id)->toBe($this->business->id)
        ->and($package->discount_enabled)->toBeTrue();

    $this->actingAs($this->partner)
        ->put("/partners/packages/{$package->id}", ['title' => 'Ella Camp 2026 (updated)'])
        ->assertRedirect(route('partner.dashboard'));

    $package->refresh();

    expect($package->title)->toBe('Ella Camp 2026 (updated)')
        ->and($package->slug)->toBe('ella-camp-2026');

    $this->actingAs($this->partner)->delete("/partners/packages/{$package->id}")->assertRedirect();

    expect($package->fresh()->active)->toBeFalse();
    $this->get('/packages/ella-camp-2026')->assertNotFound();
});

it('puts a hidden package back in the catalogue', function () {
    $package = Package::factory()->for($this->business)->inactive()->create(['slug' => 'hidden-camp']);

    $this->get('/packages/hidden-camp')->assertNotFound();

    $this->actingAs($this->partner)
        ->patch("/partners/packages/{$package->id}/publish")
        ->assertRedirect();

    expect($package->fresh()->active)->toBeTrue();
    $this->get('/packages/hidden-camp')->assertOk();
});

it('will not publish another partner package', function () {
    $other = Business::factory()->for(User::factory()->partner(), 'user')->create(['approved' => true]);
    $package = Package::factory()->for($other)->inactive()->create();

    $this->actingAs($this->partner)
        ->patch("/partners/packages/{$package->id}/publish")
        ->assertForbidden();

    expect($package->fresh()->active)->toBeFalse();
});

it('rejects a fixed discount larger than the price', function () {
    $this->actingAs($this->partner)->post('/partners/packages', [
        'title' => 'Bad discount',
        'category' => 'dayout',
        'description' => 'Testing the discount guard.',
        'location' => 'Kandy',
        'schedule_type' => 'always',
        'duration_days' => 1,
        'duration_nights' => 0,
        'price_lkr' => 5000,
        'price_type' => 'per_package',
        'discount_type' => 'fixed',
        'discount_value' => 9000,
        'min_guests' => 1,
        'max_guests' => 4,
    ])->assertSessionHasErrors('discount_value');
});

it('blocks editing another partner package', function () {
    $other = Business::factory()->for(User::factory()->partner(), 'user')->create(['approved' => true]);
    $package = Package::factory()->for($other)->create();

    $this->actingAs($this->partner)
        ->get("/partners/packages/{$package->id}/edit")
        ->assertForbidden();

    $this->actingAs($this->partner)
        ->put("/partners/packages/{$package->id}", ['title' => 'Hijacked'])
        ->assertForbidden();
});

it('uploads package photos to the public disk and rejects other files', function () {
    $response = $this->actingAs($this->partner)->post('/partners/images', [
        'images' => [UploadedFile::fake()->image('camp.jpg', 800, 600)],
    ]);

    $response->assertOk()->assertJsonCount(1, 'images');

    $url = $response->json('images.0');

    expect($url)->toContain("/packages/{$this->business->id}/");

    $this->actingAs($this->partner)->post('/partners/images', [
        'images' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')],
    ])->assertSessionHasErrors('images.0');
});

it('takes a receipt upload for an open invoice and keeps it private', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->partner()->create();
    Business::factory()->for($other, 'user')->create(['approved' => true]);

    $invoice = Invoice::factory()->for($this->business)->create(['period' => '2030-03']);

    $response = $this->actingAs($this->partner)->post('/partners/payments/receipts', [
        'invoice_id' => $invoice->id,
        'note' => 'Transfer 88231',
        'file' => UploadedFile::fake()->image('receipt.jpg'),
    ]);

    $response->assertRedirect();

    $receipt = Receipt::query()->firstOrFail();

    expect($receipt->status)->toBe(ReceiptStatus::Pending)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Submitted);

    Storage::disk('local')->assertExists($receipt->file_path);

    Notification::assertSentTo($admin, ActivityNotification::class, function ($notification) {
        return $notification->payload['type'] === 'receipt'
            && $notification->payload['link'] === '/admin/payments';
    });

    $this->actingAs($this->partner)
        ->get("/receipts/{$receipt->id}")
        ->assertOk();

    $this->actingAs($other)
        ->get("/receipts/{$receipt->id}")
        ->assertForbidden();
});

it('shows partner analytics for the signed-in business', function () {
    Package::factory()->for($this->business)->create();

    $this->actingAs($this->partner)
        ->get('/partners/analytics')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('partner/analytics')
            ->where('analytics.business.id', $this->business->id)
            ->has('analytics.summary'));
});

it('counts upcoming confirmed bookings on the dashboard', function () {
    $package = Package::factory()->for($this->business)->create();

    Booking::factory()->forPackage($package)->confirmed()->create([
        'check_in' => now()->addWeek()->toDateString(),
        'check_out' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($this->partner)
        ->get('/partners/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.packages', 1)
            ->where('stats.bookings', 1)
            ->where('stats.upcoming', 1));
});
