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

it('shows a cancelled audit as finished and cancelled', function () {
    $audit = Audit::factory()->create(['status' => AuditStatus::Cancelled]);

    $this->get(route('audits.show', $audit))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('audit.isFinished', true)
            ->where('audit.isCancelled', true));
});

it('lists the findings in the order of the rules', function () {
    $audit = Audit::factory()->create(['status' => AuditStatus::Done]);
    Finding::factory()->for($audit)->create(['accessibility_rule' => AccessibilityRule::DocumentLanguage]);
    Finding::factory()->for($audit)->create(['accessibility_rule' => AccessibilityRule::ImageAlt]);

    $this->get(route('audits.show', $audit))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('audit.findingGroups.0.accessibilityRule', 'image_alt')
            ->where('audit.findingGroups.1.accessibilityRule', 'document_language'));
});

it('shows a summary per rule with counts, status and sentence', function () {
    $audit = Audit::factory()->create([
        'status' => AuditStatus::Done,
        'checked_counts' => ['image_alt' => 10, 'document_language' => 1, 'document_title' => 1, 'form_label' => 0, 'link_text' => 45],
    ]);
    Finding::factory()->for($audit)->create(['accessibility_rule' => AccessibilityRule::DocumentLanguage]);
    Finding::factory()->count(4)->for($audit)->create(['accessibility_rule' => AccessibilityRule::LinkText]);
    Finding::factory()->count(6)->for($audit)->create(['accessibility_rule' => AccessibilityRule::ImageAlt]);

    $this->get(route('audits.show', $audit))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('audit.ruleSummaries', 5)
            ->where('audit.ruleSummaries.0.accessibilityRule', 'image_alt')
            ->where('audit.ruleSummaries.0.checkedCount', 10)
            ->where('audit.ruleSummaries.0.findingCount', 6)
            ->where('audit.ruleSummaries.0.result', 'failed')
            ->where('audit.ruleSummaries.1.result', 'failed')
            ->where('audit.ruleSummaries.1.summarySentence', 'Sprachangabe fehlt')
            ->where('audit.ruleSummaries.2.result', 'passed')
            ->where('audit.ruleSummaries.3.result', 'not_applicable')
            ->where('audit.ruleSummaries.4.result', 'partial')
            ->where('audit.ruleSummaries.4.summarySentence', '4 von 45 Links ohne Text'));
});

it('shows the real finding count when fewer snippets are stored', function () {
    $audit = Audit::factory()->create([
        'status' => AuditStatus::Done,
        'checked_counts' => ['image_alt' => 80, 'document_language' => 1, 'document_title' => 1, 'form_label' => 0, 'link_text' => 0],
        'finding_counts' => ['image_alt' => 60, 'document_language' => 0, 'document_title' => 0, 'form_label' => 0, 'link_text' => 0],
    ]);
    Finding::factory()->count(2)->for($audit)->create(['accessibility_rule' => AccessibilityRule::ImageAlt]);

    $this->get(route('audits.show', $audit))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('audit.findingGroups.0.snippets', 2)
            ->where('audit.findingGroups.0.findingCount', 60)
            ->where('audit.ruleSummaries.0.findingCount', 60)
            ->where('audit.ruleSummaries.0.summarySentence', '60 von 80 Bildern ohne Alternativtext'));
});

it('falls back to the stored snippets for the finding count of an audit stored without finding counts', function () {
    $audit = Audit::factory()->create(['status' => AuditStatus::Done]);
    Finding::factory()->count(3)->for($audit)->create(['accessibility_rule' => AccessibilityRule::ImageAlt]);

    $this->get(route('audits.show', $audit))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('audit.findingGroups.0.findingCount', 3));
});

it('shows no rule summaries for an audit stored without checked counts', function () {
    $audit = Audit::factory()->create(['status' => AuditStatus::Done]);

    $this->get(route('audits.show', $audit))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('audit.ruleSummaries', null));
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
