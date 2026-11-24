<?php

use App\Enums\AuditStatus;
use App\Models\Audit;

it('cancels an audit that is pending or running', function (AuditStatus $status) {
    $audit = Audit::factory()->create(['status' => $status]);

    $this->post(route('audits.cancel', $audit))->assertRedirect(route('audits.show', $audit));

    expect($audit->refresh()->status)->toBe(AuditStatus::Cancelled);
})->with([AuditStatus::Pending, AuditStatus::Running]);

it('leaves a finished audit as it is', function (AuditStatus $status) {
    $audit = Audit::factory()->create(['status' => $status]);

    $this->post(route('audits.cancel', $audit))->assertRedirect(route('audits.show', $audit));

    expect($audit->refresh()->status)->toBe($status);
})->with([AuditStatus::Done, AuditStatus::Failed]);
