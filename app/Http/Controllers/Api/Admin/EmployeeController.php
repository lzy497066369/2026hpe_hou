<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\CurrentUserResolver;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function index(Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);

        $query = User::query()->orderByDesc('created_at');
        if ($keyword = $request->query('keyword')) {
            $query->where(function ($query) use ($keyword): void {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('employee_no', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        return ApiResponse::success($query->paginate((int) $request->query('pageSize', 20)));
    }

    public function store(Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'employeeNo' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:user,admin'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'employee_no' => $data['employeeNo'],
            'email' => $data['email'],
            'nickname' => $data['nickname'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'role' => $data['role'] ?? 'user',
            'password' => 'unused',
            'status' => 'active',
        ]);

        return ApiResponse::success($this->formatUser($user));
    }

    public function update(string $employeeId, Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);

        $user = User::query()->findOrFail($employeeId);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,disabled'],
            'role' => ['sometimes', 'in:user,admin'],
        ]);

        $user->fill($data)->save();

        return ApiResponse::success($this->formatUser($user));
    }

    public function destroy(string $employeeId, Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);

        User::query()->findOrFail($employeeId)->delete();

        return ApiResponse::success(['success' => true]);
    }

    public function export(Request $request, CurrentUserResolver $resolver): StreamedResponse
    {
        $this->requireAdmin($request, $resolver);

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'employee_no', 'email', 'nickname', 'phone', 'address', 'role', 'status']);
            User::query()->orderBy('id')->each(function (User $user) use ($out): void {
                fputcsv($out, [$user->id, $user->name, $user->employee_no, $user->email, $user->nickname, $user->phone, $user->address, $user->role, $user->status]);
            });
            fclose($out);
        }, 'employees.csv');
    }

    private function requireAdmin(Request $request, CurrentUserResolver $resolver): void
    {
        $user = $resolver->require($request);
        abort_if($user->role !== 'admin', 403, 'Forbidden.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
            'phone' => $user->phone,
            'address' => $user->address,
            'role' => $user->role,
            'status' => $user->status,
        ];
    }
}
