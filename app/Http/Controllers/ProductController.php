<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use GuzzleHttp\{Client, RequestOptions};
use Illuminate\Support\Carbon;

class ProductController extends Controller
{
    public $client;

    public function __construct(){
        $this->client = new Client(['base_uri' => config('services.salesforce.url')]);
    }

    public function fetchProducts()
    {
        // $request->region
        // ex: vismin => 01s2v00000LYBvGAAX luzon => 01s2v00000LYBvBAAX
        try {
            $query = 'SELECT id, Name, Pricebook2Id, Product2Id, ProductCode, UnitPrice FROM PricebookEntry WHERE IsActive=true AND UnitPrice!=0';

            $response = $this->client->get('/services/data/v55.0/query/', [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . \Request::get('access_token'),
                ],
                RequestOptions::QUERY => [
                    'q' => $query
                ],
            ]);

            $responseData = json_decode($response->getBody(), true);
            $recordsArray = $responseData['records'];

            $result = collect($recordsArray)->filter(function ($value, $key) {
                $temp_array[] = $value['ProductCode'];

                foreach ($temp_array as $array_item) {
                    $a[] = $array_item;
                }

                $b = ['PHAASC', 'PHAVF', 'LPG11GC', 'LPG2.7GC', 'PHAPK', 'PHAPKS', 'TEST', 'CYL11', 'CYL2.7', 'CYL22'];

                if (!array_intersect($a, $b)) {
                    return $value;
                }

            })->values()->all();

            return $result;

        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function insertProductsFromSalesforceToDatabase() {
        $products = $this->fetchProducts();

        $container = [];
        foreach ($products as $p) {
            $container[] = [
                'name' => $p['Name'],
                'pricebook_id' => $p['Pricebook2Id'],
                'product_id' => $p['Product2Id'],
                'product_code' => $p['ProductCode'],
                'unit_price' => $p['UnitPrice'],
                'status' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        Product::insert($container);
        return 'Products inserted successfully';
    }

    public function fetchProductsFromDatabase() {
        return Product::all();
    }
}
