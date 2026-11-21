<div class="space-y-6">
    {{-- Top Metrics & Kiosk Branch Selector --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Check-in Branch</label>
            <select wire:model.live="activeBranchId" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @foreach ($accessibleBranches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                @endforeach
            </select>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Allowed Check-ins ({{ $dateFilter }})</div>
            <div class="mt-1 text-3xl font-bold text-emerald-600">{{ $todayAllowedCount }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Currently Checked In</div>
            <div class="mt-1 text-3xl font-bold text-slate-900">{{ $currentlyInsideCount }}</div>
        </div>
    </div>

    {{-- Instant Scan Result Banner --}}
    @if ($lastResult)
        <div class="rounded-2xl p-5 border-2 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 {{ $lastResult['allowed'] ? 'bg-emerald-50 border-emerald-500 text-emerald-950' : 'bg-rose-50 border-rose-500 text-rose-950' }}">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $lastResult['allowed'] ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }}">
                        {{ $lastResult['allowed'] ? 'ACCESS ALLOWED' : 'ACCESS DENIED' }}
                    </span>
                    @if (!empty($lastResult['member_name']))
                        <span class="font-bold text-base">{{ $lastResult['member_name'] }}</span>
                        <span class="font-mono text-xs opacity-75">({{ $lastResult['member_code'] }})</span>
                    @endif
                </div>
                <p class="text-sm font-medium mt-1.5">{{ $lastResult['message'] }}</p>
                @if (!empty($lastResult['plan_name']))
                    <p class="text-xs opacity-80 mt-0.5">Plan: {{ $lastResult['plan_name'] }} · {{ $lastResult['days_left'] }} days remaining</p>
                @endif
            </div>
            <button wire:click="$set('lastResult', null)" class="text-xs font-semibold underline self-start sm:self-center">Dismiss</button>
        </div>
    @endif

    @if ($canCheckIn)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Webcam QR Scanner --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4"
             wire:ignore
             x-data="{
                scanning: false,
                scanner: null,
                error: '',
                async startCamera() {
                    this.error = '';
                    if (typeof Html5Qrcode === 'undefined') {
                        this.error = 'QR scanner library failed to load. Check internet connection or use manual input.';
                        return;
                    }
                    try {
                        this.scanner = new Html5Qrcode('qr-reader');
                        await this.scanner.start(
                            { facingMode: 'environment' },
                            { fps: 10, qrbox: { width: 220, height: 220 } },
                            (decodedText) => {
                                $wire.scanCode(decodedText, 'qr');
                            },
                            () => {}
                        );
                        this.scanning = true;
                    } catch (e) {
                        this.error = 'Unable to access camera: ' + (e?.message || e);
                    }
                },
                async stopCamera() {
                    if (this.scanner && this.scanning) {
                        await this.scanner.stop();
                        this.scanner.clear();
                    }
                    this.scanning = false;
                }
             }">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900 text-sm">Webcam QR Scanner</h3>
                    <p class="text-xs text-slate-500">Scan a member's portal QR pass with your front-desk camera.</p>
                </div>
                <button type="button" x-show="!scanning" @click="startCamera()" class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                    Start Camera
                </button>
                <button type="button" x-show="scanning" @click="stopCamera()" style="display:none;" class="px-3.5 py-2 rounded-lg bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700">
                    Stop Camera
                </button>
            </div>

            <div id="qr-reader" class="w-full rounded-xl overflow-hidden bg-slate-950 min-h-[200px] flex items-center justify-center text-slate-400 text-xs">
                Click "Start Camera" to scan member QR codes
            </div>
            <p x-show="error" x-text="error" class="text-xs text-rose-600"></p>
        </div>

        {{-- Barcode Gun / Manual Check-in --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-5">
            <div>
                <h3 class="font-semibold text-slate-900 text-sm">Quick Code / USB Scanner Input</h3>
                <p class="text-xs text-slate-500 mt-0.5">Scan with a USB QR gun or enter a Member ID (e.g. IP-2026-0001).</p>
                <form wire:submit="scanCode(null, 'manual')" class="mt-3 flex gap-2">
                    <input type="text" wire:model="scanInput" placeholder="Member ID or QR token..."
                           class="flex-1 rounded-lg border-slate-300 text-sm font-mono focus:border-emerald-500 focus:ring-emerald-500">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                        Check In
                    </button>
                </form>
            </div>

            <hr class="border-slate-100">

            <div>
                <h3 class="font-semibold text-slate-900 text-sm">Search Member by Name</h3>
                <input type="text" wire:model.live.debounce.250ms="memberSearch" placeholder="Type at least 2 characters..."
                       class="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">

                @if ($searchResults->isNotEmpty())
                    <div class="mt-2 divide-y divide-slate-100 border border-slate-200 rounded-lg overflow-hidden">
                        @foreach ($searchResults as $m)
                            <div class="px-3.5 py-2.5 flex items-center justify-between text-xs bg-white hover:bg-slate-50">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $m->user->name }}</div>
                                    <div class="text-slate-500">{{ $m->member_code }} · {{ ucfirst($m->status) }}</div>
                                </div>
                                <button wire:click="manualCheckIn({{ $m->id }})" class="px-3 py-1.5 rounded-md bg-emerald-600 text-white font-medium hover:bg-emerald-700">
                                    Check In
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Attendance Log --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="font-semibold text-slate-900 text-sm">Attendance Log</h3>
            <input type="date" wire:model.live="dateFilter" class="rounded-lg border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Time</th>
                        <th class="px-5 py-3 text-left">Member</th>
                        <th class="px-5 py-3 text-center">Method</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Check Out</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($attendances as $row)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5 font-mono text-slate-700">
                                {{ $row->checked_in_at->format('h:i:s A') }}
                                @if ($row->checked_out_at)
                                    <div class="text-[11px] text-slate-400">Out: {{ $row->checked_out_at->format('h:i A') }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.members.show', $row->member) }}" class="font-semibold text-slate-900 hover:text-emerald-700">
                                    {{ $row->member->user->name }}
                                </a>
                                <div class="text-slate-500">{{ $row->member->member_code }}</div>
                                @if ($row->denial_reason)
                                    <div class="text-rose-600 font-medium mt-0.5">{{ $row->denial_reason }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center uppercase text-slate-600 font-medium">{{ $row->method }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full font-medium {{ $row->status === 'allowed' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700' }}">
                                    {{ ucfirst($row->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if ($row->status === 'allowed' && !$row->checked_out_at && $canCheckIn)
                                    <button wire:click="checkOut({{ $row->id }})" class="px-2.5 py-1 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100 font-medium">
                                        Check Out
                                    </button>
                                @elseif ($row->checked_out_at)
                                    <span class="text-slate-400">Checked out</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">No check-ins recorded for this date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attendances->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $attendances->links() }}</div>
        @endif
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</div>
