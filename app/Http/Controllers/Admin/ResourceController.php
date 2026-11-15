<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Support\AdminResources;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** One controller for every simple admin module defined in App\Support\AdminResources. */
class ResourceController extends Controller
{
    private function def(Request $request, string $resource): array
    {
        $def = AdminResources::find($resource);
        abort_unless($def, 404);
        abort_unless(in_array($request->user()->role, $def['roles'], true), 403, 'You do not have access to this module.');

        return $def;
    }

    private function optionsFor(array $def): array
    {
        $out = [];
        foreach ($def['fields'] as $f) {
            if (isset($f['options_model'])) {
                [$model, $label] = $f['options_model'];
                $out[$f['key']] = $model::orderBy($label)->get(['id', $label])->map(fn ($m) => ['value' => $m->id, 'label' => $m->{$label}])->all();
            } elseif (isset($f['options'])) {
                $out[$f['key']] = collect($f['options'])->map(fn ($l, $k) => ['value' => $k, 'label' => $l])->values()->all();
            }
        }

        return $out;
    }

    public function index(Request $request, string $resource): Response
    {
        $def = $this->def($request, $resource);
        /** @var class-string<Model> $model */
        $model = $def['model'];

        $query = $model::query()->with($def['with'] ?? []);
        if ($q = trim((string) $request->query('search', ''))) {
            $query->where(function ($w) use ($def, $q) {
                foreach ($def['search'] as $col) {
                    $w->orWhere($col, 'like', '%'.$q.'%');
                }
            });
        }
        if (isset($def['order'])) {
            $query->orderBy($def['order'][0], $def['order'][1]);
        }

        $rows = $query->paginate(15)->withQueryString()->through(function (Model $m) use ($def) {
            $row = ['id' => $m->getKey()];
            foreach ($def['columns'] as $c) {
                $row[$c['key']] = data_get($m, $c['key']);
            }

            return $row;
        });

        return Inertia::render('Admin/Resource/Index', [
            'resource' => $resource,
            'def' => Arr::only($def, ['title', 'singular', 'columns', 'can_create', 'readonly']),
            'rows' => $rows,
            'filters' => ['search' => $request->query('search', '')],
        ]);
    }

    public function create(Request $request, string $resource): Response
    {
        $def = $this->def($request, $resource);
        abort_if($def['readonly'] ?? false, 404);
        abort_if(($def['can_create'] ?? true) === false, 404);

        $values = [];
        foreach ($def['fields'] as $f) {
            $values[$f['key']] = $f['default'] ?? ($f['type'] === 'checkbox' ? false : '');
        }

        return Inertia::render('Admin/Resource/Form', [
            'resource' => $resource, 'def' => Arr::only($def, ['title', 'singular', 'fields']),
            'options' => $this->optionsFor($def), 'record' => null, 'values' => $values,
        ]);
    }

