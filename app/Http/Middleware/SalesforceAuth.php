<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SalesforceAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => config('services.salesforce.url') .'/services/oauth2/token?grant_type=password&client_id='.config('services.salesforce.client_id').'&client_secret='.config('services.salesforce.client_secret').'&username='.config('services.salesforce.username').'&password='.config('services.salesforce.password').'',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $salesforceResponse = json_decode($response);

        $request->attributes->add(['access_token' => $salesforceResponse->access_token]);

        return $next($request);
    }
}
