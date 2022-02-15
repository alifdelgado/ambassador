<?php

namespace App\Http\Controllers;

use Cache;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Events\ProductUpdatedEvent;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Product::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product = Product::create($request->only('title', 'description', 'image', 'price'));
        event(new ProductUpdatedEvent);
        return response($product, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return $product;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $product->update($request->only('title', 'description', 'image', 'price'));
        event(new ProductUpdatedEvent);
        return response($product, Response::HTTP_ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();
        event(new ProductUpdatedEvent);
        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function frontend()
    {
        return Cache::remember('products_frontend', 30*60, fn() => Product::all());
    }

    public function backend(Request $request)
    {
        Cache::forget('products_backend');
        $page = $request->input('page', 1);
        $products = Cache::remember('products_backend', 30*60, fn() => Product::all());
        $s = $request->input('s');
        if(Str::length($s)) {
            $products = $products
                            ->filter(
                                    fn(Product $product) =>
                                        Str::contains($product->title, $s) || Str::contains($product->description, $s)
                                );
        }
        $total = $products->count();
        $sort = $request->input('sort');
        if(Str::length($sort)) {
            if($sort === 'asc') {
                $products = $products->sort([
                    fn($a, $b) => $a['price'] <=> $b['price']
                ]);
            } else {
                $products = $products->sort([
                    fn($a, $b) => $b['price'] <=> $a['price']
                ]);
            }
        }
        return [
            'data'  =>  $products->forPage($page, 9)->values(),
            'meta'  =>  [
                'total'     =>  $total,
                'page'      =>  $page,
                'last_page' =>  ceil($total/9)
            ]
        ];
    }
}
