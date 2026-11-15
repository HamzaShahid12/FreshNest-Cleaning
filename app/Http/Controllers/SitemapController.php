<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Service;
use App\Models\ServiceArea;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect(['/', '/about', '/services', '/pricing', '/gallery', '/reviews', '/areas', '/contact', '/faq', '/blog', '/book',
            '/legal/privacy', '/legal/terms', '/legal/cancellation'])
            ->map(fn ($p) => ['loc' => url($p), 'lastmod' => null]);

        $urls = $urls
            ->concat(Service::active()->get()->map(fn ($s) => ['loc' => url('/services/'.$s->slug), 'lastmod' => $s->updated_at]))
            ->concat(ServiceArea::where('is_active', true)->get()->map(fn ($a) => ['loc' => url('/areas/'.$a->slug), 'lastmod' => $a->updated_at]))
            ->concat(BlogPost::published()->get()->map(fn ($p) => ['loc' => url('/blog/'.$p->slug), 'lastmod' => $p->updated_at]));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.e($u['loc']).'</loc>'
                .($u['lastmod'] ? '<lastmod>'.$u['lastmod']->toAtomString().'</lastmod>' : '')
                ."</url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
