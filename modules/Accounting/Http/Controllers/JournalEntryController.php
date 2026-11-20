<?php

namespace Modules\Accounting\Http\Controllers;

use Modules\Accounting\Entities\PaymentType;
use Modules\Accounting\Entities\BusinessLocation;
use Modules\Accounting\Entities\Currency;
use Modules\Accounting\Entities\PaymentDetail;
use Modules\Accounting\Services\FlashService;
use App\Utils\Util;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;
use Yajra\DataTables\Facades\DataTables;

class JournalEntryController extends Controller
{
    private $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $orderBy = $request->order_by;
        $orderByDir = $request->order_by_dir;
        $search = $request->s;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;
        $chart_of_account_id = $request->chart_of_account_id;
        $businessId = session('business.id');
        $hasLocation = Schema::hasColumn('journal_entries', 'location_id');
        $hasCreatedBy = Schema::hasColumn('journal_entries', 'created_by_id');
        $hasBusinessLocations = Schema::hasTable('business_locations');
        $hasUsers = Schema::hasTable('users');
        $hasSubtypes = Schema::hasTable('account_subtypes');
        $hasDetailTypes = Schema::hasTable('account_detail_types');

        $query = JournalEntry::query()
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where(function ($q) use ($businessId) {
                $q->where('chart_of_accounts.business_id', $businessId)
                    ->orWhere('chart_of_accounts.business_id', 0)
                    ->orWhereNull('chart_of_accounts.business_id');
            })
            ->where(function ($q) {
                $q->where('journal_entries.reversed', 0)->orWhereNull('journal_entries.reversed');
            });

        if ($hasLocation && $hasBusinessLocations) {
            $query->leftJoin('business_locations', 'business_locations.id', '=', 'journal_entries.location_id');
        }
        if ($hasCreatedBy && $hasUsers) {
            $query->leftJoin('users', 'users.id', '=', 'journal_entries.created_by_id');
        }
        if ($hasSubtypes) {
            $query->leftJoin('account_subtypes', 'account_subtypes.id', '=', 'chart_of_accounts.account_subtype_id');
        }
        if ($hasDetailTypes) {
            $query->leftJoin('account_detail_types', 'account_detail_types.id', '=', 'chart_of_accounts.detail_type_id');
        }

        $query
            ->when($orderBy, function (Builder $q) use ($orderBy, $orderByDir) {
                $q->orderBy($orderBy, $orderByDir);
            })
            ->when($end_date, function ($q) use ($start_date, $end_date) {
                $q->whereBetween('journal_entries.date', [$start_date, $end_date]);
            })
            ->when($hasLocation && $location_id, function ($q) use ($location_id) {
                $q->where('journal_entries.location_id', $location_id);
            })
            ->when($chart_of_account_id, function ($q) use ($chart_of_account_id) {
                $q->where('journal_entries.chart_of_account_id', $chart_of_account_id);
            })
            ->when($search, function (Builder $q) use ($search, $hasLocation, $hasBusinessLocations, $hasCreatedBy, $hasUsers) {
                $q->where(function ($inner) use ($search, $hasLocation, $hasBusinessLocations, $hasCreatedBy, $hasUsers) {
                    $inner->where('journal_entries.transaction_number', 'like', "%$search%")
                        ->orWhere('journal_entries.id', 'like', "%$search%");
                    if ($hasLocation && $hasBusinessLocations) {
                        $inner->orWhere('business_locations.name', 'like', "%$search%");
                    }
                    if ($hasCreatedBy && $hasUsers) {
                        $inner->orWhere('users.name', 'like', "%$search%");
                    }
                });
            });

        $select = [
            'journal_entries.id',
            'journal_entries.date',
            'journal_entries.debit',
            'journal_entries.credit',
            'journal_entries.transaction_number',
            'chart_of_accounts.account_type',
            'chart_of_accounts.name as account_name',
        ];
        if ($hasCreatedBy) {
            $select[] = 'journal_entries.created_by_id';
        }
        if ($hasLocation) {
            $select[] = 'journal_entries.location_id';
        }
        $query->select($select)
            ->selectRaw($hasLocation && $hasBusinessLocations ? "COALESCE(business_locations.name,'—') as business_location" : "'—' as business_location")
            ->selectRaw($hasCreatedBy && $hasUsers ? "COALESCE(users.name,'—') as created_by" : "'—' as created_by")
            ->selectRaw($hasSubtypes ? 'account_subtypes.name as account_subtype' : 'NULL as account_subtype')
            ->selectRaw($hasDetailTypes ? 'account_detail_types.name as account_detail_type' : 'NULL as account_detail_type');

        $data = $query->orderByDesc('journal_entries.date')->orderByDesc('journal_entries.id')->limit(500)->get();
        $chart_of_accounts = ChartOfAccount::query()->orderBy('gl_code')->get(['id', 'name']);

