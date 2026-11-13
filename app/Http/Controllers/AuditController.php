<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuditRequest;
use App\Http\Resources\AuditResource;
use App\Models\Audit;
use App\Repositories\AuditRepository;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function __construct(private readonly AuditService $auditService, private readonly AuditRepository $auditRepository) {}

    public function store(StoreAuditRequest $request): RedirectResponse
    {
        $audit = $this->auditService->start($request->string('url')->toString());

        return redirect()->route('audits.show', $audit);
    }

    public function show(Audit $audit): Response
    {
        return Inertia::render('audits/show', [
            'audit' => new AuditResource($this->auditRepository->auditWithFindingsByRule($audit)),
        ]);
    }
}
