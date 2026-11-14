<?php

namespace App\Http\Controllers;

use App\Http\Requests\Public\SubmitLandingLeadRequest;
use App\Services\Leads\ConsultLeadService;
use App\Services\Settings\SiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class LandingController extends Controller
{
    public function __construct(
        private readonly ConsultLeadService $leads,
        private readonly SiteSettingsService $settings,
    ) {
    }

    public function duHocHanQuoc2026(): Response
    {
        $html = file_get_contents(resource_path('landings/du-hoc-han-quoc-2026.html'));
        if ($html === false) {
            abort(500, 'Landing HTML missing.');
        }

        $home = rtrim(url('/'), '/');
        $canonical = $home.'/du-hoc-han-quoc-2026';
        $assets = asset('landing/du-hoc-han-quoc-2026');
        $logo = asset('assets/img/logo.png');
        $formImg = asset('assets/img/form.jpg');
        $leadUrl = route('landing.duhoc2026.lead');
        $gaId = trim($this->settings->get('google_analytics_id'));
        $gtmId = trim($this->settings->get('google_tag_manager_id'));

        $gtmHead = '';
        $gtmBody = '';
        if ($gtmId !== '') {
            $gtmHead = "\n<!-- Google Tag Manager -->\n"
                ."<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\n"
                ."new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n"
                ."j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n"
                ."'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n"
                ."})(window,document,'script','dataLayer','".e($gtmId)."');</script>\n"
                ."<!-- End Google Tag Manager -->\n";
            $gtmBody = "\n<!-- Google Tag Manager (noscript) -->\n"
                .'<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.e($gtmId).'"'
                ."\nheight=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n"
                ."<!-- End Google Tag Manager (noscript) -->\n";
        }

        $ga = '';
        if ($gaId !== '') {
            $ga = "\n    <!-- Google tag (gtag.js) -->\n"
                .'<script async src="https://www.googletagmanager.com/gtag/js?id='.e($gaId).'"></script>'."\n"
                ."<script>\n"
                ."  window.dataLayer = window.dataLayer || [];\n"
                ."  function gtag(){dataLayer.push(arguments);}\n"
                ."  gtag('js', new Date());\n"
                ."  gtag('config', '".e($gaId)."');\n"
                ."</script>\n";
        }

        $headInject = $gtmHead.'  <meta name="csrf-token" content="'.e(csrf_token()).'">'.$ga;

        $html = str_replace('<head>', '<head>'.$headInject, $html);
        $html = str_replace(
            '<link rel="canonical" href="https://duhocsunny.com/" />',
            '<link rel="canonical" href="'.e($canonical).'" />',
            $html
        );
        $html = str_replace(
            '<meta property="og:image" content="https://duhocsunny.com/assets/img/logo.png" />',
            '<meta property="og:url" content="'.e($canonical).'" />'."\n  "
            .'<meta property="og:image" content="'.e($logo).'" />',
            $html
        );
        $html = str_replace('https://duhocsunny.com/assets/img/logo.png', $logo, $html);
        $html = str_replace('https://duhocsunny.com/assets/img/form.jpg', $formImg, $html);
        $html = str_replace('https://duhocsunny.com/', $home.'/', $html);
        $html = str_replace('assets/licenses/', $assets.'/licenses/', $html);
        $html = str_replace('assets/certificates/', $assets.'/certificates/', $html);
        $html = str_replace('assets/pricing/', $assets.'/pricing/', $html);
        $html = str_replace("API_URL: ''", "API_URL: '".e($leadUrl)."'", $html);
        $html = str_replace(
            "headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }",
            "headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'), 'X-Requested-With': 'XMLHttpRequest' }",
            $html
        );
        $html = preg_replace('/<body(\s[^>]*)?>/', '<body$1>'.$gtmBody, $html, 1);
        $html = str_replace('<body class="', '<body id="top" class="', $html);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function duHocHanQuoc2026Lead(SubmitLandingLeadRequest $request): JsonResponse
    {
        if (trim((string) $request->input('website')) !== '') {
            return response()->json(['ok' => true]);
        }

        $this->leads->createFromLanding($request);

        return response()->json(['ok' => true]);
    }
}