        return view('accounting::journal_entry.index', compact('data', 'chart_of_accounts'));
    }

    public function get_journal_entries(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;
        $chart_of_account_id = $request->chart_of_account_id;
        $hasLocation = Schema::hasColumn('journal_entries', 'location_id');
        $hasCreatedBy = Schema::hasColumn('journal_entries', 'created_by_id');
        $hasBusinessLocations = Schema::hasTable('business_locations');
        $hasUsers = Schema::hasTable('users');

        $query = DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->when($hasLocation && $hasBusinessLocations, function ($q) {
                $q->leftJoin('business_locations', 'business_locations.id', '=', 'journal_entries.location_id');
            })
            ->when($hasCreatedBy && $hasUsers, function ($q) {
                $q->leftJoin('users', 'users.id', '=', 'journal_entries.created_by_id');
            })
            ->when($end_date, function ($q) use ($start_date, $end_date) {
                $q->whereBetween('journal_entries.date', [$start_date, $end_date]);
            })
            ->when($hasLocation && $location_id, function ($q) use ($location_id) {
                $q->where('journal_entries.location_id', $location_id);
            })
            ->when($chart_of_account_id, function ($q) use ($chart_of_account_id) {
                $q->where('journal_entries.chart_of_account_id', $chart_of_account_id);
            })
            ->where(function ($q) {
                $bid = session('business.id');
                $q->where('chart_of_accounts.business_id', $bid)
                    ->orWhere('chart_of_accounts.business_id', 0)
                    ->orWhereNull('chart_of_accounts.business_id');
            })
            ->where(function ($q) {
                $q->where('journal_entries.reversed', 0)->orWhereNull('journal_entries.reversed');
            })
            ->selectRaw(
                "journal_entries.id,journal_entries.date,journal_entries.debit,journal_entries.credit,journal_entries.transaction_number,".
                ($hasLocation && $hasBusinessLocations ? "COALESCE(business_locations.name,'—')" : "'—'")." as business_location,".
                "chart_of_accounts.account_type,chart_of_accounts.name account_name,".
                ($hasCreatedBy && $hasUsers ? "COALESCE(users.name,'—')" : "'—'").' as created_by'
            );
        return DataTables::of($query)->editColumn('debit', function ($data) {
            if (!empty($data->debit)) {
                return number_format($data->debit, 2);
            }
        })->editColumn('credit', function ($data) {
            if (!empty($data->credit)) {
                return number_format($data->credit, 2);
            }
        })->editColumn('account_type', function ($data) {
            if ($data->account_type == 'asset') {
                return trans_choice('accounting::general.asset', 1);
            }
            if ($data->account_type == 'expense') {
                return trans_choice('accounting::general.expense', 1);
            }
            if ($data->account_type == 'equity') {
                return trans_choice('accounting::general.equity', 1);
            }
            if ($data->account_type == 'liability') {
                return trans_choice('accounting::general.liability', 1);
            }
            if ($data->account_type == 'income') {
                return trans_choice('accounting::general.income', 1);
            }
        })->editColumn('action', function ($data) {
            $action = '<div class="btn-group"><button type="button" class="btn btn-info btn-xs dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="true"><i class="fa fa-navicon"></i></button> <ul class="dropdown-menu dropdown-menu-right" role="menu">';
            $action .= '<li><a href="' . url('accounting/journal_entry/' . $data->transaction_number . '/show') . '" class="">' . trans_choice('accounting::lang.detail', 2) . '</a></li>';
            $action .= "</ul></li></div>";
            return $action;
        })->editColumn('id', function ($data) {
            return '<a href="' . url('accounting/journal_entry/' . $data->transaction_number . '/show') . '">' . $data->id . '</a>';
        })->rawColumns(['id', 'action'])->make(true);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $chart_of_accounts = ChartOfAccount::forBusiness()->where('active', 1)->orderBy('gl_code')->get();
        $payment_types = PaymentType::getTypesCollection();
        $business_locations = BusinessLocation::getDropdownCollection(session('business.id'));
        $currencies = Currency::all();

        return view('accounting::journal_entry.create', compact('chart_of_accounts', 'payment_types', 'business_locations', 'currencies'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'location_id' => ['required'],
            'currency_id' => ['required'],
            'journal_entry_data' => ['required', 'array'], // {'debit', 'credit', 'amount', 'notes'}
            'date' => ['required', 'date'],
        ]);

        foreach ($request->journal_entry_data as $data) {
            try {
                DB::beginTransaction();

                $payment_detail = new PaymentDetail();
                $payment_detail->created_by_id = Auth::id();
                $payment_detail->payment_type_id = $request->payment_type_id;
                $payment_detail->transaction_type = 'journal_manual_entry';
                $payment_detail->save();

                //debit account
                $transaction_number = get_uniqid();
                $journal_entry = new JournalEntry();
                $journal_entry->created_by_id = Auth::id();
                $journal_entry->payment_detail_id = $payment_detail->id;
                $journal_entry->transaction_number = $transaction_number;
                $journal_entry->location_id = $request->location_id;
                $journal_entry->currency_id = $request->currency_id;
                $journal_entry->chart_of_account_id = $data['debit'];
                $journal_entry->transaction_type = 'manual_entry';
                $journal_entry->date = $request->date;
                $date = explode('-', $request->date);
                $journal_entry->month = $date[1];
                $journal_entry->year = $date[0];
                $journal_entry->debit = $data['amount'];
                $journal_entry->manual_entry = 1;
                $journal_entry->notes = $data['notes'];
                $journal_entry->save();

                //credit account
                $journal_entry = new JournalEntry();
                $journal_entry->created_by_id = Auth::id();
                $journal_entry->transaction_number = $transaction_number;
                $journal_entry->payment_detail_id = $payment_detail->id;
                $journal_entry->location_id = $request->location_id;
                $journal_entry->currency_id = $request->currency_id;
                $journal_entry->chart_of_account_id = $data['credit'];
                $journal_entry->transaction_type = 'manual_entry';
                $journal_entry->date = $request->date;
                $date = explode('-', $request->date);
                $journal_entry->month = $date[1];
                $journal_entry->year = $date[0];
                $journal_entry->credit = $data['amount'];
                $journal_entry->manual_entry = 1;
                $journal_entry->notes = $data['notes'];
                $journal_entry->save();

                activity()
                    ->on($journal_entry)
                    ->withProperties(['id' => $journal_entry->id])
                    ->log('Create Journal Entry');

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return (new FlashService())->onException($e)->redirectBackWithInput();
            }
        }

        (new FlashService())->onSave();
        return redirect('accounting/journal_entry');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $data = JournalEntry::where('transaction_number', $id)
            ->with('chart_of_account')
            ->with('business_location') //Filtered by business_id session
            ->get();
        abort_if($data->isEmpty(), 404);
        $line = $data->first(fn ($entry) => $entry->source_type && $entry->source_id) ?? $data->first();
        $source = \App\Services\Accounting\SourceDocumentResolver::forJournal($line);

        return view('accounting::journal_entry.show', compact('data', 'source'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('accounting::journal_entry.edit');
    }

    public function reverse($old_transaction_number)
    {
        $journalRows = JournalEntry::query()
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where('journal_entries.transaction_number', $old_transaction_number)
            ->where(function ($q) {
                $bid = session('business.id');
                $q->where('chart_of_accounts.business_id', $bid)->orWhere('chart_of_accounts.business_id', 0);
            })
            ->select('journal_entries.*')
            ->get();

        JournalEntry::query()
            ->whereIn('id', $journalRows->pluck('id'))
            ->update(['reversed' => 1, 'reversible' => 0]);
        //create new transactions to reverse these
        $new_transaction_number = uniqid();

        foreach ($journalRows as $key) {
            if (empty($key->debit)) {
                //credit account
                $journal_entry = new JournalEntry();
                $journal_entry->created_by_id = Auth::id();
                $journal_entry->transaction_number = $new_transaction_number;
                $journal_entry->payment_detail_id = $key->payment_detail_id;
                $journal_entry->location_id = $key->location_id;
                $journal_entry->currency_id = $key->currency_id;
                $journal_entry->chart_of_account_id = $key->chart_of_account_id;
                $journal_entry->transaction_type = $key->transaction_type;
                $journal_entry->date = date("Y-m-d");
                $date = explode('-', date("Y-m-d"));
                $journal_entry->month = $date[1];
                $journal_entry->year = $date[0];
                $journal_entry->debit = $key->credit;
                $journal_entry->reference = $key->reference;
                $journal_entry->manual_entry = $key->manual_entry;
                $journal_entry->notes = $key->notes;
                $journal_entry->save();
            } else {
                //debit account
                $journal_entry = new JournalEntry();
                $journal_entry->created_by_id = Auth::id();
                $journal_entry->transaction_number = $new_transaction_number;
                $journal_entry->payment_detail_id = $key->payment_detail_id;
                $journal_entry->location_id = $key->location_id;
                $journal_entry->currency_id = $key->currency_id;
                $journal_entry->chart_of_account_id = $key->chart_of_account_id;
                $journal_entry->transaction_type = $key->transaction_type;
                $journal_entry->date = date("Y-m-d");
                $date = explode('-', date("Y-m-d"));
                $journal_entry->month = $date[1];
                $journal_entry->year = $date[0];
                $journal_entry->credit = $key->debit;
                $journal_entry->reference = $key->reference;
                $journal_entry->manual_entry = $key->manual_entry;
                $journal_entry->notes = $key->notes;
                $journal_entry->save();
            }
        }
        activity()
            ->on($journal_entry)
            ->withProperties(['id' => $journal_entry->id])
            ->log('Reverse Journal Entry');
        (new FlashService())->onSave();
        return redirect()->back()->with("transaction_number", $new_transaction_number);
    }
}
