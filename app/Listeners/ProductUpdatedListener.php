<?php

namespace App\Listeners;

use App\Events\ProductUpdatedEvent;
use Cache;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductUpdatedListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ProductUpdatedEvent $event)
    {
        Cache::forget('products_frontend');
        Cache::forget('products_backend');
    }
}
