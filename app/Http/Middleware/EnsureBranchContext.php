<?php

namespace App\Http\Middleware;

use App\Services\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchContext
{
    public function __construct(protected BranchContext $branchContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->branchContext->initialize($request->user());

        return $next($request);
    }
}
