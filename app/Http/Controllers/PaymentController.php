<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\PaymentTransaction;

class PaymentController extends Controller
{
    public function getRemainingPaymentInfo($order_id)
    {
        // $decodedOrderId = urldecode($order_id);
        $booking = Booking::with(['package:id,package_name'])->where('order_id', $order_id)->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($booking->payment_type !== 'dp') {
            return response()->json(['message' => 'Bukan pembayaran DP'], 400);
        }

        $remainingAmount = ($booking->gross_amount * $booking->qty) - $booking->dp_amount;

        $response = [
            'booking_id' => $booking->booking_id,
            'order_id' => $booking->order_id,
            'customer_name' => $booking->customer_name,
            'customer_phone' => $booking->customer_phone,
            'gross_amount' => $booking->gross_amount,
            'total' => $booking->gross_amount * $booking->qty,
            'qty' => $booking->qty,
            'deposit' => $booking->dp_amount ,
            'remaining_amount' => $remainingAmount,
            'tour_date' => $booking->tour_date,
            'package' => $booking->package,
            'message' => 'Pembayaran belum lunas',
        ];

        if ($booking->payment_status === 'paid') {
            $response['message'] = 'Pembayaran DP Lunas';
        }
    
        return response()->json($response, 200);
    }


    public function startRemainingPayment($order_id)
    {
        // $decodedOrderId = urldecode($order_id);
        $booking = Booking::where('order_id', $order_id)->firstOrFail();

        
        if ($booking->payment_type !== 'dp' || $booking->payment_status === 'paid') {
            return response()->json(['message' => 'Tidak bisa diproses'], 400);
        }
        
        $remainingAmount = ($booking->gross_amount * $booking->qty) - $booking->dp_amount;
        
        $newTransaction = PaymentTransaction::create([
            'booking_id' => $booking->booking_id,
            'order_id' => $booking->order_id . '-2', 
            'amount' => $remainingAmount,
            'payment_for' => 'remaining',
            'status' => 'pending',            
        ]);

        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = false;
        // Set sanitization on (default)
        \Midtrans\Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        \Midtrans\Config::$is3ds = true;
        
        // Snap token
        $params = [
            'transaction_details' => [
                'order_id' => $newTransaction->order_id,
                'gross_amount' => $newTransaction->amount,
            ],
            'customer_details' => [
                'first_name' => $booking->customer_name,
                'email' => $booking->customer_email,
            ],
        ];
        
        $snapToken = \Midtrans\Snap::getSnapToken($params);

        $newTransaction->update([
            'snap_token' => $snapToken,
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/' . $snapToken,
        ]);

        return response()->json([
            'snap_token' => $snapToken,
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/' . $snapToken,
            'order' => [
                'booking_id' => $newTransaction->booking_id,
                'order_id' => $newTransaction->order_id,
                'amount' => $newTransaction->amount,
                'payment_for' => $newTransaction->payment_for,
                'updated_at' => $newTransaction->updated_at,
                'created_at' => $newTransaction->created_at,
                'transaction_id' => $newTransaction->transaction_id,
            ],
        ]);
        
    }

    public function index()
    {
        // $transactions = PaymentTransaction::get();
        $transactions = PaymentTransaction::with('booking')
        ->select('transaction_id', 'booking_id','order_id', 'amount', 'channel','status', 'payment_for', 'created_at',)
        ->get();
        return response()->json($transactions, 200);
    }

    public function show($booking_id)
    {
        $transaction = PaymentTransaction::where('booking_id', $booking_id)
        ->select('transaction_id', 'booking_id','order_id', 'amount', 'channel', 'status', 'payment_for', 'created_at',)
        ->get();


        if ($transaction->isEmpty()) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        return response()->json($transaction, 200);
    }


}
