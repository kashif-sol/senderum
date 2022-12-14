<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class OrderController extends Controller
{
    public function index()
    {
        $shop = Auth::user();
        $orders = $shop->api()->rest('GET', "/admin/api/2021-04/orders.json", ["fields" => "id,order_number,contact_email,created_at,total_price"]);
        $data = $orders['body']->container['orders'];
        return view('orders', compact('data'));
    }
    public function order_view()
    {
        $shop = Auth::user();
        $orders = $shop->api()->rest('GET', "/admin/api/2021-04/orders.json", ["fields" => "id,order_number,contact_email,created_at,total_price"]);
        $data = $orders['body']->container['orders'];
        return view('orders', compact('data'));
    }

    public function getOrderID(Request $request)
    {

        $shop = Auth::user();


        $rest = implode(",", $request->order_ids);

        $query = [
            'ids' => $rest,
            'limit' => 250,
        ];

        $orders = $shop->api()->rest('GET', "/admin/api/2021-04/orders.json", $query);
        $shopName = DB::table('users')->orderBy('id', 'desc')->where('name', $shop->name)->first();
        $shopData = DB::table('shops_otherdetails')->orderBy('id', 'desc')->where('shop_id', $shopName->id)->first();
        $orderss = $orders['body']['container']['orders'];
        
       if(filled($orderss)) {
           foreach ($orderss as $order) {
               $ch = curl_init();

               $postdata['order_id'] = $order['id'];
               $postdata['status'] = $order['fulfillment_status'];
               $postdata['financial_status'] = $order['financial_status'];
               $postdata['cart_tax'] = $order['total_tax'];
               $postdata['currency'] = $order['currency'];
               $postdata['discount_tax'] = $order['current_total_discounts'];
               $postdata['discount_total'] = $order['current_total_discounts'];
               if (!$order['shipping_lines'] == null) {
                   $postdata['shipping_tax'] = $order['shipping_lines'][0]['price'];
               } else {
                   $postdata['shipping_tax'] = '';
               }
               $postdata['shipping_total'] = $order['total_shipping_price_set']['shop_money']['amount'];
               $postdata['subtotal'] = $order['subtotal_price'];
               $postdata['tax_totals'] = $order['shipping_lines'];
               $postdata['taxes'] = $order['shipping_lines'];
               $postdata['total'] = $order['subtotal_price'];
               $postdata['total_discount'] = $order['current_total_discounts'];
               $postdata['total_tax'] = $order['total_tax'];
               $postdata['total_refunded'] = $order['total_tax'];
               $postdata['total_tax_refunded'] = $order['total_tax'];
               if ($order['financial_status'] == 'paid') {
                   $postdata['status'] = 1;
               } else {
                   $postdata['status'] = 0;
               }
               // $postdata['items']=$order['line_items'];
               // dd($postdata['items']);
               $order_itms = $order['line_items'];
               foreach ($order_itms as $key => $item) {
                   $postdata['items'][]['product_id'] = $item['id'];
                   $postdata['items'][]['name'] = $item['name'];
                   $postdata['items'][]['description'] = $item['name'];
                   $postdata['items'][]['quantity'] = $item['quantity'];
                   $postdata['items'][]['subtotal'] = $item['price'];
                   $postdata['items'][]['total'] = $item['price'];
                   $postdata['items'][]['weight'] = $order['total_weight'];
                   $postdata['items'][]['subtotal_tax'] = $order['total_weight'];
                   $postdata['items'][]['tax_class'] = $order['total_weight'];
                   $postdata['items'][]['tax_status'] = $item['taxable'];
               }


               $postdata['billing']['billing_first_name'] = $order['billing_address']['first_name'];
               $postdata['billing']['billing_last_name'] = $order['billing_address']['last_name'];
               $postdata['billing']['billing_company'] = $order['billing_address']['company'];
               $postdata['billing']['billing_address_1'] = $order['billing_address']['address1'];
               $postdata['billing']['billing_address_2'] = $order['billing_address']['address2'];
               $postdata['billing']['billing_city'] = $order['billing_address']['city'];
               $postdata['billing']['billing_state'] = $order['billing_address']['city'];
               $postdata['billing']['billing_postcode'] = $order['billing_address']['zip'];
               $postdata['billing']['billing_country'] = $order['billing_address']['country'];
               $postdata['billing']['billing_email'] = $order['email'];
               $postdata['billing']['billing_phone'] = $order['customer']['phone'];

               $postdata['shipping']['shipping_first_name'] = $order['shipping_address']['first_name'];
               $postdata['shipping']['shipping_last_name'] = $order['shipping_address']['last_name'];
               $postdata['shipping']['shipping_company'] = $order['shipping_address']['company'];
               $postdata['shipping']['shipping_address_1'] = $order['shipping_address']['address1'];
               $postdata['shipping']['shipping_address_2'] = $order['shipping_address']['address2'];
               $postdata['shipping']['shipping_city'] = $order['shipping_address']['city'];
               $postdata['shipping']['shipping_state'] = $order['shipping_address']['country'];
               $postdata['shipping']['shipping_postcode'] = $order['shipping_address']['zip'];
               $postdata['shipping']['shipping_country'] = $order['shipping_address']['country'];
               $postdata['shipping']['address']['first_name'] = $order['shipping_address']['first_name'];
               $postdata['shipping']['address']['last_name'] = $order['shipping_address']['last_name'];
               $postdata['shipping']['address']['company'] = $order['shipping_address']['company'];
               $postdata['shipping']['address']['address_1'] = $order['shipping_address']['address1'];
               $postdata['shipping']['address']['address_2'] = $order['shipping_address']['address2'];
               $postdata['shipping']['address']['city'] = $order['shipping_address']['city'];
               $postdata['shipping']['address']['state'] = $order['shipping_address']['country'];
               $postdata['shipping']['address']['postcode'] = $order['shipping_address']['zip'];
               $postdata['shipping']['address']['country'] = $order['shipping_address']['country'];
               $postdata['shipping']['address']['email'] = $order['email'];
               $postdata['shipping']['address']['phone'] = $order['customer']['phone'];
               // $array_final =  preg_replace('/"([a-zA-Z]+[a-zA-Z0-9_]*)":/','$1:',$postdata); ;
               // dd($array_final);


               $array = [
                   'orders' => array($postdata),
               ];
               // dd($array);

               $data = json_encode($array);
               // echo $data;dd();
               // $newPhrase = str_replace('"', '', $phrase);
               // dd($data);


               $header = [
                   'x-api-key:' . $shopData->identity,
                   'Accept: application/json',
                   'Content-Type: application/json',
                   'Authorization: Bearer ' . $shopData->authentication,
               ];

               $ch = curl_init('https://senderum.com/api/vendors/sync/webhooks/shop/sync-orders');
               curl_setopt($ch, CURLOPT_POST, 1);
               curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
               curl_setopt($ch, CURLOPT_VERBOSE, true);
               curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
               curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
               $output = curl_exec($ch);
               if (curl_errno($ch)) {
                   $error_msg = curl_error($ch);

               }
               curl_close($ch);
               $output = json_decode($output);
             
               // dd($output->orders->$order['id']);
               // dd($output->orders[0]->$order['id']);
               if (isset($output->status)) {
                   if ($output->status == true) {
                       $save = Order::create([
                           'order_id' => $order['id'],
                           'api_id' => $output->orders->{$order['id']},

                       ]);
                       $save->save();
                   }
               }
           }
       }
          return Redirect::tokenRedirect('home', ['notice' => 'Orders sent']);
    }
    public function single_order($dataa,$shopdomain)
    {
        // dd($dataa);
        $shop = User::first();
        $shopName = DB::table('users')->orderBy('id', 'desc')->where('name', $shopdomain->name)->first();
        $shopData = DB::table('shops_otherdetails')->orderBy('id', 'desc')->where('shop_id', $shopName->id)->first();

        // $rest = $req->order_id;
        $id = $dataa['id'];
        $data = $shop->api()->rest('GET', "/admin/api/2022-04/orders/" . $id . ".json");
        // dd($data);
        $order = $data['body']['container']['order'];
        // dd($order);
        $postdata['order_id'] = $order['id'];
        $postdata['cart_tax'] = $order['total_tax'];
        $postdata['currency'] = $order['currency'];
        $postdata['discount_tax'] = $order['current_total_discounts'];
        $postdata['discount_total'] = $order['current_total_discounts'];
        if (!$order['shipping_lines'] == null) {
            $postdata['shipping_tax'] = $order['shipping_lines'][0]['price'];
        } else {
            $postdata['shipping_tax'] = '';
        }
        $postdata['shipping_total'] = $order['total_shipping_price_set']['shop_money']['amount'];
        $postdata['subtotal'] = $order['subtotal_price'];
        $postdata['tax_totals'] = $order['shipping_lines'];
        $postdata['taxes'] = $order['shipping_lines'];
        $postdata['total'] = $order['subtotal_price'];
        $postdata['total_discount'] = $order['current_total_discounts'];
        $postdata['total_tax'] = $order['total_tax'];
        $postdata['total_refunded'] = $order['total_tax'];
        $postdata['total_tax_refunded'] = $order['total_tax'];
        if ($order['financial_status'] == 'paid') {
            $postdata['status'] = 1;
        } else {
            $postdata['status'] = 0;
        }
        // $postdata['items']=$order['line_items'];
        // dd($postdata['items']);
        $order_itms = $order['line_items'];
        $line_it = array();
        foreach ($order_itms  as $key => $item) {
            $line['product_id'] = $item['id'];
            $line['name'] = $item['name'];
            $line['description'] = $item['name'];
            $line['quantity'] = $item['quantity'];
            $line['subtotal'] = $item['price'];
            $line['total'] = $item['price'];
            $line['price'] = $item['price'];
            $line['weight'] = $item['grams'];
            $line['subtotal_tax'] = $item['grams'];
            $line['tax_class'] = $item['grams'];
            $line['tax_status'] = $item['taxable'];
            $line_it[] =  $line;
        }
        $postdata['items'] = $line_it;

        $postdata['billing']['billing_first_name'] = $order['billing_address']['first_name'];
        $postdata['billing']['billing_last_name'] = $order['billing_address']['last_name'];
        $postdata['billing']['billing_company'] = $order['billing_address']['company'];
        $postdata['billing']['billing_address_1'] = $order['billing_address']['address1'];
        $postdata['billing']['billing_address_2'] = $order['billing_address']['address2'];
        $postdata['billing']['billing_city'] = $order['billing_address']['city'];
        $postdata['billing']['billing_state'] = $order['billing_address']['city'];
        $postdata['billing']['billing_postcode'] = $order['billing_address']['zip'];
        $postdata['billing']['billing_country'] = $order['billing_address']['country'];
        $postdata['billing']['billing_email'] = $order['email'];
        $postdata['billing']['billing_phone'] = $order['customer']['phone'];

        $postdata['shipping']['shipping_first_name'] = $order['shipping_address']['first_name'];
        $postdata['shipping']['shipping_last_name'] = $order['shipping_address']['last_name'];
        $postdata['shipping']['shipping_company'] = $order['shipping_address']['company'];
        $postdata['shipping']['shipping_address_1'] = $order['shipping_address']['address1'];
        $postdata['shipping']['shipping_address_2'] = $order['shipping_address']['address2'];
        $postdata['shipping']['shipping_city'] = $order['shipping_address']['city'];
        $postdata['shipping']['shipping_state'] = $order['shipping_address']['country'];
        $postdata['shipping']['shipping_postcode'] = $order['shipping_address']['zip'];
        $postdata['shipping']['shipping_country'] = $order['shipping_address']['country'];
        $postdata['shipping']['address']['first_name'] = $order['shipping_address']['first_name'];
        $postdata['shipping']['address']['last_name'] = $order['shipping_address']['last_name'];
        $postdata['shipping']['address']['company'] = $order['shipping_address']['company'];
        $postdata['shipping']['address']['address_1'] = $order['shipping_address']['address1'];
        $postdata['shipping']['address']['address_2'] = $order['shipping_address']['address2'];
        $postdata['shipping']['address']['city'] = $order['shipping_address']['city'];
        $postdata['shipping']['address']['state'] = $order['shipping_address']['country'];
        $postdata['shipping']['address']['postcode'] = $order['shipping_address']['zip'];
        $postdata['shipping']['address']['country'] = $order['shipping_address']['country'];
        $postdata['shipping']['address']['email'] = $order['email'];
        $postdata['shipping']['address']['phone'] = $order['customer']['phone'];

        $data = json_encode($postdata);
        // dd($data);
        $header = [
            'x-api-key:' . $shopData->identity,
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $shopData->authentication,
        ];
        $ch = curl_init('https://senderum.com/api/vendors/webhooks/shop/order-created');
        //  dd($ch);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER,  $header);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output);
        // dd($output);
        if ($output->status == true) {
            $save = Order::create([
                'order_id' => $order['id'],
                'api_id' => $output->orders->{$order['id']},

            ]);
            $save->save();
        }
    }
}
