<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\CurrentUserResolver;
use App\Support\ApiResponse;
use App\Support\AdminDisplay;
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
                    ->orWhere('username', 'like', "%{$keyword}%")
                    ->orWhere('employee_no', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('work_city', 'like', "%{$keyword}%")
                    ->orWhere('mail_code', 'like', "%{$keyword}%")
                    ->orWhere('work_address_code', 'like', "%{$keyword}%");
            });
        }

        return ApiResponse::success($query->paginate((int) $request->query('pageSize', 20)));
    }

    public function store(Request $request, CurrentUserResolver $resolver): JsonResponse
    {
        $this->requireAdmin($request, $resolver);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'employeeNo' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'workCity' => ['nullable', 'string', 'max:100'],
            'mailCode' => ['nullable', 'string', 'max:100'],
            'workAddressCode' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:user,admin'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'employee_no' => $data['employeeNo'],
            'email' => $data['email'],
            'nickname' => $data['nickname'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'work_city' => $data['workCity'] ?? null,
            'mail_code' => $data['mailCode'] ?? null,
            'work_address_code' => $data['workAddressCode'] ?? null,
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
            'username' => ['nullable', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'workCity' => ['nullable', 'string', 'max:100'],
            'mailCode' => ['nullable', 'string', 'max:100'],
            'workAddressCode' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:active,disabled'],
            'role' => ['sometimes', 'in:user,admin'],
        ]);

        $mappedData = collect($data)
            ->except(['workCity', 'mailCode', 'workAddressCode'])
            ->all();

        if (array_key_exists('workCity', $data)) {
            $mappedData['work_city'] = $data['workCity'];
        }

        if (array_key_exists('mailCode', $data)) {
            $mappedData['mail_code'] = $data['mailCode'];
        }

        if (array_key_exists('workAddressCode', $data)) {
            $mappedData['work_address_code'] = $data['workAddressCode'];
        }

        $user->fill($mappedData)->save();

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
            fputcsv($out, ['id', 'preferred_name', 'name', 'employee_no', 'email', 'nickname', 'phone', 'address', 'work_city', 'mail_code', 'work_address_code', 'role', 'status']);
            User::query()->orderBy('id')->each(function (User $user) use ($out): void {
                fputcsv($out, [$user->id, AdminDisplay::preferredName($user), $user->name, $user->employee_no, $user->email, $user->nickname, $user->phone, $user->address, $user->work_city, $user->mail_code, $user->work_address_code, $user->role, $user->status]);
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
            'username' => $user->username,
            'preferredName' => AdminDisplay::preferredName($user),
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
            'phone' => $user->phone,
            'address' => $user->address,
            'workCity' => $user->work_city,
            'mailCode' => $user->mail_code,
            'workAddressCode' => $user->work_address_code,
            'role' => $user->role,
            'status' => $user->status,
        ];
    }
}
