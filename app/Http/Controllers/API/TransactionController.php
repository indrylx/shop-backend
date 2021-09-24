<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ItemNotFoundException;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit  = $request->input('limit');
        $status = $request->input('status');

        if($id)
        {
            $transacion = Transaction::with(['items.product'])->find($id);

            if($transacion)
            {
                return ResponseFormatter::success(
                    $transacion,
                    'Data Transaksi Berhasil Di Ambil'
                );
            }else
            {
                return ResponseFormatter::error(
                    null,
                    'Data Transaksi Tidak Ada',
                    404
                );
            }
        }

        $transacion = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

        if($status)
        {
            $transacion->where('status', $status);
        }
        return ResponseFormatter::success(
            $transacion->paginate($limit),
            'Data List Transaksi Berhasil Di ambil'
        );
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'exists:products,id',
            'total_price' => 'required',
            'shipping_price' => 'required',
            'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED'
    ]);

        $transacion = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status,
        ]);
        foreach($request->items as $product)
        {
            TransactionItem::create([
                'users_id' => Auth::user()->id,  
                'products_id' => $product['id'],
                'transactions_id' => $transacion->id,
                'quantity' => $product['quantity']

            ]);
        }

        return ResponseFormatter::success($transacion->load('items.product'), 'Transaksi Berhasil');
    }
}
