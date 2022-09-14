<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Transformers\Admin\UserTransformer;
use App\Filters\UserFilters;


use App\Http\Requests\Auth\RegisterRequest;



class UsersController extends Controller
{
  /*  public function __construct()
    {
        $this->middleware(['role:admin']);
    }
*/


/**
     * List all users
     *
     * @param UserFilters $filters
     * @return \Inertia\Response
     */
    public function index(UserFilters $filters)
    {
        return Inertia::render('Admin/Users/Index', [
            'roles' => [
                ['value' => 1, 'text' => 'Admin'],
                ['value' => 2, 'text' => 'Customer'],
                 
            ],
            'users' => function () use($filters) {
                return fractal(User::with('roles:id,name')->filter($filters)
                ->paginate(request()->perPage != null ? request()->perPage : 10),
		               new UserTransformer())->toArray();
		          },
            

            // 'schools' => School::select(['id as value', 'name as text'])->active()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create', 
        [
            'roles' => [
                ['value' => 1, 'text' => 'Admin'],
                ['value' => 2, 'text' => 'Customer'],
            // ['value' => 3, 'text' => 'Guest'],
             ],
        ]
    );
    }

    public function store(RegisterRequest $request )
    {

        $user = User::create(
            
            array_merge($request->validated(),
             [
             'photo_path' => Request::file('photo') ? Request::file('photo')->store('users') : null,
             ]
             )
        
        );
        if($user) {
            $user->assignRole($request['role']);
        }
        return Redirect::route('users')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [          
            'roles' => [
                    ['value' => 1, 'text' => 'Admin'],
                    ['value' => 2, 'text' => 'Customer'],
            ],

            'user' => $user,
        ]);
    }

    public function update(User $user)
    {
        
        Request::validate([
            'first_name' => ['required', 'max:50'],
            'last_name' => ['required', 'max:50'],
            'email' => ['required', 'max:50', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable'],
            'photo' => ['nullable', 'image'],

        ]);

        $user->update(Request::only('first_name', 'last_name', 'email'));

        if (Request::file('photo')) {
            $user->update(['photo_path' => Request::file('photo')->store('users')]);
        }

        if (Request::get('password')) {
            $user->update(['password' => Request::get('password')]);
        }
        if($user) {
            $user->syncRoles(Request::only('role'));
            // $user->assignRole(Request::only('role'));
        }

        return Redirect::back()->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        return Redirect::back()->with('error', 'Deleting the demo user is not allowed.');  
        $user->delete();
        return Redirect::back()->with('success', 'User deleted.');
    }

    public function restore(User $user)
    {
        $user->restore();

        return Redirect::back()->with('success', 'User restored.');
    }
}
