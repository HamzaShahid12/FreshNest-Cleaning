<?php

namespace App\Support;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactEnquiry;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\ServiceCategory;
use App\Models\ServiceExtra;
use App\Models\User;

/**
 * Declarative definitions for the simple CRUD modules of the admin panel.
 * One generic controller + one generic Vue page render all of them.
 *
 * Column types: text | money | bool | date | datetime | badge | rating | limit
 * Field types:  text | textarea | number | select | checkbox | date | image | lines | password | display
 */
class AdminResources
{
    private const ALL = ['super_admin', 'admin', 'manager'];

    private const OWNERS = ['super_admin', 'admin'];

    public static function all(): array
    {
        $yesNo = fn (string $key, string $label, bool $default = true) => ['key' => $key, 'label' => $label, 'type' => 'checkbox', 'default' => $default, 'rules' => 'boolean'];

        return [
            'categories' => [
                'title' => 'Service categories', 'singular' => 'category', 'model' => ServiceCategory::class, 'roles' => self::OWNERS,
                'search' => ['name'], 'order' => ['sort_order', 'asc'], 'slug_from' => 'name', 'soft' => true,
                'columns' => [['key' => 'name', 'label' => 'Name'], ['key' => 'sort_order', 'label' => 'Order'], ['key' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:120'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => 'nullable|string|max:1000'],
                    ['key' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0, 'rules' => 'nullable|integer|min:0'],
                    $yesNo('is_active', 'Active'),
                ],
            ],

            'services' => [
                'title' => 'Services', 'singular' => 'service', 'model' => Service::class, 'roles' => self::OWNERS,
                'with' => ['category'], 'search' => ['name', 'slug'], 'order' => ['sort_order', 'asc'], 'slug_from' => 'name', 'soft' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'Service'], ['key' => 'category.name', 'label' => 'Category'],
                    ['key' => 'base_price', 'label' => 'Base price', 'type' => 'money'], ['key' => 'pricing_method', 'label' => 'Pricing'],
                    ['key' => 'duration_minutes', 'label' => 'Minutes'], ['key' => 'is_active', 'label' => 'Published', 'type' => 'bool'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:150'],
                    ['key' => 'service_category_id', 'label' => 'Category', 'type' => 'select', 'options_model' => [ServiceCategory::class, 'name'], 'rules' => 'nullable|exists:service_categories,id'],
                    ['key' => 'short_description', 'label' => 'Short description', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => 'nullable|string|max:8000'],
                    ['key' => 'featured_image', 'label' => 'Featured image', 'type' => 'image', 'folder' => 'services', 'rules' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'],
                    ['key' => 'base_price', 'label' => 'Base price ($)', 'type' => 'number', 'step' => '0.01', 'default' => 0, 'rules' => 'required|numeric|min:0|max:100000'],
                    ['key' => 'pricing_method', 'label' => 'Pricing method', 'type' => 'select', 'default' => 'size_based', 'rules' => 'required|in:size_based,flat,quote',
                        'options' => ['size_based' => 'Size based (base + bedrooms/bathrooms/sq ft)', 'flat' => 'Flat price', 'quote' => 'Custom quotation']],
                    ['key' => 'duration_minutes', 'label' => 'Estimated duration (minutes)', 'type' => 'number', 'default' => 120, 'rules' => 'required|integer|min:15|max:1440'],
                    ['key' => 'included', 'label' => 'Included tasks (one per line)', 'type' => 'lines', 'rules' => 'nullable|string|max:4000'],
                    ['key' => 'benefits', 'label' => 'Benefits (one per line)', 'type' => 'lines', 'rules' => 'nullable|string|max:4000'],
                    ['key' => 'faqs', 'label' => 'FAQs (one per line: Question|Answer)', 'type' => 'lines', 'rules' => 'nullable|string|max:6000'],
                    ['key' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0, 'rules' => 'nullable|integer|min:0'],
                    $yesNo('is_active', 'Published (visible and bookable)'),
                    $yesNo('is_featured', 'Featured on homepage / pricing', false),
                    ['key' => 'seo_title', 'label' => 'SEO title', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
                    ['key' => 'seo_description', 'label' => 'SEO description', 'type' => 'textarea', 'rules' => 'nullable|string|max:320'],
                ],
            ],

            'extras' => [
                'title' => 'Extra services', 'singular' => 'extra', 'model' => ServiceExtra::class, 'roles' => self::OWNERS,
                'search' => ['name'], 'order' => ['sort_order', 'asc'], 'soft' => true,
                'columns' => [['key' => 'name', 'label' => 'Name'], ['key' => 'price', 'label' => 'Price', 'type' => 'money'], ['key' => 'duration_minutes', 'label' => 'Minutes'], ['key' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:120'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
                    ['key' => 'price', 'label' => 'Price ($)', 'type' => 'number', 'step' => '0.01', 'default' => 0, 'rules' => 'required|numeric|min:0|max:100000'],
                    ['key' => 'duration_minutes', 'label' => 'Extra minutes', 'type' => 'number', 'default' => 30, 'rules' => 'required|integer|min:0|max:600'],
                    ['key' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0, 'rules' => 'nullable|integer|min:0'],
                    $yesNo('is_active', 'Active'),
                ],
            ],

            'coupons' => [
                'title' => 'Coupons', 'singular' => 'coupon', 'model' => Coupon::class, 'roles' => self::OWNERS,
                'search' => ['code'], 'order' => ['id', 'desc'], 'soft' => true,
                'columns' => [
                    ['key' => 'code', 'label' => 'Code'], ['key' => 'type', 'label' => 'Type'], ['key' => 'value', 'label' => 'Value'],
                    ['key' => 'used_count', 'label' => 'Used'], ['key' => 'expires_on', 'label' => 'Expires', 'type' => 'date'], ['key' => 'is_active', 'label' => 'Active', 'type' => 'bool'],
                ],
                'fields' => [
                    ['key' => 'code', 'label' => 'Code', 'type' => 'text', 'rules' => 'required|alpha_dash|max:40|unique:coupons,code,{id}', 'upper' => true],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
                    ['key' => 'type', 'label' => 'Discount type', 'type' => 'select', 'default' => 'percent', 'rules' => 'required|in:percent,fixed', 'options' => ['percent' => 'Percent (%)', 'fixed' => 'Fixed amount ($)']],
                    ['key' => 'value', 'label' => 'Value', 'type' => 'number', 'step' => '0.01', 'rules' => 'required|numeric|min:0.01|max:100000'],
                    ['key' => 'min_subtotal', 'label' => 'Minimum order ($)', 'type' => 'number', 'step' => '0.01', 'default' => 0, 'rules' => 'nullable|numeric|min:0'],
                    ['key' => 'max_uses', 'label' => 'Max uses (blank = unlimited)', 'type' => 'number', 'rules' => 'nullable|integer|min:1'],
                    ['key' => 'starts_on', 'label' => 'Starts on', 'type' => 'date', 'rules' => 'nullable|date'],
                    ['key' => 'expires_on', 'label' => 'Expires on', 'type' => 'date', 'rules' => 'nullable|date'],
                    $yesNo('is_active', 'Active'),
                ],
            ],

            'reviews' => [
                'title' => 'Reviews', 'singular' => 'review', 'model' => Review::class, 'roles' => self::ALL, 'with' => ['user', 'service'],
                'search' => ['comment'], 'order' => ['id', 'desc'], 'can_create' => false,
                'columns' => [
                    ['key' => 'user.name', 'label' => 'Customer'], ['key' => 'service.name', 'label' => 'Service'],
                    ['key' => 'rating', 'label' => 'Rating', 'type' => 'rating'], ['key' => 'comment', 'label' => 'Comment', 'type' => 'limit'],
                    ['key' => 'is_approved', 'label' => 'Approved', 'type' => 'bool'],
                ],
                'fields' => [
                    ['key' => 'comment', 'label' => 'Comment', 'type' => 'textarea', 'rules' => 'nullable|string|max:1500'],
                    $yesNo('is_approved', 'Approved (visible on the website)', false),
                ],
            ],

            'enquiries' => [
                'title' => 'Contact enquiries', 'singular' => 'enquiry', 'model' => ContactEnquiry::class, 'roles' => self::ALL,
                'search' => ['name', 'email', 'subject'], 'order' => ['id', 'desc'], 'can_create' => false,
                'columns' => [
                    ['key' => 'name', 'label' => 'Name'], ['key' => 'email', 'label' => 'Email'], ['key' => 'subject', 'label' => 'Subject'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'], ['key' => 'created_at', 'label' => 'Received', 'type' => 'datetime'],
                ],
                'fields' => [
                    ['key' => 'message', 'label' => 'Message', 'type' => 'display'],
                    ['key' => 'phone', 'label' => 'Phone', 'type' => 'display'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'rules' => 'required|in:new,read,replied,closed', 'options' => ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'closed' => 'Closed']],
                    ['key' => 'admin_notes', 'label' => 'Internal notes', 'type' => 'textarea', 'rules' => 'nullable|string|max:3000'],
                ],
            ],

            'blog' => [
                'title' => 'Blog posts', 'singular' => 'post', 'model' => BlogPost::class, 'roles' => self::ALL, 'with' => ['category'],
                'search' => ['title', 'slug'], 'order' => ['id', 'desc'], 'slug_from' => 'title', 'soft' => true,
                'columns' => [['key' => 'title', 'label' => 'Title'], ['key' => 'category.name', 'label' => 'Category'], ['key' => 'is_published', 'label' => 'Published', 'type' => 'bool'], ['key' => 'published_at', 'label' => 'Date', 'type' => 'date']],
                'fields' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|string|max:200'],
                    ['key' => 'blog_category_id', 'label' => 'Category', 'type' => 'select', 'options_model' => [BlogCategory::class, 'name'], 'rules' => 'nullable|exists:blog_categories,id'],
                    ['key' => 'excerpt', 'label' => 'Excerpt', 'type' => 'textarea', 'rules' => 'nullable|string|max:400'],
                    ['key' => 'body', 'label' => 'Body (plain text; blank line = new paragraph)', 'type' => 'textarea', 'rows' => 12, 'rules' => 'required|string|max:50000'],
                    ['key' => 'cover_image', 'label' => 'Cover image', 'type' => 'image', 'folder' => 'blog', 'rules' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'],
                    $yesNo('is_published', 'Published', false),
                    ['key' => 'published_at', 'label' => 'Publish date', 'type' => 'date', 'rules' => 'nullable|date'],
                    ['key' => 'seo_title', 'label' => 'SEO title', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
                    ['key' => 'seo_description', 'label' => 'SEO description', 'type' => 'textarea', 'rules' => 'nullable|string|max:320'],
                ],
            ],

            'blog-categories' => [
                'title' => 'Blog categories', 'singular' => 'category', 'model' => BlogCategory::class, 'roles' => self::ALL,
                'search' => ['name'], 'order' => ['name', 'asc'], 'slug_from' => 'name',
                'columns' => [['key' => 'name', 'label' => 'Name'], ['key' => 'slug', 'label' => 'Slug']],
                'fields' => [['key' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:100']],
            ],

            'faqs' => [
                'title' => 'FAQs', 'singular' => 'FAQ', 'model' => Faq::class, 'roles' => self::ALL,
                'search' => ['question', 'category'], 'order' => ['sort_order', 'asc'],
                'columns' => [['key' => 'category', 'label' => 'Category'], ['key' => 'question', 'label' => 'Question', 'type' => 'limit'], ['key' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'category', 'label' => 'Category', 'type' => 'text', 'default' => 'General', 'rules' => 'required|string|max:60'],
                    ['key' => 'question', 'label' => 'Question', 'type' => 'text', 'rules' => 'required|string|max:255'],
                    ['key' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'rules' => 'required|string|max:4000'],
                    ['key' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0, 'rules' => 'nullable|integer|min:0'],
                    $yesNo('is_active', 'Active'),
                ],
            ],

            'areas' => [
                'title' => 'Service areas', 'singular' => 'area', 'model' => ServiceArea::class, 'roles' => self::OWNERS,
                'search' => ['name', 'zip_codes'], 'order' => ['sort_order', 'asc'], 'slug_from' => 'name',
                'columns' => [['key' => 'name', 'label' => 'Area'], ['key' => 'state', 'label' => 'State'], ['key' => 'country', 'label' => 'Country'], ['key' => 'zip_codes', 'label' => 'Postal codes', 'type' => 'limit'], ['key' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'name', 'label' => 'City / area name', 'type' => 'text', 'rules' => 'required|string|max:120'],
                    ['key' => 'state', 'label' => 'State / province / region (optional)', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                    ['key' => 'country', 'label' => 'Country', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                    ['key' => 'zip_codes', 'label' => 'ZIP / postal codes (optional, comma separated). Leave empty to match customers by city name only.', 'type' => 'textarea', 'rules' => 'nullable|string|max:3000'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rules' => 'nullable|string|max:2000'],
                    ['key' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0, 'rules' => 'nullable|integer|min:0'],
                    $yesNo('is_active', 'Active'),
                ],
            ],

            'gallery' => [
                'title' => 'Gallery', 'singular' => 'gallery item', 'model' => GalleryItem::class, 'roles' => self::ALL,
                'search' => ['title'], 'order' => ['sort_order', 'asc'],
                'columns' => [['key' => 'title', 'label' => 'Title'], ['key' => 'category', 'label' => 'Category', 'type' => 'badge'], ['key' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|string|max:150'],
                    ['key' => 'category', 'label' => 'Category', 'type' => 'select', 'default' => 'residential', 'rules' => 'required|in:residential,commercial', 'options' => ['residential' => 'Residential', 'commercial' => 'Commercial']],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
                    ['key' => 'before_image', 'label' => 'Before photo', 'type' => 'image', 'folder' => 'gallery', 'rules' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072'],
                    ['key' => 'after_image', 'label' => 'After photo', 'type' => 'image', 'folder' => 'gallery', 'rules' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072'],
                    ['key' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0, 'rules' => 'nullable|integer|min:0'],
                    $yesNo('is_active', 'Active'),
                ],
            ],

            'payments' => [
                'title' => 'Payments', 'singular' => 'payment', 'model' => Payment::class, 'roles' => self::ALL, 'with' => ['booking'],
                'search' => ['transaction_reference'], 'order' => ['id', 'desc'], 'readonly' => true,
                'columns' => [
                    ['key' => 'booking.reference', 'label' => 'Booking'], ['key' => 'method', 'label' => 'Method'], ['key' => 'amount', 'label' => 'Amount', 'type' => 'money'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge'], ['key' => 'transaction_reference', 'label' => 'Reference'],
                    ['key' => 'refund_amount', 'label' => 'Refunded', 'type' => 'money'], ['key' => 'created_at', 'label' => 'Created', 'type' => 'date'],
                ],
                'fields' => [],
            ],

            'invoices' => [
                'title' => 'Invoices', 'singular' => 'invoice', 'model' => Invoice::class, 'roles' => self::ALL, 'with' => ['booking'],
                'search' => ['number'], 'order' => ['id', 'desc'], 'readonly' => true,
                'columns' => [
                    ['key' => 'number', 'label' => 'Invoice'], ['key' => 'booking.reference', 'label' => 'Booking'], ['key' => 'issued_on', 'label' => 'Issued', 'type' => 'date'],
                    ['key' => 'total', 'label' => 'Total', 'type' => 'money'], ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ],
                'fields' => [],
            ],

            'users' => [
                'title' => 'Users & roles', 'singular' => 'user', 'model' => User::class, 'roles' => ['super_admin'],
                'search' => ['name', 'email'], 'order' => ['id', 'desc'], 'soft' => true, 'special' => 'users',
                'columns' => [['key' => 'name', 'label' => 'Name'], ['key' => 'email', 'label' => 'Email'], ['key' => 'role', 'label' => 'Role', 'type' => 'badge'], ['key' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:120'],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'rules' => 'required|email:rfc|max:190|unique:users,email,{id}'],
                    ['key' => 'phone', 'label' => 'Phone', 'type' => 'text', 'rules' => 'nullable|string|max:30'],
                    ['key' => 'role', 'label' => 'Role', 'type' => 'select', 'default' => 'staff', 'rules' => 'required|in:super_admin,admin,manager,staff,customer', 'options' => ['super_admin' => 'Super Admin', 'admin' => 'Administrator', 'manager' => 'Manager', 'staff' => 'Cleaning Staff', 'customer' => 'Customer']],
                    ['key' => 'password', 'label' => 'Password (leave blank to keep)', 'type' => 'password', 'rules' => 'nullable|string|min:10|max:100'],
                    $yesNo('is_active', 'Active (can sign in)'),
                ],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        $all = static::all();

        return isset($all[$key]) ? ['key' => $key] + $all[$key] : null;
    }

    /** Sidebar groups for the admin layout (filtered by role in the controller). */
    public static function navigation(): array
    {
        $all = static::all();

        return collect($all)->map(fn ($d, $k) => ['key' => $k, 'title' => $d['title'], 'roles' => $d['roles']])->values()->all();
    }
}
