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

    public function fetchProducts(Request $request)
    {
        // ex: vismin => 01s2v00000LYBvGAAX luzon => 01s2v00000LYBvBAAX
        $areaCode = ($request->area === 'luzon') ? 'BAAX' : (($request->area === 'vismin') ? 'GAAX' : 'default');
        $productId = $request->productId;

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

            $collection = collect($recordsArray);

            $productCodeFilter = ['PHAASC', 'PHAVF', 'LPG11GC', 'LPG2.7GC', 'PHAPK', 'PHAPKS', 'TEST', 'CYL11', 'CYL2.7', 'CYL22'];
            $pricebookIdFilter = ['01s2v00000My81DAAR', '01s2v00000My81IAAR', '01s2v00000I1HEkAAN'];

            $valuesToFilter = array_merge($productCodeFilter, $pricebookIdFilter);

            $filtered = $collection->filter(function ($values) use ($valuesToFilter, $areaCode, $productId) {
                if (isset($productId)) {
                    return !in_array($values['ProductCode'], $valuesToFilter) && !in_array($values['Pricebook2Id'], $valuesToFilter) && substr($values['Pricebook2Id'], -4) === $areaCode && $values['Product2Id'] === $productId;
                }
                if (!in_array($values['ProductCode'], $valuesToFilter) && !in_array($values['Pricebook2Id'], $valuesToFilter) && substr($values['Pricebook2Id'], -4) === $areaCode) {
                    return $values;
                }

            })->values()->all();

            return $filtered;

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