    public function edit(Request $request, string $resource, int $id): Response
    {
        $def = $this->def($request, $resource);
        abort_if($def['readonly'] ?? false, 404);
        $record = $def['model']::findOrFail($id);

        $values = [];
        $display = [];
        foreach ($def['fields'] as $f) {
            $raw = $record->{$f['key']};
            if ($f['type'] === 'password') {
                $values[$f['key']] = '';
            } elseif ($f['type'] === 'lines') {
                $values[$f['key']] = is_array($raw) ? implode("\n", $raw) : (string) $raw;
            } elseif ($f['type'] === 'image') {
                $values[$f['key']] = null; // upload only when replacing
                $display[$f['key'].'_url'] = $raw ? Storage::disk('public')->url($raw) : null;
            } elseif ($f['type'] === 'date') {
                $values[$f['key']] = $raw ? \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d') : '';
            } elseif ($f['type'] === 'display') {
                $display[$f['key']] = $raw;
            } else {
                $values[$f['key']] = $raw ?? ($f['type'] === 'checkbox' ? false : '');
            }
        }

        if ($resource === 'enquiries' && $record->status === 'new') {
            $record->update(['status' => 'read']);
            $values['status'] = 'read';
        }

        return Inertia::render('Admin/Resource/Form', [
            'resource' => $resource, 'def' => Arr::only($def, ['title', 'singular', 'fields']),
            'options' => $this->optionsFor($def), 'record' => ['id' => $record->getKey()],
            'values' => $values, 'display' => $display,
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $def = $this->def($request, $resource);
        abort_if($def['readonly'] ?? false, 404);
        abort_if(($def['can_create'] ?? true) === false, 404);

        return $this->save($request, $def, new ($def['model']));
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $def = $this->def($request, $resource);
        abort_if($def['readonly'] ?? false, 404);

        return $this->save($request, $def, $def['model']::findOrFail($id));
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        $def = $this->def($request, $resource);
        abort_if($def['readonly'] ?? false, 404);
        $record = $def['model']::findOrFail($id);

        if ($resource === 'users') {
            abort_if($record->id === $request->user()->id, 422, 'You cannot delete your own account.');
        }
        if ($resource === 'services' && $record->bookings()->exists()) {
            // Keep history intact: archive instead of hard delete (the model uses soft deletes anyway).
            $record->update(['is_active' => false]);
        }

        $record->delete();
        AuditLog::record('resource.deleted', $record, "Deleted {$def['singular']} #{$record->getKey()}");

        return redirect()->route('admin.resource.index', $resource)->with('success', ucfirst($def['singular']).' removed.');
    }

    private function save(Request $request, array $def, Model $record): RedirectResponse
    {
        $rules = [];
        foreach ($def['fields'] as $f) {
            if (($f['type'] ?? '') === 'display') {
                continue;
            }
            $rules[$f['key']] = str_replace('{id}', (string) ($record->getKey() ?? 'NULL'), $f['rules'] ?? 'nullable');
        }
        $data = $request->validate($rules);

        $payload = [];
        foreach ($def['fields'] as $f) {
            $key = $f['key'];
            if (! array_key_exists($key, $data) && ($f['type'] ?? '') !== 'checkbox') {
                continue;
            }

            switch ($f['type']) {
                case 'lines':
                    $payload[$key] = collect(preg_split('/\R/', (string) ($data[$key] ?? '')))->map('trim')->filter()->values()->all();
                    break;
                case 'checkbox':
                    $payload[$key] = (bool) ($data[$key] ?? false);
                    break;
                case 'image':
                    if ($request->hasFile($key)) {
                        if ($record->{$key}) {
                            Storage::disk('public')->delete($record->{$key});
                        }
                        // Randomised file name + validated image mime: never trust the client file name/extension.
                        $payload[$key] = $request->file($key)->store($f['folder'] ?? 'uploads', 'public');
                    }
                    break;
                case 'password':
                    break; // handled below
                default:
                    $value = $data[$key] ?? null;
                    if (! empty($f['upper']) && is_string($value)) {
                        $value = strtoupper(trim($value));
                    }
                    $payload[$key] = $value === '' ? null : $value;
            }
        }

        if (isset($def['slug_from'])) {
            $source = $payload[$def['slug_from']] ?? $record->{$def['slug_from']};
            if (! $record->exists || $record->{$def['slug_from']} !== $source || blank($record->slug)) {
                $payload['slug'] = $this->uniqueSlug($def['model'], (string) $source, $record);
            }
        }

        // Fields not covered by mass assignment protection need explicit handling.
        if (($def['special'] ?? null) === 'users') {
            return $this->saveUser($request, $def, $record, $payload, $data);
        }
        if ($def['key'] === 'blog') {
            $payload['user_id'] = $record->user_id ?? $request->user()->id;
            if (! empty($payload['is_published']) && empty($payload['published_at'])) {
                $payload['published_at'] = now();
            }
        }
        if ($def['key'] === 'coupons' && $payload['type'] === 'percent' && (float) $payload['value'] > 100) {
            throw ValidationException::withMessages(['value' => 'A percentage cannot be above 100.']);
        }
        if ($def['key'] === 'reviews') {
            $payload = Arr::only($payload, ['comment', 'is_approved']);
        }

        $record->fill($payload)->save();
        AuditLog::record($record->wasRecentlyCreated ? 'resource.created' : 'resource.updated', $record,
            ucfirst($def['singular']).' #'.$record->getKey().($record->wasRecentlyCreated ? ' created' : ' updated'));

        return redirect()->route('admin.resource.index', $def['key'])
            ->with('success', ucfirst($def['singular']).' saved.');
    }

    /** User accounts: role, active flag and password are privileged and set explicitly. */
    private function saveUser(Request $request, array $def, Model $user, array $payload, array $data): RedirectResponse
    {
        $actor = $request->user();
        abort_if($user->exists && $user->id === $actor->id && ($payload['role'] ?? $user->role) !== $user->role, 422, 'You cannot change your own role.');
        abort_if($user->exists && $user->id === $actor->id && empty($payload['is_active']), 422, 'You cannot deactivate your own account.');

        // Safety net: never remove the last active Super Admin.
        if ($user->exists && $user->role === 'super_admin' && (($payload['role'] ?? 'super_admin') !== 'super_admin' || empty($payload['is_active']))) {
            $others = \App\Models\User::where('role', 'super_admin')->where('is_active', true)->where('id', '!=', $user->id)->count();
            abort_if($others === 0, 422, 'There must be at least one active Super Admin.');
        }

        $role = $payload['role'];
        $active = (bool) ($payload['is_active'] ?? false);
        unset($payload['role'], $payload['is_active']);

        $new = ! $user->exists;
        $user->fill(Arr::only($payload, ['name', 'email', 'phone']));
        $user->forceFill(['role' => $role, 'is_active' => $active]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
            $user->must_change_password = true;
        } elseif ($new) {
            throw ValidationException::withMessages(['password' => 'A password is required for new users.']);
        }
        if ($new) {
            $user->email_verified_at = now();
        }
        $user->save();

        if ($role === 'staff') {
            $user->employeeProfile()->firstOrCreate([], ['working_days' => [1, 2, 3, 4, 5], 'work_start' => '08:00', 'work_end' => '17:00']);
        }

        AuditLog::record($new ? 'user.created' : 'user.updated', $user, "User {$user->email} ({$role}) ".($new ? 'created' : 'updated'));

        return redirect()->route('admin.resource.index', 'users')->with('success', 'User saved.');
    }

    private function uniqueSlug(string $model, string $source, Model $record): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $i = 2;
        while ($model::query()->where('slug', $slug)->when($record->exists, fn ($q) => $q->where('id', '!=', $record->getKey()))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
