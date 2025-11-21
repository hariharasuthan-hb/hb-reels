<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the single page website.
     * This is the main frontend page with all sections.
     */
    public function index(): View
    {
        // Fetch landing page content
        $landingPage = \App\Models\LandingPageContent::getActive();
        
        // Fetch CMS content by type
        $cmsContentRepository = app(\App\Repositories\Interfaces\CmsContentRepositoryInterface::class);
        
        $cmsHero = $cmsContentRepository->findByKey('hero-banner') ?? $cmsContentRepository->findByType('hero')->first();
        $cmsAbout = $cmsContentRepository->findByKey('about-section') ?? $cmsContentRepository->findByType('about')->first();
        $cmsServicesSection = $cmsContentRepository->findByKey('services-section') ?? $cmsContentRepository->findByType('services')->first();
        $allServices = $cmsContentRepository->getFrontendContent('services');
        $cmsServices = $allServices->filter(function($item) use ($cmsServicesSection) {
            // Exclude the section header from the services list
            if ($cmsServicesSection) {
                return $item->id !== $cmsServicesSection->id && $item->key !== 'services-section';
            }
            return $item->key !== 'services-section';
        });
        $cmsFeatures = $cmsContentRepository->getFrontendContent('features');
        $cmsTestimonialsSection = $cmsContentRepository->findByKey('testimonials-section');
        $allTestimonials = $cmsContentRepository->getFrontendContent('testimonials');
        $cmsTestimonials = $allTestimonials->filter(function($item) {
            return $item->key !== 'testimonials-section';
        });
        
        return view('frontend.home.index', compact(
            'landingPage',
            'cmsHero',
            'cmsAbout',
            'cmsServicesSection',
            'cmsServices',
            'cmsFeatures',
            'cmsTestimonialsSection',
            'cmsTestimonials'
        ));
    }
}

