<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $values = Setting::map();
        $groups = [];
        foreach (config('freshnest.settings') as $key => $group) {
            $groups[] = [
                'key' => $key,
                'label' => $group['label'],
                'fields' => collect($group['fields'])->map(fn ($f, $k) => ['key' => $k, 'label' => $f['label'], 'type' => $f['type'], 'value' => $values[$k] ?? ''])->values(),
            ];
        }

        return Inertia::render('Admin/Settings', ['groups' => $groups]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (config('freshnest.settings') as $group) {
            foreach ($group['fields'] as $key => $field) {
                $rules[$key] = match (true) {
                    $field['type'] === 'number' => ['nullable', 'numeric', 'min:0', 'max:100000'],
                    $field['type'] === 'textarea' => ['nullable', 'string', 'max:4000'],
                    $key === 'business_lat' => ['nullable', 'numeric', 'between:-90,90'],
                    $key === 'business_lng' => ['nullable', 'numeric', 'between:-180,180'],
                    str_starts_with($key, 'social_') || $key === 'maps_embed_url' => ['nullable', 'url', 'max:500'],
                    str_starts_with($key, 'slot_') && ! str_contains($key, 'interval') => ['nullable', 'date_format:H:i'],
                    default => ['nullable', 'string', 'max:500'],
                };
            }
        }
        $data = $request->validate($rules);

        foreach ($data as $key => $value) {
            Setting::put($key, $value === null ? '' : (string) $value);
        }
        AuditLog::record('settings.updated', null, 'Business settings updated');

        return back()->with('success', 'Settings saved.');
    }
}
