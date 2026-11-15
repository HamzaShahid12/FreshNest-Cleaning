<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Support\BookingView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $customers = User::where('role', 'customer')
            ->withCount('bookings')
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest('id')->paginate(15)->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
                'bookings_count' => $u->bookings_count, 'is_active' => $u->is_active, 'joined' => $u->created_at->format('M j, Y'),
            ]);

        return Inertia::render('Admin/Customers/Index', ['customers' => $customers, 'filters' => ['search' => $search]]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Customers/Form', ['customer' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $user = User::create([
            'name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null,
            // A random password the customer never sees; they use "Forgot password" to sign in the first time.
            'password' => Str::random(40),
        ]);
        $user->forceFill(['role' => 'customer', 'is_active' => true, 'admin_notes' => $data['admin_notes'] ?? null])->save();
        AuditLog::record('customer.created', $user, "Customer {$user->email} created");

        return redirect()->route('admin.customers.show', $user)->with('success', 'Customer added. They can set a password with "Forgot password".');
    }

    public function show(User $customer): Response
    {
        abort_unless($customer->role === 'customer', 404);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $customer->id, 'name' => $customer->name, 'email' => $customer->email, 'phone' => $customer->phone,
                'is_active' => $customer->is_active, 'admin_notes' => $customer->admin_notes,
                'joined' => $customer->created_at->format('M j, Y'), 'verified' => $customer->hasVerifiedEmail(),
            ],
            'stats' => [
                'bookings' => $customer->bookings()->count(),
                'spent' => (float) Payment::whereHas('booking', fn ($q) => $q->where('user_id', $customer->id))
                    ->whereIn('status', ['paid', 'partially_refunded'])->sum('amount'),
            ],
            'bookings' => $customer->bookings()->with(['service', 'employee'])->orderByDesc('scheduled_at')->limit(30)->get()->map(fn ($b) => BookingView::summary($b)),
            'payments' => Payment::with('booking')->whereHas('booking', fn ($q) => $q->where('user_id', $customer->id))->latest('id')->limit(30)->get()
                ->map(fn ($p) => ['id' => $p->id, 'booking' => $p->booking->reference, 'method' => $p->method, 'amount' => (float) $p->amount, 'status' => $p->status, 'date' => $p->created_at->format('M j, Y')]),
            'addresses' => $customer->addresses()->get(['id', 'label', 'street', 'unit', 'city', 'state', 'zip']),
        ]);
    }

    public function edit(User $customer): Response
    {
        abort_unless($customer->role === 'customer', 404);

        return Inertia::render('Admin/Customers/Form', [
            'customer' => $customer->only(['id', 'name', 'email', 'phone', 'admin_notes', 'is_active']),
        ]);
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === 'customer', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['boolean'],
        ]);

        $customer->fill(['name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null]);
        $customer->forceFill(['admin_notes' => $data['admin_notes'] ?? null, 'is_active' => $data['is_active'] ?? true])->save();
        AuditLog::record('customer.updated', $customer, "Customer {$customer->email} updated");

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer updated.');
    }
}
