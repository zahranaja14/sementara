<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Menampilkan daftar semua order di dashboard admin
    public function index()
    {
        $orders = Order::with('user')->get();
        return view('admin.dashboard', compact('orders'));
    }

    // Menampilkan detail order spesifik
    public function show($id)
    {
        $order = Order::with(['user', 'orderDetails.product'])->findOrFail($id);
        return view('admin.detail', compact('order'));
    }

    // Aksi untuk verifikasi (approve/reject)
    public function verify(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:lunas,dibatalkan',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $request) {
                if ($request->status === 'lunas') {
                    $order->update(['status' => 'lunas']);

                    // Kurangi stok produk yang dibeli
                    foreach ($order->orderDetails as $detail) {
                        $product = $detail->product;
                        if ($product->stok >= $detail->jumlah) {
                            $product->decrement('stok', $detail->jumlah);
                        } else {
                            throw new \Exception("Stok untuk produk '{$product->nama_produk}' tidak mencukupi.");
                        }
                    }
                } else {
                    $order->update(['status' => 'dibatalkan']);
                }
            });

            return redirect('/admin/dashboard')->with('success', 'Status pesanan berhasil diupdate!');
        } catch (\Exception $e) {
            return redirect('/admin/dashboard')->with('error', $e->getMessage());
        }
    }
}