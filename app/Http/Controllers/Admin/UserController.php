<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminTemporaryPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'system');
        return view('admin.users.index', compact('tab'));
    }

    public function data(Request $request)
    {
        $tab = $request->get('tab', 'system');

        $query = User::query()->select([
            'id', 'name', 'email', 'role', 'email_verified_at', 'google_id',
            'must_change_password', 'created_at', 'updated_at',
        ]);

        if ($tab === 'guests') {
            $query->where('role', 'guest');
        } else {
            $query->whereIn('role', ['admin', 'editor']);
        }

        return DataTables::of($query)
            ->editColumn('email_verified_at', fn ($u) => $u->email_verified_at ? 'Yes' : 'No')
            ->editColumn('google_id', fn ($u) => $u->google_id ? 'Yes' : 'No')
            ->editColumn('must_change_password', fn ($u) => $u->must_change_password ? 'Yes' : 'No')
            ->editColumn('created_at', fn ($u) => optional($u->created_at)->format('Y-m-d'))
            ->rawColumns([])
            ->make(true);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|in:admin,editor,guest',
        ]);

        $validated['password']          = Hash::make($validated['password']);
        $validated['email_verified_at'] = now();

        User::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'User created successfully.',
            ]);
        }

        return redirect()->route('admin.users.index')->with('status', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function editAjax($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name'  => 'required|max:255',
            'email' => 'required|email|max:255',
            'role'  => 'required|in:admin,editor,guest',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->role  = $validated['role'];
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'User updated successfully']);
    }

    public function resetPassword(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You cannot reset your own password from here.',
            ], 422);
        }

        $tempPassword = Str::password(12, true, true, false, false);

        $user->password             = Hash::make($tempPassword);
        $user->must_change_password = true;
        $user->save();

        try {
            Mail::to($user->email)->send(new AdminTemporaryPasswordMail($user->name, $tempPassword));
        } catch (\Throwable $e) {
            Log::error('Failed to send temporary password email: '.$e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Password was reset, but the email could not be sent. Please contact the user manually.',
            ], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Temporary password sent to '.$user->email,
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted successfully.');
    }
}
