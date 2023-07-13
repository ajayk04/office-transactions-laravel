<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index()
    {
        $list = [];
        try {
            $list = Transaction::select([ 'id', 'type', 'amount', 'description', 'running_balance', 'created_at' ])
                        ->orderByDesc('id')->get();
        } catch (\Throwable $th) {
            Log::error($th);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Transactions',
            'data'    => $list
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'        => 'required|in:credit,debit',
            'amount'      => 'required|numeric',
            'description' => 'required',
        ], [
            'type.in' => 'The selected type value is invalid.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->messages()->first()
            ], 422);
        }

        $lastRecord = Transaction::latest()->first();
        $balance    = $lastRecord->running_balance ?? 0;
        $balance    = $request->type === 'credit' ? ($balance + $request->amount) : ($balance - $request->amount);

        Transaction::create([
            'type' => $request->type,
            'description' => $request->description,
            'amount'      => $request->amount,
            'running_balance' => $balance
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Transaction added successfully.',
        ]);
    }
}
