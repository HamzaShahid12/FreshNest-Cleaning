<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\GalleryItem;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Booking', 'How do I book a cleaning?', 'Use the Book a Cleaning button, choose your service, tell us about your home, pick a time and confirm. You will see the full price before you submit.'],
            ['Booking', 'Can I reschedule or cancel?', 'Yes. You can reschedule or cancel free of charge from your account up to the cancellation window shown in our Cancellation Policy.'],
            ['Booking', 'How far ahead should I book?', 'We often have same-week availability, but booking a few days ahead gives you the widest choice of times.'],
            ['Pricing', 'How is the price calculated?', 'Price depends on the service, number of bedrooms and bathrooms, home size, add-ons and how often you want us to visit. Recurring plans receive a discount.'],
            ['Pricing', 'Are there any hidden fees?', 'No. The total shown at checkout is the total you pay, including any applicable tax.'],
            ['Pricing', 'Do you offer discounts?', 'Yes, weekly, biweekly and monthly plans are discounted, and you can apply promo codes at checkout.'],
            ['Services', 'Do I need to supply cleaning products?', 'No. Our team brings professional equipment and products. Let us know if you prefer specific eco-friendly options.'],
            ['Services', 'Are your cleaners vetted?', 'All team members are background-checked and trained to our cleaning standards.'],
            ['Services', 'Do you clean offices?', 'Yes. Office cleaning is quoted individually. Send us the details and we will tailor a plan.'],
            ['Payments', 'How can I pay?', 'You can pay online by card when enabled, pay in cash after the service, or choose pay later and we will invoice you. We never store card numbers.'],
            ['Payments', 'When am I charged?', 'Card payments are taken at booking. Cash and pay-later bookings are settled after your cleaning.'],
        ] as $i => [$cat, $q, $a]) {
            Faq::updateOrCreate(['question' => $q], ['category' => $cat, 'answer' => $a, 'sort_order' => $i + 1, 'is_active' => true]);
        }

        foreach ([
            ['Kitchen reset', 'residential', 'Greasy stovetop and cabinets returned to a like-new shine.'],
            ['Bathroom refresh', 'residential', 'Hard-water stains and grout restored.'],
            ['Move-out clean', 'residential', 'Empty apartment ready for the next tenant.'],
            ['Open-plan office', 'commercial', 'Daily office cleaning for a busy team.'],
            ['Living room deep clean', 'residential', 'Carpets, sofa area and baseboards refreshed.'],
            ['Reception area', 'commercial', 'Glass, floors and high-touch surfaces cleaned.'],
        ] as $i => [$title, $cat, $desc]) {
            GalleryItem::updateOrCreate(['title' => $title], ['category' => $cat, 'description' => $desc, 'sort_order' => $i + 1, 'is_active' => true]);
        }

        $tips = BlogCategory::updateOrCreate(['slug' => 'cleaning-tips'], ['name' => 'Cleaning tips']);
        $news = BlogCategory::updateOrCreate(['slug' => 'company-news'], ['name' => 'Company news']);

        foreach ([
            ['How often should you deep clean your home?', 'how-often-deep-clean-home', $tips, 'A practical schedule for deep cleaning each room, plus signs it is time to book.',
                "Most homes benefit from a deep clean two to four times a year.\n\nKitchens and bathrooms collect grease and mineral deposits fastest, so they often need extra attention between seasonal deep cleans.\n\nIf you notice dust building on baseboards, cloudy shower glass or sticky cabinet fronts, it is probably time for a deep clean."],
            ['A simple weekly cleaning checklist', 'weekly-cleaning-checklist', $tips, 'Keep your home fresh with this short checklist you can finish in under two hours.',
                "Start at the top of each room and work down: dust surfaces, then vacuum, then mop.\n\nWipe kitchen counters daily and sanitise high-touch surfaces like handles and switches weekly.\n\nIf the list feels like too much, a recurring cleaning plan can take it off your plate."],
            ['Why we use trained, vetted cleaners', 'trained-vetted-cleaners', $news, 'Our approach to hiring, training and supporting the FreshNest team.',
                "Every cleaner completes a background check and hands-on training before visiting a customer home.\n\nWe follow a room-by-room checklist so quality stays consistent, whoever visits.\n\nIf anything is not right, tell us within 24 hours and we will fix it."],
        ] as [$title, $slug, $cat, $excerpt, $body]) {
            BlogPost::updateOrCreate(['slug' => $slug], [
                'title' => $title, 'blog_category_id' => $cat->id, 'excerpt' => $excerpt, 'body' => $body,
                'is_published' => true, 'published_at' => now()->subDays(rand(3, 40)),
                'seo_title' => $title.' | FreshNest', 'seo_description' => $excerpt,
            ]);
        }
    }
}
