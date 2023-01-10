<?php

namespace App\Mails;

use App\Module;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderOverviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public $overviewData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($chOrder)
    {
        $this->overviewData = $chOrder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $chOrder = $this->overviewData['order'];

        $module = find_module_package( 'marinar_orders' ); //orders
        $addons = $module->addons;
        $tplAddonPrefixes = $module->hookedAddonPrefixes();

        $chOrder->loadMissing(['addresses', 'payment', 'delivery']);

        return $this
            ->from("noreply@".app()->make("Site")->domain, config('app.name'))
//            ->view( config('marinar_orders.overview_template'), [
            ->view( config('marinar_orders.overview_template'), [
                'chOrder' => $chOrder,
                'tplAddonPrefixes' => $tplAddonPrefixes,
                'addons' => $addons,
            ]);
    }
}
