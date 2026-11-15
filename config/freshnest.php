<?php

/*
|--------------------------------------------------------------------------
| FreshNest business configuration
|--------------------------------------------------------------------------
| Every value under "settings" is a DEFAULT. Admins override them from
| Admin > Business Settings (stored in the business_settings table).
*/

$emails = [
    'welcome' => ['label' => 'Welcome / registration', 'subject' => 'Welcome to {business}', 'body' => "Thanks for creating an account with {business}.\nYou can now book and manage your cleanings online."],
    'booking_received' => ['label' => 'Booking request received', 'subject' => 'Booking request received - {reference}', 'body' => "We received your booking request for {service} on {date} at {time}.\nEstimated total: {total}.\nWe will confirm it shortly."],
    'booking_confirmed' => ['label' => 'Booking confirmed', 'subject' => 'Your booking is confirmed - {reference}', 'body' => "Good news! Your {service} on {date} at {time} is confirmed.\nTotal: {total}."],
    'employee_assigned' => ['label' => 'Employee assigned', 'subject' => 'Your cleaner has been assigned - {reference}', 'body' => "{employee} will take care of your {service} on {date} at {time}."],
    'booking_rescheduled' => ['label' => 'Booking rescheduled', 'subject' => 'Your booking was rescheduled - {reference}', 'body' => "Your {service} has been moved to {date} at {time}."],
    'booking_cancelled' => ['label' => 'Booking cancelled', 'subject' => 'Your booking was cancelled - {reference}', 'body' => "Your {service} booking on {date} at {time} has been cancelled.\nIf this was a mistake, please contact us."],
    'booking_rejected' => ['label' => 'Booking rejected', 'subject' => 'We could not accept your booking - {reference}', 'body' => "Unfortunately we are unable to accept your booking for {date} at {time}.\nPlease choose another time or contact us."],
    'booking_completed' => ['label' => 'Booking completed', 'subject' => 'Your cleaning is complete - {reference}', 'body' => "Your {service} on {date} is complete. Thank you for choosing {business}!"],
    'payment_confirmed' => ['label' => 'Payment confirmation', 'subject' => 'Payment received - {reference}', 'body' => "We received your payment of {total} for booking {reference}. Thank you!"],
    'review_request' => ['label' => 'Review request', 'subject' => 'How did we do? - {reference}', 'body' => "We would love your feedback on your recent {service}.\nLeaving a review takes less than a minute."],
    'admin_new_booking' => ['label' => 'Admin: new booking', 'subject' => 'New booking {reference}', 'body' => "{name} booked {service} for {date} at {time}. Total: {total}."],
    'admin_contact_received' => ['label' => 'Admin: new contact enquiry', 'subject' => 'New contact enquiry from {name}', 'body' => "{name} wrote:\n{message}"],
    'job_assigned' => ['label' => 'Staff: job assigned', 'subject' => 'New job assigned - {reference}', 'body' => "You have been assigned {service} on {date} at {time}."],
];

