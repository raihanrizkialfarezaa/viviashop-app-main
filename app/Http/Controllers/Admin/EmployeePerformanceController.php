<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeePerformance;
use App\Models\EmployeeBonus;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeePerformanceController extends Controller
{
    public function index()
    {
        $employees = EmployeePerformance::getEmployeeList();
        $totalTransactions = EmployeePerformance::count();
        $totalRevenue = EmployeePerformance::sum('transaction_value');
        $averageTransaction = EmployeePerformance::avg('transaction_value');
        
        $topEmployee = EmployeePerformance::selectRaw('employee_name, SUM(transaction_value) as total_revenue')
                                        ->groupBy('employee_name')
                                        ->orderBy('total_revenue', 'desc')
                                        ->first();

        return view('admin.employee-performance.index', compact(
            'employees', 'totalTransactions', 'totalRevenue', 'averageTransaction', 'topEmployee'
        ));
    }

    public function data(Request $request)
    {
        $query = EmployeePerformance::selectRaw('
            employee_name,
            COUNT(*) as total_transactions,
            SUM(transaction_value) as total_revenue,
            AVG(transaction_value) as average_transaction,
            MAX(completed_at) as last_transaction
        ')->groupBy('employee_name');

        if ($request->has('period') && $request->period) {
            $period = $request->period;
            $now = Carbon::now();
            
            switch ($period) {
                case 'today':
                    $query->whereDate('completed_at', $now->toDateString());
                    break;
                case 'week':
                    $query->whereBetween('completed_at', [$now->startOfWeek(), $now->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('completed_at', $now->month)
                          ->whereYear('completed_at', $now->year);
                    break;
                case 'year':
                    $query->whereYear('completed_at', $now->year);
                    break;
            }
        }

        if ($request->has('employee') && $request->employee) {
            $query->where('employee_name', 'like', '%' . $request->employee . '%');
        }

        if ($request->has('sort_by') && $request->sort_by) {
            $sortBy = $request->sort_by;
            switch ($sortBy) {
                case 'highest':
                    $query->orderBy('total_revenue', 'desc');
                    break;
                case 'lowest':
                    $query->orderBy('total_revenue', 'asc');
                    break;
                case 'latest':
                    $query->orderBy('last_transaction', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('last_transaction', 'asc');
                    break;
                default:
                    $query->orderBy('total_revenue', 'desc');
            }
        } else {
            $query->orderBy('total_revenue', 'desc');
        }

        return DataTables::of($query)
            ->addColumn('formatted_revenue', function ($row) {
                return 'Rp ' . number_format($row->total_revenue, 0, ',', '.');
            })
            ->addColumn('formatted_average', function ($row) {
                return 'Rp ' . number_format($row->average_transaction, 0, ',', '.');
            })
            ->addColumn('formatted_date', function ($row) {
                return Carbon::parse($row->last_transaction)->format('d/m/Y H:i');
            })
            ->addColumn('actions', function ($row) {
                return '<div class="btn-group">
                    <a href="' . route('admin.employee-performance.show', $row->employee_name) . '" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> Detail
                    </a>
                    <button type="button" class="btn btn-sm btn-success" onclick="showBonusModal(\'' . $row->employee_name . '\')">
                        <i class="fas fa-gift"></i> Bonus
                    </button>
                </div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function show($employeeName)
    {
        $performances = EmployeePerformance::where('employee_name', $employeeName)
                                         ->with('order')
                                         ->orderBy('completed_at', 'desc')
                                         ->paginate(20);

        $bonuses = EmployeeBonus::where('employee_name', $employeeName)
                               ->with('givenBy')
                               ->orderBy('given_at', 'desc')
                               ->get();

        $stats = EmployeePerformance::getMonthlyStats($employeeName);

        return view('admin.employee-performance.show', compact('employeeName', 'performances', 'bonuses', 'stats'));
    }

    public function giveBonus(Request $request)
    {
        $request->validate([
            'employee_name' => 'required|string|max:255',
            'bonus_amount' => 'required|numeric|min:0',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string'
        ]);

        EmployeeBonus::create([
            'employee_name' => $request->employee_name,
            'bonus_amount' => $request->bonus_amount,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'notes' => $request->notes,
            'given_by' => Auth::id(),
            'given_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bonus berhasil diberikan!'
        ]);
    }

    public function bonusHistory()
    {
        $bonuses = EmployeeBonus::with('givenBy')
                               ->orderBy('given_at', 'desc')
                               ->paginate(20);

        return view('admin.employee-performance.bonus-history', compact('bonuses'));
    }
}
