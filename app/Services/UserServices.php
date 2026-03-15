<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserServices
{


    public function getUsers(int $take = 10, ?int $branch = null)
    {
        request()->validate([
            'column' => ['nullable', Rule::in(['firstname', 'middlename', 'lastname', 'username'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        return User::query()->with(['branch'])
            ->when(request('location_id'), fn ($q, $location) => $q->where('branch_id', $location))
            ->when(request('business_unit'), fn ($q, $business_unit) => $q->where('business_unit', $business_unit))
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',firstname,middlename,lastname,username) like '%{$keyword}%' ");
                }
            )
            ->when(
                request()->get('column') && request()->get('direction'),
                fn ($q) => $q->orderBy(request()->get('column'), request()->get('direction'))
            )

            ->latest()
            ->paginate(is_integer(request()->get('paginate')) ?? 1000000);
    }

    public function create(array $data, mixed $type): User
    {

        $user = new User();
        $user->firstname = $data['firstname'];
        $user->lastname = $data['lastname'];
        $user->middlename = $data['middlename'] ?? '';
        $user->avatar = $data['avatar'] ?? '';
        $user->contact = $data['contact'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->user_type = $type;
        $user->branch_id = request()->get('branch_id');
        $user->business_unit = request()->get('division');

        if (request()->hasFile('avatar')) {
            $user->avatar = request()->file('avatar')->store('users');
        }
        $user->save();
        $last_user = User::query()->with(['branch'])->findOrfail($user->id);

        return $last_user;
    }

    public function update(array $data, int $id): User
    {
        $user = User::query()->with(['branch'])->findOrfail($id);
        $user->firstname = $data['firstname'];
        $user->lastname = $data['lastname'];
        $user->middlename = $data['middlename'] ?? '';
        $user->avatar = $data['avatar'] ?? '';
        $user->contact = $data['contact'];
        $user->email = $data['email'];
        $user->username = $data['username'];
        $user->branch_id = request()->get('branch_id');
        $user->business_unit = request()->get('division');
        $user->user_type = request()->get('type');
        if (request()->hasFile('avatar')) {
            $user->avatar = request()->file('avatar')->store('users');
        }
        $user->save();

        return $user;
    }


    public function updatePassword(int $id)
    {
        $user = User::query()
            ->where('id', '!=', 1)
            ->findOrfail($id);

        $user->password = bcrypt(request()->get('password'));
        $user->save();
    }

    public function resetPassword(User $user, string $password): void
    {
        $user->password = bcrypt($password);
        $user->save();
    }

    /**
     * @throws ValidationException
     */
    public function changePassword(string $old_password, string $password, User $user): void
    {
        if (!Hash::check($old_password, $user->password))
            throw  ValidationException::withMessages([
                'old_password' => 'Invalid old password'
            ]);

        $user->password = bcrypt($password);

        $user->save();
    }
}
