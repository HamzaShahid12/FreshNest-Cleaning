<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactEnquiry;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\NewsletterSubscriber;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\ServiceExtra;
use App\Models\Setting;
use App\Support\Notify;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Public marketing website. */
class PageController extends Controller
{
    private function serviceCard(Service $s): array
    {
        return [
            'id' => $s->id,
            'slug' => $s->slug,
            'name' => $s->name,
            'short_description' => $s->short_description,
            'image_url' => $s->image_url,
            'base_price' => (float) $s->base_price,
            'pricing_method' => $s->pricing_method,
            'duration_minutes' => $s->duration_minutes,
            'category' => $s->category?->name,
        ];
    }

    private function reviewCards(int $limit = 6): array
    {
        return Review::with('user')->where('is_approved', true)->latest()->limit($limit)->get()
            ->map(fn (Review $r) => [
                'id' => $r->id, 'name' => $r->publicName(), 'rating' => $r->rating,
                'comment' => $r->comment, 'date' => $r->created_at->format('M j, Y'),
            ])->all();
    }

    private function reviewStats(): array
    {
        $q = Review::where('is_approved', true);

        return ['count' => (clone $q)->count(), 'average' => round((float) (clone $q)->avg('rating'), 1)];
    }

    public function home(): Response
    {
        return Inertia::render('Public/Home', [
            'services' => Service::active()->with('category')->orderBy('sort_order')->limit(8)->get()->map(fn ($s) => $this->serviceCard($s)),
            'packages' => Service::active()->where('is_featured', true)->orderBy('sort_order')->limit(3)->get()->map(fn ($s) => $this->serviceCard($s) + ['included' => array_slice($s->included ?? [], 0, 6)]),
            'reviews' => $this->reviewCards(6),
            'reviewStats' => $this->reviewStats(),
            'faqs' => Faq::where('is_active', true)->orderBy('sort_order')->limit(6)->get(['id', 'question', 'answer']),
            'areas' => ServiceArea::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'state', 'country']),
            'gallery' => GalleryItem::where('is_active', true)->orderBy('sort_order')->limit(3)->get(),
            'posts' => BlogPost::published()->latest('published_at')->limit(3)->get(['id', 'title', 'slug', 'excerpt', 'cover_image', 'published_at']),
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('Public/About');
    }

    public function services(): Response
    {
        return Inertia::render('Public/Services/Index', [
            'services' => Service::active()->with('category')->orderBy('sort_order')->get()->map(fn ($s) => $this->serviceCard($s)),
        ]);
    }

    public function service(Service $service): Response
    {
        abort_unless($service->is_active, 404);
        $service->load('category');

        return Inertia::render('Public/Services/Show', [
            'service' => $this->serviceCard($service) + [
                'description' => $service->description,
                'included' => $service->included ?? [],
                'benefits' => $service->benefits ?? [],
                'faqs' => $service->faqPairs(),
                'seo_title' => $service->seo_title,
                'seo_description' => $service->seo_description,
            ],
            'related' => Service::active()->with('category')->where('id', '!=', $service->id)
                ->when($service->service_category_id, fn ($q) => $q->where('service_category_id', $service->service_category_id))
                ->orderBy('sort_order')->limit(3)->get()->map(fn ($s) => $this->serviceCard($s)),
        ]);
    }

