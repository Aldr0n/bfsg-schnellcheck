<?php

use App\Enums\AuditStatus;
use App\Jobs\RunAudit;
use App\Models\Audit;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

it('shows the form on the start page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('audits/create'));
});

it('queues an audit and sends the visitor to its report', function () {
    Queue::fake();

    $response = $this->post(route('audits.store'), ['url' => 'https://example.com/kontakt']);

    $audit = Audit::firstOrFail();

    expect($audit->url)->toBe('https://example.com/kontakt')
        ->and($audit->status)->toBe(AuditStatus::Pending);
    $response->assertRedirect(route('audits.show', $audit));
    Queue::assertPushed(RunAudit::class, fn (RunAudit $job) => $job->audit->is($audit));
});

it('rejects an address that is not a http or https url', function (string $url) {
    Queue::fake();

    $this->post(route('audits.store'), ['url' => $url])->assertSessionHasErrors('url');

    expect(Audit::count())->toBe(0);
    Queue::assertNothingPushed();
})->with(['empty' => '', 'plain text' => 'keine adresse', 'other scheme' => 'ftp://example.com']);

it('rejects the sixth audit within a minute with an error on the url field', function () {
    Queue::fake();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('audits.store'), ['url' => 'https://example.com'])->assertRedirect();
    }

    $this->post(route('audits.store'), ['url' => 'https://example.com'])
        ->assertRedirect()
        ->assertSessionHasErrors(['url' => 'Zu viele Prüfungen. Bitte warte eine Minute.']);
    expect(Audit::count())->toBe(5);
});