$settings = [
    'business' => [
        'label' => 'Business details',
        'fields' => [
            'business_name' => ['label' => 'Business name', 'default' => 'FreshNest Cleaning Services', 'type' => 'text'],
            'tagline' => ['label' => 'Tagline', 'default' => 'A Cleaner Home. A Happier Life.', 'type' => 'text'],
            'business_email' => ['label' => 'Business email', 'default' => 'hello@freshnest.test', 'type' => 'text'],
            'business_phone' => ['label' => 'Business phone', 'default' => '(555) 010-2030', 'type' => 'text'],
            'business_address' => ['label' => 'Business address', 'default' => '123 Maple Street, Austin, TX 78701', 'type' => 'text'],
            'business_lat' => ['label' => 'Business latitude (set on the map)', 'default' => '', 'type' => 'text'],
            'business_lng' => ['label' => 'Business longitude (set on the map)', 'default' => '', 'type' => 'text'],
            'business_hours' => ['label' => 'Opening hours (text)', 'default' => 'Mon-Sat 8:00 AM - 6:00 PM', 'type' => 'text'],
            'maps_embed_url' => ['label' => 'Google Maps embed URL (optional)', 'default' => '', 'type' => 'text'],
            'announcement' => ['label' => 'Top announcement bar (blank to hide)', 'default' => 'Book online today and get 10% off your first cleaning with code WELCOME10', 'type' => 'text'],
            'social_facebook' => ['label' => 'Facebook URL', 'default' => '', 'type' => 'text'],
            'social_instagram' => ['label' => 'Instagram URL', 'default' => '', 'type' => 'text'],
            'social_x' => ['label' => 'X / Twitter URL', 'default' => '', 'type' => 'text'],
            'social_youtube' => ['label' => 'YouTube URL', 'default' => '', 'type' => 'text'],
        ],
    ],
    'booking' => [
        'label' => 'Booking rules',
        'fields' => [
            'slot_start' => ['label' => 'First appointment start (HH:MM)', 'default' => '08:00', 'type' => 'text'],
            'slot_end' => ['label' => 'Last appointment start (HH:MM)', 'default' => '16:00', 'type' => 'text'],
            'slot_interval_minutes' => ['label' => 'Time between slot start times (minutes)', 'default' => '60', 'type' => 'number'],
            'min_notice_hours' => ['label' => 'Minimum notice for new bookings (hours)', 'default' => '12', 'type' => 'number'],
            'max_advance_days' => ['label' => 'How far ahead customers can book (days)', 'default' => '60', 'type' => 'number'],
            'cancellation_hours' => ['label' => 'Free cancel / reschedule until (hours before)', 'default' => '24', 'type' => 'number'],
            'restrict_service_area' => ['label' => 'Only accept bookings inside the listed Service areas (1 = yes, 0 = accept bookings from anywhere)', 'default' => '1', 'type' => 'number'],
            'allow_pay_later' => ['label' => 'Allow "pay later" (1 = yes, 0 = no)', 'default' => '1', 'type' => 'number'],
            'allow_cash' => ['label' => 'Allow "cash on completion" (1 = yes, 0 = no)', 'default' => '1', 'type' => 'number'],
        ],
    ],
    'pricing' => [
        'label' => 'Pricing rules',
        'fields' => [
            'bedroom_rate' => ['label' => 'Price per additional bedroom ($)', 'default' => '15', 'type' => 'number'],
            'bathroom_rate' => ['label' => 'Price per additional bathroom ($)', 'default' => '20', 'type' => 'number'],
            'sqft_included' => ['label' => 'Square feet included in base price', 'default' => '1000', 'type' => 'number'],
            'sqft_block_size' => ['label' => 'Extra size block (sq ft)', 'default' => '500', 'type' => 'number'],
            'sqft_block_rate' => ['label' => 'Price per extra size block ($)', 'default' => '12', 'type' => 'number'],
            'apartment_multiplier' => ['label' => 'Apartment multiplier', 'default' => '1', 'type' => 'number'],
            'house_multiplier' => ['label' => 'House multiplier', 'default' => '1.1', 'type' => 'number'],
            'office_multiplier' => ['label' => 'Office multiplier', 'default' => '1.2', 'type' => 'number'],
            'discount_weekly' => ['label' => 'Weekly discount (%)', 'default' => '15', 'type' => 'number'],
            'discount_biweekly' => ['label' => 'Every-2-weeks discount (%)', 'default' => '10', 'type' => 'number'],
            'discount_monthly' => ['label' => 'Monthly discount (%)', 'default' => '5', 'type' => 'number'],
            'tax_rate' => ['label' => 'Sales tax rate (%) - 0 if not applicable', 'default' => '0', 'type' => 'number'],
            'bedroom_minutes' => ['label' => 'Extra minutes per additional bedroom', 'default' => '30', 'type' => 'number'],
            'bathroom_minutes' => ['label' => 'Extra minutes per additional bathroom', 'default' => '20', 'type' => 'number'],
            'sqft_block_minutes' => ['label' => 'Extra minutes per extra size block', 'default' => '15', 'type' => 'number'],
        ],
    ],
];

$emailFields = [];
foreach ($emails as $key => $tpl) {
    $emailFields["email_{$key}_subject"] = ['label' => $tpl['label'].' - subject', 'default' => $tpl['subject'], 'type' => 'text'];
    $emailFields["email_{$key}_body"] = ['label' => $tpl['label'].' - body', 'default' => $tpl['body'], 'type' => 'textarea'];
}
$settings['email'] = [
    'label' => 'Email templates (placeholders: {name} {reference} {service} {date} {time} {total} {employee} {business} {message})',
    'fields' => $emailFields,
];

return [
    'timezone' => env('FRESHNEST_TIMEZONE', 'America/Chicago'),

    'roles' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'staff' => 'Cleaning Staff',
        'customer' => 'Customer',
    ],
    'admin_roles' => ['super_admin', 'admin', 'manager'],

    'statuses' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
    ],
    // Statuses that occupy a time slot.
    'active_statuses' => ['pending', 'confirmed', 'assigned', 'in_progress'],

    'transitions' => [
        'pending' => ['confirmed', 'rejected', 'cancelled'],
        'confirmed' => ['assigned', 'in_progress', 'cancelled'],
        'assigned' => ['confirmed', 'in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
        'rejected' => [],
    ],

    'property_types' => ['apartment' => 'Apartment', 'house' => 'House', 'office' => 'Office'],
    'frequencies' => [
        'one_time' => 'One-time',
        'weekly' => 'Weekly',
        'biweekly' => 'Every 2 weeks',
        'monthly' => 'Monthly',
    ],
    'payment_methods' => ['stripe' => 'Pay online (card)', 'cash' => 'Cash on completion', 'pay_later' => 'Pay later / invoice'],

    'settings' => $settings,
    'emails' => $emails,

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
