<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        
        // Content Security Policy - más permisivo en desarrollo
        if (app()->environment('local')) {
            $csp = "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';";
        } else {
            // En producción, CSP más restrictivo para PayPal y servicios
            $csp = "
                default-src 'self';
                script-src 'self' 'unsafe-inline' 'unsafe-eval' 
                    https://www.paypal.com 
                    https://checkout.paypal.com 
                    https://www.google.com;
                img-src 'self' data: 
                    https://*.paypal.com 
                    https://*.paypalobjects.com 
                    https://objects.paypal.cn
                    https://www.google.com
                    https://*.googleusercontent.com;
                connect-src 'self' 
                    https://*.paypal.com 
                    https://*.paypalobjects.com 
                    https://objects.paypal.cn 
                    https://www.google.com 
                    https://browser-intake-us5-datadoghq.com;
                frame-src 'self' 
                    https://*.paypal.com 
                    https://checkout.paypal.com;
                style-src 'self' 'unsafe-inline' https://*.paypal.com;
                font-src 'self' data:;
            ";
        }
        $response->headers->set('Content-Security-Policy', str_replace(["\r", "\n"], " ", $csp));
        return $response;
    }
}
