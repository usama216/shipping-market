<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Payments\Stripe;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Redirect;

class TransactionController extends Controller
{
    protected $transactionRepository, $stripeClient;
    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
        $this->stripeClient = new Stripe();
    }


    public function index()
    {

        return Inertia::render('Admin/Transaction/Report', [
            'transactions' => $this->transactionRepository->getAllTransaction(),
        ]);
    }

    public function userTransaction(User $user)
    {
        $transactions = $this->transactionRepository->getTransactionById($user->id);
        return Inertia::render('Admin/Users/EditTabs/Transaction', ['transactions' => $transactions, 'user' => $user]);
    }

    public function refundTransaction(Transaction $transaction)
    {
        try {
            DB::beginTransaction();

            $stripeResponse = $this->stripeClient->refund($transaction->transaction_id);
            if ($stripeResponse->status === "succeeded") {
                $this->transactionRepository->update($transaction->id, [
                    'status' => Transaction::STATUS_REFUND,
                ]);
            } else {
                return Redirect::back()->withErrors(['message' => $stripeResponse]);
            }
            DB::commit();
            return Redirect::back()->with('alert', 'Refund successful.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

}
