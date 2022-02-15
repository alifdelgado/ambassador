<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Link;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function index()
    {
        return OrderResource::collection(Order::with('orderItems')->get());
    }

    public function store(Request $request)
    {
        $link = Link::whereCode($request->input('code'))->first();
        if(!$link) {
            abort(400, 'Invalid code');
        }
        try {
            $lineItems = [];
            DB::beginTransaction();
            $order = new Order();
            $order->code = $link->code;
            $order->user_id = $link->user->id;
            $order->embassador_email = $link->user->email;
            $order->first_name = $request->input('first_name');
            $order->last_name = $request->input('last_name');
            $order->email = $request->input('email');
            $order->address = $request->input('address');
            $order->country = $request->input('country');
            $order->city = $request->input('city');
            $order->zip = $request->input('zip');
            $order->save();
            foreach($request->input('products') as $item) {
                $product = Product::find($item['product_id']);
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_title = $order->title;
                $orderItem->price = $order->price;
                $orderItem->quantity = $item['quantity'];
                $orderItem->ambassador_revenue = 0.1 * $product->price * $item['quantity'];
                $orderItem->admin_revenue = 0.9 * $product->price * $item['quantity'];
                $orderItem->save();
                $lineItems[] = [
                    'name'  =>  $product->title,
                    'description'   =>  $product->description,
                    'images'    =>  [
                        $product->image
                    ],
                    'amount'    =>  100 * $product->price,
                    'currency'  =>  'usd',
                    'quantity'  =>  $item['quantity']
                ];
            }
            $stripe = Stripe::make(env('STRIPE_SECRET'));
            $source = $stripe->checkout()->session()->create([
                'payment_method_types'   => ['card'],
                'lin_items' =>  $lineItems,
                'success_url'   =>  env('CHECKOUT_URL') . '/success?source={CHECKOUT_SESSION_ID}',
                'cancel_url'    =>  env('CHECKOUT_URL') . '/error'
            ]);
            $order->transaction_id = $source['id'];
            $order->save();
            DB::commit();
            return $source;
        }catch(Throwable $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage()],400);
        }
    }

    public function confirm(Request $request)
    {
        $order = Order::whereTransactionId($request->input('source'))->first();
        if(!$order) {
            return response(['order' => 'Order not found'], 404);
        }
        $order->complete = 1;
        $order->save();
        return ['message' =>'success'];
    }
}
