<?php

namespace App\Livewire\Admin\Classes;

use App\Models\Booking;
use App\Models\ClassSchedule;
use App\Models\GymClass;
use App\Models\Trainer;
use App\Models\User;
use App\Services\BranchContext;
use App\Services\ClassBookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class ClassManager extends Component
{
    public string $tab = 'schedules'; // schedules, classes, trainers

    // Trainer Modal
    public bool $showTrainerModal = false;
    public string $trainer_name = '';
    public string $trainer_email = '';
    public string $trainer_phone = '';
    public ?int $trainer_branch_id = null;
    public string $trainer_specialization = '';
    public string $trainer_certifications = '';
    public string $trainer_hourly_rate = '';

    // GymClass Modal
    public bool $showClassModal = false;
    public ?int $editingClassId = null;
    public string $class_name = '';
    public string $class_category = 'HIIT';
    public string $class_description = '';
    public ?int $class_branch_id = null;
    public ?int $class_trainer_id = null;
    public int $class_capacity = 20;
    public int $class_duration = 60;

    // Schedule Modal
    public bool $showScheduleModal = false;
    public ?int $schedule_class_id = null;
    public ?int $schedule_trainer_id = null;
    public string $schedule_date = '';
    public string $schedule_time = '08:00';
    public string $schedule_room = 'Main Studio';
    public int $schedule_capacity = 20;

    // Roster Modal
    public bool $showRosterModal = false;
    public ?int $rosterScheduleId = null;

    public function mount(): void
    {
        $this->schedule_date = now()->addDay()->toDateString();
    }

    public function openTrainerModal(): void
    {
        abort_unless(Auth::user()->can('trainers.manage'), 403);

        $this->reset(['trainer_name', 'trainer_email', 'trainer_phone', 'trainer_specialization', 'trainer_certifications', 'trainer_hourly_rate']);
        $branchContext = app(BranchContext::class);
        $this->trainer_branch_id = $branchContext->currentBranchId() ?? $branchContext->accessibleBranches()->first()?->id;
        $this->resetValidation();
        $this->showTrainerModal = true;
    }

    public function saveTrainer(): void
    {
        abort_unless(Auth::user()->can('trainers.manage'), 403);

        $allowedBranches = app(BranchContext::class)->accessibleBranches()->pluck('id')->all();

        $validated = $this->validate([
            'trainer_name' => ['required', 'string', 'max:120'],
            'trainer_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'trainer_phone' => ['nullable', 'string', 'max:30'],
            'trainer_branch_id' => ['required', Rule::in($allowedBranches)],
            'trainer_specialization' => ['nullable', 'string', 'max:120'],
            'trainer_certifications' => ['nullable', 'string', 'max:255'],
            'trainer_hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['trainer_name'],
                'email' => strtolower($validated['trainer_email']),
                'phone' => $validated['trainer_phone'] ?: null,
                'password' => Hash::make(Str::random(16)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            Role::findOrCreate('trainer', 'web');
            $user->assignRole('trainer');
            $user->branches()->syncWithoutDetaching([$validated['trainer_branch_id'] => ['is_primary' => true]]);

            Trainer::create([
                'user_id' => $user->id,
                'branch_id' => $validated['trainer_branch_id'],
                'specialization' => $validated['trainer_specialization'] ?: null,
                'certifications' => $validated['trainer_certifications'] ?: null,
                'hourly_rate' => $validated['trainer_hourly_rate'] !== '' ? $validated['trainer_hourly_rate'] : null,
                'is_active' => true,
            ]);
        });

        $this->showTrainerModal = false;
        session()->flash('success', 'Trainer profile created.');
    }

    public function openClassModal(?int $classId = null): void
    {
        abort_unless(Auth::user()->can('classes.manage'), 403);

        $this->resetValidation();
        $branchContext = app(BranchContext::class);

        if ($classId) {
            $gymClass = GymClass::findOrFail($classId);
            $this->editingClassId = $gymClass->id;
            $this->class_name = $gymClass->name;
            $this->class_category = (string) $gymClass->category;
            $this->class_description = (string) $gymClass->description;
            $this->class_branch_id = $gymClass->branch_id;
            $this->class_trainer_id = $gymClass->trainer_id;
            $this->class_capacity = $gymClass->default_capacity;
            $this->class_duration = $gymClass->duration_minutes;
        } else {
            $this->reset(['editingClassId', 'class_name', 'class_description', 'class_trainer_id']);
            $this->class_category = 'HIIT';
            $this->class_capacity = 20;
            $this->class_duration = 60;
            $this->class_branch_id = $branchContext->currentBranchId() ?? $branchContext->accessibleBranches()->first()?->id;
        }

        $this->showClassModal = true;
    }

    public function saveClass(): void
    {
        abort_unless(Auth::user()->can('classes.manage'), 403);

        $allowedBranches = app(BranchContext::class)->accessibleBranches()->pluck('id')->all();

        $validated = $this->validate([
            'class_name' => ['required', 'string', 'max:120'],
            'class_category' => ['nullable', 'string', 'max:50'],
            'class_description' => ['nullable', 'string', 'max:1000'],
            'class_branch_id' => ['required', Rule::in($allowedBranches)],
            'class_trainer_id' => ['nullable', 'exists:trainers,id'],
            'class_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'class_duration' => ['required', 'integer', 'min:15', 'max:240'],
        ]);

        $payload = [
            'name' => $validated['class_name'],
            'category' => $validated['class_category'] ?: null,
            'description' => $validated['class_description'] ?: null,
            'branch_id' => $validated['class_branch_id'],
            'trainer_id' => $validated['class_trainer_id'] ?: null,
            'default_capacity' => $validated['class_capacity'],
            'duration_minutes' => $validated['class_duration'],
        ];

        if ($this->editingClassId) {
            GymClass::findOrFail($this->editingClassId)->update($payload);
            session()->flash('success', 'Class updated.');
        } else {
            GymClass::create($payload);
            session()->flash('success', 'Class template created.');
        }

        $this->showClassModal = false;
    }

    public function openScheduleModal(): void
    {
        abort_unless(Auth::user()->can('classes.manage'), 403);

        $this->resetValidation();
        $this->schedule_class_id = null;
        $this->schedule_trainer_id = null;
        $this->schedule_date = now()->addDay()->toDateString();
        $this->schedule_time = '08:00';
        $this->schedule_room = 'Main Studio';
        $this->schedule_capacity = 20;
        $this->showScheduleModal = true;
    }

    public function updatedScheduleClassId($classId): void
    {
        if ($classId && ($gymClass = GymClass::find($classId))) {
            $this->schedule_trainer_id = $gymClass->trainer_id;
            $this->schedule_capacity = $gymClass->default_capacity;
        }
    }

    public function saveSchedule(): void
    {
        abort_unless(Auth::user()->can('classes.manage'), 403);

        $validated = $this->validate([
            'schedule_class_id' => ['required', 'exists:gym_classes,id'],
            'schedule_trainer_id' => ['nullable', 'exists:trainers,id'],
            'schedule_date' => ['required', 'date'],
            'schedule_time' => ['required', 'date_format:H:i'],
            'schedule_room' => ['nullable', 'string', 'max:80'],
            'schedule_capacity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $gymClass = GymClass::findOrFail($validated['schedule_class_id']);
        $startsAt = Carbon::parse($validated['schedule_date'] . ' ' . $validated['schedule_time']);
        $endsAt = $startsAt->copy()->addMinutes($gymClass->duration_minutes);

        ClassSchedule::create([
            'gym_class_id' => $gymClass->id,
            'branch_id' => $gymClass->branch_id,
            'trainer_id' => $validated['schedule_trainer_id'] ?: $gymClass->trainer_id,
            'room' => $validated['schedule_room'] ?: null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'capacity' => $validated['schedule_capacity'],
            'status' => 'scheduled',
        ]);

        $this->showScheduleModal = false;
        session()->flash('success', 'Class session scheduled.');
    }

    public function openRoster(int $scheduleId): void
    {
        $this->rosterScheduleId = $scheduleId;
        $this->showRosterModal = true;
    }

    public function markBookingStatus(int $bookingId, string $status): void
    {
        abort_unless(Auth::user()->can('bookings.manage') || Auth::user()->hasRole('trainer'), 403);

        $booking = Booking::findOrFail($bookingId);

        if ($status === 'cancelled') {
            app(ClassBookingService::class)->cancel($booking);
            session()->flash('success', 'Booking cancelled (next waitlisted member promoted if available).');

            return;
        }

        if (in_array($status, ['attended', 'no_show'], true)) {
            $booking->update(['status' => $status]);
        }
    }

    public function render()
    {
        $branchContext = app(BranchContext::class);
        $scopedBranchIds = $branchContext->scopedBranchIds();

        $trainers = Trainer::query()
            ->with(['user', 'branch'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->orderByDesc('id')
            ->get();

        $classes = GymClass::query()
            ->with(['branch', 'trainer.user'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->orderBy('name')
            ->get();

        $schedules = ClassSchedule::query()
            ->with(['gymClass', 'branch', 'trainer.user'])
            ->withCount([
                'confirmedBookings as confirmed_bookings_count',
                'waitlistedBookings as waitlisted_bookings_count',
            ])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at')
            ->limit(40)
            ->get();

        $rosterSchedule = $this->rosterScheduleId
            ? ClassSchedule::with(['gymClass', 'bookings.member.user'])->find($this->rosterScheduleId)
            : null;

        return view('livewire.admin.classes.class-manager', [
            'trainers' => $trainers,
            'classes' => $classes,
            'schedules' => $schedules,
            'rosterSchedule' => $rosterSchedule,
            'branches' => $branchContext->accessibleBranches(),
            'canManageTrainers' => Auth::user()->can('trainers.manage'),
            'canManageClasses' => Auth::user()->can('classes.manage'),
            'currencySymbol' => config('app.currency_symbol', '₱'),
        ]);
    }
}
