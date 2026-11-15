<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\ServiceCategory;
use App\Models\ServiceExtra;
use Illuminate\Database\Seeder;

/** Services, categories, extras, areas and coupons. All prices are demonstration values editable in the admin panel. */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [];
        foreach ([['Home cleaning', 'home-cleaning', 1], ['Moving', 'moving', 2], ['Commercial', 'commercial', 3], ['Specialty', 'specialty', 4]] as [$name, $slug, $order]) {
            $cats[$slug] = ServiceCategory::updateOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => $order, 'is_active' => true]);
        }

        $services = [
            ['Standard House Cleaning', 'standard-house-cleaning', 'home-cleaning', 80, 'size_based', 120, true,
                'A thorough top-to-bottom clean to keep your home fresh week after week.',
                'Our standard clean covers every room in your home: surfaces dusted, floors vacuumed and mopped, kitchen and bathrooms sanitised. Ideal as a regular visit to keep your home consistently fresh.',
                ['Dust all reachable surfaces', 'Vacuum carpets and rugs', 'Mop hard floors', 'Clean and sanitise bathrooms', 'Wipe kitchen counters and appliance exteriors', 'Empty trash and replace liners', 'Make beds on request'],
                ['A consistently fresh home', 'Background-checked, trained cleaners', 'Eco-friendly products on request', 'Flexible weekly, biweekly or monthly plans']],
            ['Deep House Cleaning', 'deep-house-cleaning', 'home-cleaning', 150, 'size_based', 240, true,
                'A detailed, top-to-bottom reset that reaches the places routine cleaning misses.',
                'Perfect for a first visit, a seasonal refresh or before hosting guests. We go beyond the surface with baseboards, door frames, vents, cabinet fronts and detailed scrubbing.',
                ['Everything in Standard cleaning', 'Baseboards and door frames', 'Inside microwave', 'Cabinet fronts and handles', 'Light fixtures and ceiling fans', 'Detailed bathroom descaling', 'Window sills and tracks'],
                ['Reaches built-up grime', 'Great as a first visit', 'Leaves the home ready for regular upkeep']],
            ['Move-In Cleaning', 'move-in-cleaning', 'moving', 180, 'size_based', 240, true,
                'Start fresh in your new place with every surface cleaned before the boxes arrive.',
                'We clean an empty home so you can unpack into a space that feels truly yours: inside cabinets, closets, appliances and every surface.',
                ['Inside all cabinets and drawers', 'Inside closets', 'Appliances cleaned inside and out', 'Bathrooms scrubbed and disinfected', 'Floors vacuumed and mopped', 'Windows and sills wiped'],
                ['Move into a spotless home', 'Done before your belongings arrive', 'Perfect for new owners and tenants']],
            ['Move-Out Cleaning', 'move-out-cleaning', 'moving', 200, 'size_based', 270, false,
                'Leave the property spotless and help secure your deposit.',
                'A full empty-property clean designed to meet landlord and buyer expectations, including appliances, cabinets and floors.',
                ['Inside all cabinets and drawers', 'Inside oven and refrigerator', 'Wall spot cleaning', 'Bathrooms scrubbed and disinfected', 'Floors vacuumed and mopped', 'Window sills and blinds dusted'],
                ['Helps recover your deposit', 'Ready for the next occupant', 'Checklist based, nothing missed']],
            ['Apartment Cleaning', 'apartment-cleaning', 'home-cleaning', 70, 'size_based', 90, false,
                'Efficient, affordable cleaning designed for apartments and condos.',
                'Compact homes deserve a careful clean too. Our apartment service is sized and priced for smaller spaces.',
                ['Dust and wipe surfaces', 'Vacuum and mop floors', 'Kitchen and bathroom clean', 'Trash removal'],
                ['Priced for smaller spaces', 'Fast and flexible scheduling', 'Great for busy professionals']],
            ['Office Cleaning', 'office-cleaning', 'commercial', 0, 'quote', 180, false,
                'Reliable commercial cleaning tailored to your workspace. Custom quotation.',
                'A clean workplace supports health and productivity. Tell us about your space and we will provide a custom quote and cleaning schedule.',
                ['Desks and common areas', 'Restrooms and kitchenettes', 'Floors vacuumed and mopped', 'Trash and recycling', 'High-touch surface disinfection'],
                ['Tailored to your schedule', 'After-hours cleaning available', 'Consistent, accountable service']],
            ['Kitchen Deep Cleaning', 'kitchen-deep-cleaning', 'specialty', 90, 'flat', 120, false,
                'Degrease, descale and sanitise the heart of your home.',
                'Targeted deep clean of the kitchen including appliances, cabinets and hard-to-reach areas.',
                ['Stovetop and range hood degreased', 'Inside oven and microwave', 'Cabinet fronts and handles', 'Sink and faucet descaled', 'Backsplash scrubbed', 'Floors mopped'],
                ['Removes stubborn grease', 'Sanitised food-prep areas', 'Ideal before holidays']],
            ['Bathroom Cleaning', 'bathroom-cleaning', 'specialty', 60, 'flat', 75, false,
                'Sparkling, sanitised bathrooms with descaling and grout attention.',
                'A focused clean for bathrooms that need extra attention, perfect between regular visits.',
                ['Toilet, sink and tub scrubbed', 'Shower glass descaled', 'Grout spot treatment', 'Mirrors polished', 'Floors mopped'],
                ['Hygienic and fresh', 'Hard-water stain removal', 'Quick turnaround']],
            ['Carpet Cleaning', 'carpet-cleaning', 'specialty', 120, 'flat', 150, false,
                'Deep extraction cleaning to refresh carpets and remove allergens.',
                'Hot water extraction lifts dirt, odours and allergens from carpet fibres, leaving them fresh and soft.',
                ['Pre-vacuum', 'Spot treatment', 'Hot water extraction', 'Deodorising', 'Fast-dry finish'],
                ['Removes embedded dirt', 'Reduces allergens', 'Extends carpet life']],
            ['Window Cleaning', 'window-cleaning', 'specialty', 110, 'flat', 120, false,
                'Streak-free glass inside and out for brighter rooms.',
                'Professional window cleaning including frames and sills for crystal-clear views.',
                ['Interior and exterior glass', 'Frames and sills wiped', 'Screens brushed', 'Streak-free finish'],
                ['More natural light', 'Streak-free results', 'Safe, professional equipment']],
        ];

        $faqsFor = fn (string $n) => [
            "How long does {$n} take?|Most visits take the estimated duration shown, depending on home size and condition. You will see an exact estimate while booking.",
            'Do I need to be home?|No. Many customers provide access instructions. Just let us know how to get in when you book.',
            'What if I am not happy?|Contact us within 24 hours and we will return to re-clean the area at no charge.',
        ];

        foreach ($services as $i => [$name, $slug, $cat, $price, $method, $mins, $featured, $short, $desc, $included, $benefits]) {
            Service::updateOrCreate(['slug' => $slug], [
                'service_category_id' => $cats[$cat]->id, 'name' => $name, 'short_description' => $short, 'description' => $desc,
                'base_price' => $price, 'pricing_method' => $method, 'duration_minutes' => $mins, 'is_active' => true,
                'is_featured' => $featured, 'sort_order' => $i + 1, 'included' => $included, 'benefits' => $benefits,
                'faqs' => $faqsFor(strtolower($name)),
                'seo_title' => $name.' | FreshNest Cleaning Services',
                'seo_description' => $short,
            ]);
        }

        foreach ([
            ['Refrigerator cleaning', 'Inside shelves, drawers and seals', 20, 30],
            ['Oven cleaning', 'Degrease racks and interior', 25, 30],
            ['Interior window cleaning', 'Glass, frames and sills', 30, 40],
            ['Carpet cleaning', 'Hot water extraction (per visit)', 40, 45],
            ['Laundry (one load)', 'Wash, dry and fold', 20, 30],
            ['Extra bathroom cleaning', 'One additional bathroom', 25, 25],
        ] as $i => [$name, $desc, $price, $mins]) {
            ServiceExtra::updateOrCreate(['name' => $name], ['description' => $desc, 'price' => $price, 'duration_minutes' => $mins, 'is_active' => true, 'sort_order' => $i + 1]);
        }

        foreach ([
            ['Austin', 'austin', 'TX', '78701,78702,78703,78704,78705,78721,78722,78723,78731,78741,78745,78746,78751,78756,78757,78758'],
            ['Round Rock', 'round-rock', 'TX', '78664,78665,78681'],
            ['Cedar Park', 'cedar-park', 'TX', '78613'],
            ['Pflugerville', 'pflugerville', 'TX', '78660'],
            ['Georgetown', 'georgetown', 'TX', '78626,78628,78633'],
            ['Lakeway', 'lakeway', 'TX', '78734,78738'],
        ] as $i => [$name, $slug, $state, $zips]) {
            ServiceArea::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'state' => $state, 'zip_codes' => $zips, 'is_active' => true, 'sort_order' => $i + 1,
                'description' => "FreshNest provides residential and commercial cleaning across {$name}, {$state}. Book online and we will confirm your appointment.",
            ]);
        }

        Coupon::updateOrCreate(['code' => 'WELCOME10'], ['description' => '10% off your first cleaning', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 0, 'is_active' => true]);
        Coupon::updateOrCreate(['code' => 'SAVE20'], ['description' => '$20 off orders over $150', 'type' => 'fixed', 'value' => 20, 'min_subtotal' => 150, 'is_active' => true]);
    }
}
