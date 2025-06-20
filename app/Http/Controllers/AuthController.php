<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
            'password' => 'required|string',
            'c_password' => 'required|same:password'
        ]);

        $user = new User([
            'name'  => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        if ($user->save()) {
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->plainTextToken;

            return response()->json([
                'message' => 'Successfully created user!',
                'accessToken' => $token,
            ], 201);
        } else {
            return response()->json(['error' => 'Provide proper details']);
        }
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     */

    public function login(Request $request)

    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;
        $userData = $user->only(['id', 'name', 'email']); // Basic user data
        $userAbilityRules = $this->generateCaslRulesForUser($user); // Use helper

        return response()->json([
            'accessToken' => $token,
            'token_type' => 'Bearer',
            'userData' => $userData,
            'userAbilityRules' => $userAbilityRules,
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }


    /**
     * Helper function to generate CASL rules from Spatie permissions for a user.
     * This is where the core mapping logic lives.
     */
    protected function generateCaslRulesForUser(User $user): array
    {
        $rules = [];

        // Handle Super Admin explicitly - gives manage all
        if ($user->hasRole('Super Admin')) { // Ensure 'Super Admin' matches your seeder
            $rules[] = ['action' => 'manage', 'subject' => 'all'];
            return $rules; // Super Admin gets all, no need to process further Spatie perms for CASL
        }

        // Get all permissions for the user (directly assigned or via roles)
        $permissions = $user->getAllPermissions();

        foreach ($permissions as $permission) {
            // Spatie permission name: e.g., "show user", "create role", "edit faculty darc"
            $nameParts = explode(' ', $permission->name, 2);

            $action = strtolower($nameParts[0]); // "show", "create", "edit", "delete", "view"

            // Map Spatie actions to common CASL actions if needed
            if (in_array($action, ['show', 'view'])) {
                $caslAction = 'read';
            } elseif (in_array($action, ['create', 'add'])) { // 'add' if you use it
                $caslAction = 'create';
            } elseif (in_array($action, ['edit', 'update'])) { // 'update' if you use it
                $caslAction = 'update';
            } elseif ($action === 'delete') {
                $caslAction = 'delete';
            } else {
                $caslAction = $action; // Use as is if it's a custom action like 'approve'
            }

            $subject = 'all'; // Default subject if only one part (e.g., permission "publish")
            if (count($nameParts) > 1) {
                // Convert "user" to "User", "faculty darc" to "FacultyDarc" (PascalCase)
                // This is a common convention for CASL subjects (representing models or resources)
                $subjectString = str_replace('-', ' ', $nameParts[1]); // Handle kebab-case if any
                $subjectString = str_replace('_', ' ', $subjectString); // Handle snake_case
                $subject = Str::studly(Str::singular($subjectString)); // PascalCase and singularize (e.g., "users" -> "User")
                // Or just Str::studly($subjectString) if you prefer "Users" as subject.
                // Be consistent!
            }

            $rules[] = [
                'action' => $caslAction,
                'subject' => $subject,
                // 'conditions' => null, // CASL can also have conditions, e.g., for row-level security
                // 'fields' => null,     // And field-level security
            ];
        }

        // Deduplicate rules if any identical rules were generated
        // (e.g., if user has "view user" and "show user" both mapping to action:read, subject:User)
        $rules = array_map("unserialize", array_unique(array_map("serialize", $rules)));

        return array_values($rules); // Re-index array
    }
}
