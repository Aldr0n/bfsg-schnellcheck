<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditResource;
use App\Models\Audit;
use App\Repositories\AuditRepository;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function __construct(private readonly AuditRepository $auditRepository) {}

    public function show(Audit $audit): Response
    {
        return Inertia::render('audits/show', [
            'audit' => new AuditResource($this->auditRepository->auditWithFindingsByRule($audit)),
        ]);
    }
}
