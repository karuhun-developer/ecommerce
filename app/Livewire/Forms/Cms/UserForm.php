<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Cms\UserData;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UserForm extends Form
{
    public string $role = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public function setUser(User $user): void
    {
        $this->fill($user->only('name', 'email'));
        $this->role = $user->getRoleNames()->first() ?? '';
        $this->password = '';
    }

    public function data(?int $userId): UserData
    {
        return UserData::fromArray($this->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'api')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => $userId ? ['nullable'] : ['required', 'string', 'min:8'],
        ]));
    }
}
