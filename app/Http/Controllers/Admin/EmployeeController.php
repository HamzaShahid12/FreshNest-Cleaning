<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Service;
use App\Models\User;
use App\Support\BookingView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('Admin/Employees/Index', [
            'employees' => User::where('role', 'staff')->with('employeeProfile')->withCount(['assignedBookings as completed_jobs' => fn ($q) => $q->where('status', 'completed')])
                ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
                ->orderBy('name')->paginate(15)->withQueryString()
                ->through(fn ($u) => [
                    'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'is_active' => $u->is_active,
                    'job_title' => $u->employeeProfile?->job_title, 'completed_jobs' => $u->completed_jobs,
                    'hours' => ($u->employeeProfile?->work_start ?? '08:00').' - '.($u->employeeProfile?->work_end ?? '17:00'),
                ]),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Employees/Form', ['employee' => null, 'services' => $this->services()]);
    }

    private function services(): array
    {
        return Service::orderBy('name')->get(['id', 'name'])->all();
    }

    private function rules(?User $employee = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($employee?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$employee ? 'nullable' : 'required', Password::min(10)->letters()->mixedCase()->numbers()],
            'is_active' => ['boolean'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:services,id'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:1,7'],
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i', 'after:work_start'],
            'emergency_contact' => ['nullable', 'string', 'max:190'],
            'is_available' => ['boolean'],
        ];
    }

    private function persist(Request $request, User $user, array $data): void
    {
        $user->fill(['name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null]);
        $user->forceFill(['role' => 'staff', 'is_active' => $data['is_active'] ?? true]);
        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
            $user->must_change_password = true;
        }
        if (! $user->exists) {
            $user->email_verified_at = now();
        }
        $user->save();

        $user->employeeProfile()->updateOrCreate([], [
            'job_title' => $data['job_title'] ?? null,
            'bio' => $data['bio'] ?? null,
            'skills' => array_map('intval', $data['skills'] ?? []),
            'working_days' => array_map('intval', $data['working_days']),
            'work_start' => $data['work_start'],
            'work_end' => $data['work_end'],
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'is_available' => $data['is_available'] ?? true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $user = new User;
        $this->persist($request, $user, $data);
        AuditLog::record('employee.created', $user, "Employee {$user->email} created");

        return redirect()->route('admin.employees.show', $user)->with('success', 'Employee added. They must change their password at first sign-in.');
    }

    public function show(User $employee): Response
    {
        abort_unless($employee->role === 'staff', 404);
        $employee->load('employeeProfile');

        return Inertia::render('Admin/Employees/Show', [
            'employee' => [
                'id' => $employee->id, 'name' => $employee->name, 'email' => $employee->email, 'phone' => $employee->phone,
                'is_active' => $employee->is_active, 'profile' => $employee->employeeProfile,
                'skill_names' => Service::whereIn('id', $employee->employeeProfile?->skills ?? [])->pluck('name'),
            ],
            'upcoming' => $employee->assignedBookings()->with('service')->active()->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->get()->map(fn ($b) => BookingView::summary($b)),
            'history' => $employee->assignedBookings()->with('service')->where('status', 'completed')->orderByDesc('scheduled_at')->limit(30)->get()->map(fn ($b) => BookingView::summary($b)),
        ]);
    }

    public function edit(User $employee): Response
    {
        abort_unless($employee->role === 'staff', 404);
        $employee->load('employeeProfile');
        $p = $employee->employeeProfile;

        return Inertia::render('Admin/Employees/Form', [
            'employee' => [
                'id' => $employee->id, 'name' => $employee->name, 'email' => $employee->email, 'phone' => $employee->phone, 'is_active' => $employee->is_active,
                'job_title' => $p?->job_title, 'bio' => $p?->bio, 'skills' => $p?->skills ?? [], 'working_days' => $p?->working_days ?? [1, 2, 3, 4, 5],
                'work_start' => $p?->work_start ?? '08:00', 'work_end' => $p?->work_end ?? '17:00',
                'emergency_contact' => $p?->emergency_contact, 'is_available' => $p?->is_available ?? true,
            ],
            'services' => $this->services(),
        ]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        abort_unless($employee->role === 'staff', 404);
        $data = $request->validate($this->rules($employee));
        $this->persist($request, $employee, $data);
        AuditLog::record('employee.updated', $employee, "Employee {$employee->email} updated");

        return redirect()->route('admin.employees.show', $employee)->with('success', 'Employee updated.');
    }
}
