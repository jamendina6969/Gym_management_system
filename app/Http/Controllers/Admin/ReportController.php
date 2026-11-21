<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Member;
use App\Models\Payment;
use App\Models\ProductSale;
use App\Services\BranchContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, BranchContext $branchContext): View
    {
        $data = $this->buildReportData($request, $branchContext);

        return view('admin.reports.index', $data);
    }

    public function exportCsv(Request $request, BranchContext $branchContext): StreamedResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        $data = $this->buildReportData($request, $branchContext);
        $filename = 'gym-report-' . $data['from'] . '-to-' . $data['to'] . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Date', 'Branch', 'Member', 'Method', 'Status', 'Amount (' . config('app.currency', 'PHP') . ')']);

            foreach ($data['payments'] as $p) {
                fputcsv($out, [
                    $p->invoice_number,
                    ($p->paid_at ?? $p->created_at)->format('Y-m-d H:i'),
                    $p->branch?->name ?? '',
                    $p->member?->user?->name ?? '',
                    $p->payment_method,
                    $p->status,
                    $p->amount,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request, BranchContext $branchContext): Response
    {
        abort_unless($request->user()->can('reports.export'), 403);

        $data = $this->buildReportData($request, $branchContext);
        $pdf = Pdf::loadView('pdf.report', $data)->setPaper('a4');

        return $pdf->download('gym-report-' . $data['from'] . '-to-' . $data['to'] . '.pdf');
    }

    protected function buildReportData(Request $request, BranchContext $branchContext): array
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();
        $scopedBranchIds = $branchContext->scopedBranchIds();

        $payments = Payment::query()
            ->with(['member.user', 'branch', 'subscription.plan'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$fromDate, $toDate])
            ->latest('paid_at')
            ->get();

        $membershipRevenue = (float) $payments->sum('amount');

        $retailRevenue = (float) ProductSale::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->whereBetween('sold_at', [$fromDate, $toDate])
            ->sum('total_amount');

        $byMethod = $payments->groupBy('payment_method')->map(fn ($group) => round((float) $group->sum('amount'), 2));

        $newMembers = Member::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('home_branch_id', $scopedBranchIds))
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();

        $checkins = Attendance::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'allowed')
            ->whereBetween('checked_in_at', [$fromDate, $toDate])
            ->count();

        $bookingsCount = Booking::query()
            ->whereIn('status', ['confirmed', 'attended'])
            ->whereHas('schedule', fn ($q) => $q
                ->when(count($scopedBranchIds) > 0, fn ($iq) => $iq->whereIn('branch_id', $scopedBranchIds))
                ->whereBetween('starts_at', [$fromDate, $toDate]))
            ->count();

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'currentBranch' => $branchContext->currentBranch(),
            'payments' => $payments,
            'membershipRevenue' => $membershipRevenue,
            'retailRevenue' => $retailRevenue,
            'totalRevenue' => $membershipRevenue + $retailRevenue,
            'byMethod' => $byMethod,
            'newMembers' => $newMembers,
            'checkins' => $checkins,
            'bookingsCount' => $bookingsCount,
            'currencySymbol' => config('app.currency_symbol', '₱'),
            'gymName' => config('app.name', 'IronPulse Gym'),
        ];
    }
}