    public function pricing(): Response
    {
        return Inertia::render('Public/Pricing', [
            'services' => Service::active()->with('category')->orderBy('sort_order')->get()->map(fn ($s) => $this->serviceCard($s)),
            'packages' => Service::active()->where('is_featured', true)->orderBy('sort_order')->limit(3)->get()->map(fn ($s) => $this->serviceCard($s) + ['included' => $s->included ?? []]),
            'extras' => ServiceExtra::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'description', 'price']),
            'frequencies' => [
                ['key' => 'one_time', 'label' => 'One-time', 'discount' => 0],
                ['key' => 'weekly', 'label' => 'Weekly', 'discount' => Setting::float('discount_weekly')],
                ['key' => 'biweekly', 'label' => 'Every 2 weeks', 'discount' => Setting::float('discount_biweekly')],
                ['key' => 'monthly', 'label' => 'Monthly', 'discount' => Setting::float('discount_monthly')],
            ],
            'rates' => [
                'bedroom' => Setting::float('bedroom_rate'),
                'bathroom' => Setting::float('bathroom_rate'),
                'sqft_included' => Setting::int('sqft_included'),
                'sqft_block_size' => Setting::int('sqft_block_size'),
                'sqft_block_rate' => Setting::float('sqft_block_rate'),
            ],
        ]);
    }

    public function gallery(): Response
    {
        return Inertia::render('Public/Gallery', [
            'items' => GalleryItem::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function reviews(): Response
    {
        return Inertia::render('Public/Reviews', [
            'reviews' => $this->reviewCards(50),
            'stats' => $this->reviewStats(),
        ]);
    }

    public function areas(): Response
    {
        return Inertia::render('Public/Areas/Index', [
            'areas' => ServiceArea::where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'slug' => $a->slug, 'state' => $a->state, 'country' => $a->country, 'description' => $a->description]),
        ]);
    }

    public function area(ServiceArea $area): Response
    {
        abort_unless($area->is_active, 404);

        return Inertia::render('Public/Areas/Show', [
            'area' => ['id' => $area->id, 'name' => $area->name, 'slug' => $area->slug, 'state' => $area->state, 'country' => $area->country,
                'description' => $area->description, 'zips' => $area->zipList()],
            'services' => Service::active()->orderBy('sort_order')->limit(6)->get()->map(fn ($s) => $this->serviceCard($s)),
        ]);
    }

    /** Public availability check: the visitor types a ZIP/postal code or a city. */
    public function checkArea(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        abort_unless(mb_strlen($q) >= 2 && mb_strlen($q) <= 100, 422, 'Enter your ZIP / postal code or city.');

        return response()->json(['q' => $q, 'served' => ServiceArea::serves($q, $q) || ServiceArea::serves(null, $q)]);
    }

    public function checkZip(Request $request): JsonResponse
    {
        $zip = (string) $request->query('zip', '');
        abort_unless(preg_match('/^\d{5}(-\d{4})?$/', $zip) === 1, 422, 'Enter a valid 5-digit ZIP code.');

        return response()->json(['zip' => $zip, 'served' => ServiceArea::serves($zip)]);
    }

    public function contact(): Response
    {
        return Inertia::render('Public/Contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        // Honeypot: real visitors leave this hidden field empty.
        if (filled($request->input('website'))) {
            return back()->with('success', 'Thanks! We will be in touch shortly.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        $enquiry = ContactEnquiry::create($data + ['user_id' => $request->user()?->id, 'status' => 'new']);
        Notify::admins('admin_contact_received', ['name' => $enquiry->name, 'message' => $enquiry->message], route('admin.resource.index', 'enquiries'));

        return back()->with('success', 'Thanks! Your message was sent and we will reply soon.');
    }

    public function newsletter(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:190']]);
        NewsletterSubscriber::firstOrCreate(['email' => strtolower($data['email'])], ['subscribed_at' => now()]);

        return back()->with('success', 'You are subscribed. Thank you!');
    }

    public function faq(): Response
    {
        return Inertia::render('Public/Faq', [
            'faqs' => Faq::where('is_active', true)->orderBy('sort_order')->get(['id', 'category', 'question', 'answer']),
        ]);
    }

    public function blog(Request $request): Response
    {
        $category = $request->query('category');

        return Inertia::render('Public/Blog/Index', [
            'posts' => BlogPost::published()->with('category')
                ->when($category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $category)))
                ->latest('published_at')->paginate(9)->withQueryString()
                ->through(fn ($p) => [
                    'id' => $p->id, 'title' => $p->title, 'slug' => $p->slug, 'excerpt' => $p->excerpt,
                    'image_url' => $p->image_url, 'category' => $p->category?->name,
                    'date' => optional($p->published_at ?? $p->created_at)->format('M j, Y'),
                ]),
            'categories' => BlogCategory::orderBy('name')->get(['id', 'name', 'slug']),
            'activeCategory' => $category,
        ]);
    }

    public function post(BlogPost $post): Response
    {
        abort_unless($post->is_published && (! $post->published_at || $post->published_at->isPast()), 404);
        $post->load('category', 'author');

        return Inertia::render('Public/Blog/Show', [
            'post' => [
                'id' => $post->id, 'title' => $post->title, 'slug' => $post->slug, 'excerpt' => $post->excerpt,
                'body' => $post->body, // rendered as escaped text by the frontend (never v-html)
                'image_url' => $post->image_url, 'category' => $post->category?->name,
                'author' => $post->author?->name,
                'date' => optional($post->published_at ?? $post->created_at)->format('F j, Y'),
                'seo_title' => $post->seo_title, 'seo_description' => $post->seo_description,
            ],
            'related' => BlogPost::published()->where('id', '!=', $post->id)->latest('published_at')->limit(3)
                ->get(['id', 'title', 'slug', 'excerpt']),
        ]);
    }

    public function legal(string $slug): Response
    {
        abort_unless(in_array($slug, ['privacy', 'terms', 'cancellation'], true), 404);

        return Inertia::render('Public/Legal', ['slug' => $slug, 'cancellationHours' => Setting::int('cancellation_hours', 24)]);
    }
}
