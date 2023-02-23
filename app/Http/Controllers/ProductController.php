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

    public function fetchProductsFromSalesforce() {
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

            return $recordsArray;

        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function fetchProductsFromDatabase(Request $request) {
        $areaCode = ($request->area === 'luzon') ? 'BAAX' : (($request->area === 'vismin') ? 'GAAX' : 'default');
        $productId = $request->productId;

        try {
            $result = Product::with('product_images')->get();
            $products = collect($result)
                ->map(function ($product) {
                  $conversions = [
                    'PGCM' => 'PRYCEGAS Club Membership',
                    'LPG50CC' => '50kg LPG Cylinder with Content',
                    'LPG11CC' => '11kg LPG Cylinder with Content',
                    'LPG11CCAB' => '11kg LPG Bundle',
                    'LPG2.7CCPK' => '2.7kg LPG Cylinder with Power Burner',
                    'LPG2.7CC' => '2.7kg LPG Cylinder with Content',
                    'LPG50C' => '50kg LPG Refill',
                    'LPG22CC' => '22kg LPG Cylinder with Content',
                    'ACCHRCB' => 'Hose, Regulator, and Clamp Bundle',
                    'LPG22CC' => '22kg LPG Cylinder with Content',
                    'ACCPGS' => 'PRYCEGAS Stove',
                    'LPG2.7C' => '2.7kg content',
                  ];
                  if (isset($conversions[$product['product_code']])) {
                      $product['name'] = $conversions[$product['product_code']];
                  }
                  return $product;
                })
                ->filter(function ($values) use ($areaCode, $productId) {
                    $productIdFilter = ['01uBB000000jyJcYAI', '01uBB000000jyK6YAI', '01uBB000000jyJhYAI', '01uBB000000jyKBYAY', '01tBB0000012ErlYAE'];
                    $productCodeFilter = ['PHAASC', 'PHAVF', 'LPG11GC', 'LPG2.7GC', 'PHAPK', 'PHAPKS', 'TEST', 'CYL11', 'CYL2.7', 'CYL22'];
                    $pricebookIdFilter = ['01s2v00000My81DAAR', '01s2v00000My81IAAR', '01s2v00000I1HEkAAN'];
                    $valuesToFilter = array_merge($productIdFilter, $productCodeFilter, $pricebookIdFilter);
                    if (isset($productId)) {
                        return !in_array($values['product_id'], $valuesToFilter) && !in_array($values['product_code'], $valuesToFilter) && !in_array($values['pricebook_id'], $valuesToFilter) && substr($values['pricebook_id'], -4) === $areaCode && $values['product_id'] === $productId;
                    }
                    return !in_array($values['product_id'], $valuesToFilter) && !in_array($values['product_code'], $valuesToFilter) && !in_array($values['pricebook_id'], $valuesToFilter) && substr($values['pricebook_id'], -4) === $areaCode;
                })->sortBy('name')->values()->all();

            return $products;

        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function insertProductsFromSalesforceToDatabase() {
        $products = $this->fetchProductsFromSalesforce();

        Product::query()->truncate();

        $container = [];
        foreach ($products as $p) {
            $container[] = [
                'name' => $p['Name'],
                'pricebook_id' => $p['Pricebook2Id'],
                'pricebook_entry_id' => $p['Id'],
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
}
