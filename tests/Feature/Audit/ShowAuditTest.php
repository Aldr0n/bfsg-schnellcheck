<?php

use App\Enums\AccessibilityRule;
use App\Enums\AuditStatus;
use App\Models\Audit;
use App\Models\Finding;
use Inertia\Testing\AssertableInertia;

it('shows the findings of a finished audit grouped by rule', function () {
    $audit = Audit::factory()->create(['status' => AuditStatus::Done]);
    Finding::factory()->for($audit)->create(['accessibility_rule' => AccessibilityRule::LinkText, 'snippet' => '<a href="/"></a>']);
    Finding::factory()->for($audit)->create(['accessibility_rule' => AccessibilityRule::ImageAlt, 'snippet' => '<img src="a.png">']);
    Finding::factory()->for($audit)->create(['accessibility_rule' => AccessibilityRule::ImageAlt, 'snippet' => '<img src="b.png">']);

    $this->get(route('audits.show', $audit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('audits/show')
            ->where('audit.url', $audit->url)
            ->where('audit.isFinished', true)
            ->has('audit.findingGroups', 2)
            ->where('audit.findingGroups.0.accessibilityRule', 'image_alt')
            ->where('audit.findingGroups.0.snippets', ['<img src="a.png">', '<img src="b.png">'])
            ->where('audit.findingGroups.1.accessibilityRule', 'link_text')
            ->where('audit.findingGroups.1.wcagCriteria', ['2.4.4', '4.1.2']));
});

it('shows a running audit as not finished', function () {
    $audit = Audit::factory()->create(['status' => AuditStatus::Running]);

    $this->get(route('audits.show', $audit))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('audit.isFinished', false)
            ->where('audit.statusLabel', 'Läuft')
            ->where('audit.findingGroups', []));
});

it('shows why a failed audit failed', function () {
    $audit = Audit::factory()->create(['status' => AuditStatus::Failed, 'error_message' => 'Die Seite ist nicht erreichbar.']);

    $this->get(route('audits.show', $audit))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('audit.isFinished', true)
            ->where('audit.errorMessage', 'Die Seite ist nicht erreichbar.'));
});
